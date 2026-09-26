<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Configuration de jeu par tenant (docs/07 §2.1) :
 * - durée de fenêtre nullable : null = défaut config('interflo.answer_window_seconds') (I-31 / EX-19),
 * - mode mesuré activable par chaîne (I-25 / EX-30) : ⚠️ aucune valeur par
 *   défaut n'est tranchée — false par défaut ici, à arbitrer par le PO,
 * - règle de fin de partie (I-28 / EX-35) : tous les survivants ou tirage
 *   au sort — ⚠️ le tirage fait basculer le jeu de l'adresse vers le hasard
 *   (conséquence réglementaire, §10.4 du cadrage),
 * - nombre de gagnants SCELLÉ à 1, 3 ou 5 (I-28) : contrainte CHECK en base
 *   alignée sur config('interflo.sealed.winner_count_options'),
 * - classement persistant entre émissions (I-40 / EX-46) : mécanique NON
 *   spécifiée — simple interrupteur ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_game_configs', function (Blueprint $table): void {
            $table->id();
            // Une seule configuration par tenant, supprimée avec lui.
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            // null = défaut de config('interflo.answer_window_seconds') (I-31).
            $table->unsignedInteger('answer_window_seconds')->nullable();
            // ⚠️ I-25 / EX-30 : aucune valeur par défaut tranchée — false ici, à arbitrer.
            $table->boolean('measured_mode_enabled')->default(false);
            // all_survivors | draw (I-28).
            $table->string('endgame_rule')->default('all_survivors');
            // 1, 3 ou 5 gagnants — options scellées par décision PO (I-28) ;
            // null tant que la règle n'est pas le tirage au sort.
            $table->unsignedTinyInteger('winners_count')->nullable();
            // I-40 / EX-46 : mécanique non spécifiée, interrupteur seulement.
            $table->boolean('persistent_ranking_enabled')->default(false);
            $table->timestamps();
        });

        // Options scellées (I-28) garanties en base, null autorisé.
        DB::statement('ALTER TABLE tenant_game_configs ADD CONSTRAINT winners_count_sealed_options CHECK (winners_count IN (1, 3, 5))');
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_game_configs');
    }
};

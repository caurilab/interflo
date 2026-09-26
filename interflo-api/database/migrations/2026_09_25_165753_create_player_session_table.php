<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Attachement d'un joueur à une session de jeu. Une seule session active par
 * joueur (I-17), faite respecter EN BASE par un index unique partiel
 * PostgreSQL (detached_at IS NULL) — choix documenté : l'invariant est un
 * état serveur, pas un affichage. La logique de service (détachement des
 * autres sessions à l'attachement) complète cet index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_session', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->timestamp('attached_at');
            $table->timestamp('detached_at')->nullable();
            $table->timestamps();
        });

        // I-17 : une seule session active par joueur, garantie en base.
        DB::statement(
            'CREATE UNIQUE INDEX player_session_one_active_per_player
             ON player_session (player_id) WHERE detached_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('player_session');
    }
};

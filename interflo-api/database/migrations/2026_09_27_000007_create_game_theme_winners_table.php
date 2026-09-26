<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — docs/07 §5.
 *
 * Gagnants d'un thème d'élimination, persistés à la fin de partie (I-28) :
 * tous les survivants, ou tirage au sort de 1, 3 ou 5 gagnants (options
 * scellées) parmi les survivants. Le tirage est exécuté côté serveur, UNE
 * SEULE FOIS (idempotent : cette table est la preuve du tirage).
 *
 * ⚠️ Rappel réglementaire (cadrage §10.4) : le tirage au sort fait basculer
 * le jeu de l'adresse vers le hasard — cadre légal à instruire par un tiers
 * compétent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_theme_winners', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained('game_themes')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            // Rang du tirage au sort (1..n) ; null en règle « tous les survivants ».
            $table->unsignedTinyInteger('rank')->nullable();
            $table->timestamps();

            // Un joueur ne gagne qu'une fois par thème.
            $table->unique(['theme_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_theme_winners');
    }
};

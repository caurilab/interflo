<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — docs/07 §5.
 *
 * Manche d'un thème d'élimination (5 manches, scellé I-27), adossée à une
 * question validée (EX-40).
 *
 * L'autorisation de répondre est un ÉTAT SERVEUR (I-2 / R-3 / INV-8) :
 * window_opened_at / window_closed_at sont posés par l'animateur, et hors
 * fenêtre le serveur refuse. ⚠️ La fenêtre de réponse est PERSONNELLE
 * (EX-20) : ces deux colonnes bornent l'enveloppe collective de la manche,
 * la fenêtre individuelle court depuis le served_at de chaque joueur
 * (round_player_states).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('theme_id')->constrained('game_themes')->cascadeOnDelete();
            // 1..5 (scellé I-27) ; une seule manche par numéro dans un thème.
            $table->unsignedTinyInteger('round_number');
            // Question diffusée — validée humainement exigée (EX-40, contrôle
            // côté service). restrictOnDelete : on ne supprime pas la question
            // d'une manche déjà jouée.
            $table->foreignId('question_id')->constrained('questions')->restrictOnDelete();
            // Ouverture / fermeture par l'animateur (I-2). Précision ms :
            // l'enveloppe serveur se calcule depuis window_opened_at.
            $table->timestamp('window_opened_at', 3)->nullable();
            $table->timestamp('window_closed_at', 3)->nullable();
            // Statut : pending | open | closed.
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['theme_id', 'round_number']);
        });

        // Manche 1..5 (scellé I-27), garanti en base.
        DB::statement('ALTER TABLE game_rounds ADD CONSTRAINT game_rounds_round_number_check CHECK (round_number BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};

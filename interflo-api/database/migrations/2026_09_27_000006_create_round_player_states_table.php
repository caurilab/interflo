<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — docs/07 §5.
 *
 * État d'un joueur sur une manche : service de la question, réponse, verdict.
 *
 * served_at : instant où la question a été servie À CE JOUEUR — base de sa
 * fenêtre personnelle (EX-20). Précision ms : le plancher anti-automatisation
 * (I-30) se mesure en millisecondes entre served_at et l'horodatage du geste.
 *
 * ⚠️ PERSISTANCE PROVISOIRE (docs/07 §5, « ne pas modéliser la réponse comme
 * une simple ligne insérée ») : la réponse est ici une ligne relationnelle
 * insérée/mise à jour à chaque geste. Correct fonctionnellement, mais cette
 * forme ne prétend PAS tenir le pic de ~100 000 écritures en quelques
 * secondes — c'est une décision d'architecture temps réel qui n'est pas prise
 * (04-architecture §5). À revoir avec l'ADR temps réel.
 *
 * Le VERROUILLAGE (EX-32) n'est PAS stocké ici : il est DÉDUIT — un joueur
 * est verrouillé jusqu'à la fin du thème dès qu'une manche clôturée du thème
 * ne porte pas de verdict correct pour lui (erreur OU absence de réponse
 * dans sa fenêtre). Choix documenté dans EliminationSurvivorService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('round_player_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('round_id')->constrained('game_rounds')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            // Instant de service de la question à ce joueur (EX-20). ms.
            $table->timestamp('served_at', 3);
            // Réponse du joueur — null tant qu'il n'a pas répondu.
            $table->timestamp('answered_at', 3)->nullable();
            // Index choisi 0..3 (scellé I-4). ⚠️ INV-2 : jamais servi au client.
            $table->unsignedTinyInteger('answer_index')->nullable();
            // Verdict juste/faux calculé SERVEUR (R-4) — null tant que non répondu.
            $table->boolean('is_correct')->nullable();
            // Dernier motif de rejet serveur (audit provisoire) : une réponse
            // rejetée ne consomme pas le droit de répondre.
            $table->string('rejected_reason')->nullable();
            $table->timestamps();

            // Un seul état par joueur et par manche.
            $table->unique(['round_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_player_states');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — docs/07 §5.
 *
 * ⚠️ AUTHENTIFICATION ANIMATEUR PROVISOIRE : l'auth de la console tablette
 * n'est spécifiée NULLE PART dans les documents (ni cadrage, ni PRD, ni
 * contrat). Choix assumé ici : un token opaque par session de jeu, porté par
 * l'en-tête X-Pilot-Token, comparé côté serveur (middleware dédié).
 *
 * Limites assumées : token en clair en base, aucune rotation, aucune
 * révocation individuelle, aucun lien avec l'auth BOS de l'opérateur (I-37).
 * À remplacer dès qu'un arbitrage sur l'auth animateur existe — SIGNALER au PO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            // Token de pilotage animateur (PROVISOIRE — voir docblock).
            $table->string('pilot_token', 64)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->dropColumn('pilot_token');
        });
    }
};

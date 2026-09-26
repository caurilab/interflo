<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Joueur identifié par numéro de téléphone vérifié (I-8). Aucune pièce
 * d'identité (I-8), aucune clé tenant : le joueur est générique, l'application
 * n'est liée à aucune chaîne (I-13).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table): void {
            $table->id();
            // Numéro au format E.164, unique : c'est l'identifiant du joueur (I-8).
            $table->string('phone')->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};

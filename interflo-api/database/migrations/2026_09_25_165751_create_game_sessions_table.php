<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Session de jeu : vit et meurt avec l'émission (I-16). Émission terminée,
 * accès mort.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('emission_id')->constrained()->cascadeOnDelete();
            // Statut : scheduled / live / ended.
            $table->string('status');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};

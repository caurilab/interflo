<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — docs/07 §5.
 *
 * Thème de jeu : une partie d'élimination dans une session (5 manches, I-27).
 * Un thème concerne UNE seule population (I-1) : studio ou domicile, jamais
 * les deux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_themes', function (Blueprint $table): void {
            $table->id();
            // La partie vit dans la session, qui vit avec l'émission (I-16).
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            // 'studio' | 'home' : la population concernée par cette partie (I-1).
            $table->string('population');
            // Statut : active | finished.
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // Les deux seules populations reconnues (I-1), garanties en base.
        DB::statement("ALTER TABLE game_themes ADD CONSTRAINT game_themes_population_check CHECK (population IN ('studio', 'home'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('game_themes');
    }
};

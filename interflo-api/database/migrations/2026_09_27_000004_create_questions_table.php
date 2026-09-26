<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — docs/07 §5.
 *
 * Question de la banque (format élimination) : 4 propositions (scellé I-4),
 * une seule correcte, produite pendant l'émission (I-33) et VALIDÉE PAR UN
 * HUMAIN avant diffusion (EX-40) : validated_at/validated_by null tant que
 * non validée — une question non validée ne peut pas être attachée à une
 * manche (contrôle côté service, pas juste UI).
 *
 * ⚠️ INV-2 / R-4 : correct_index ne doit JAMAIS être servi au client. Il
 * n'apparaît que dans le back-office Filament.
 *
 * theme_id NULLABLE (hypothèse assumée) : une question peut être rédigée à
 * l'avance dans la banque sans thème, puis rattachée au thème quand
 * l'animateur l'y programme en manche. La validation humaine reste exigée
 * dans tous les cas avant diffusion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            // Rattachement au thème — nullable tant que la question est en
            // banque (voir docblock). Conservée si le thème disparaît.
            $table->foreignId('theme_id')->nullable()->constrained('game_themes')->nullOnDelete();
            // Manche visée dans le thème : 1..5 (scellé I-27).
            $table->unsignedTinyInteger('round_number');
            $table->text('body');
            // Exactement 4 propositions (scellé I-4) — vérifié en validation
            // applicative ; le JSON ne peut pas porter de contrainte COUNT.
            $table->json('propositions');
            // Index 0..3 de la bonne réponse. ⚠️ INV-2 : JAMAIS servi au client.
            $table->unsignedTinyInteger('correct_index');
            // Provenance : 'plateau' | 'general_culture' (I-32).
            $table->string('source');
            // Validation humaine (EX-40) : null = NON diffusable.
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });

        // Manche 1..5 (scellé I-27) et index de bonne réponse 0..3 (scellé
        // I-4), garantis en base.
        DB::statement('ALTER TABLE questions ADD CONSTRAINT questions_round_number_check CHECK (round_number BETWEEN 1 AND 5)');
        DB::statement('ALTER TABLE questions ADD CONSTRAINT questions_correct_index_check CHECK (correct_index BETWEEN 0 AND 3)');
        DB::statement("ALTER TABLE questions ADD CONSTRAINT questions_source_check CHECK (source IN ('plateau', 'general_culture'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

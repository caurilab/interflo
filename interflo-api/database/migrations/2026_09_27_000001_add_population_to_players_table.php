<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — docs/07 §5.
 *
 * Population du joueur : studio ou domicile (I-1 / EX-23). Les deux
 * populations ne concourent JAMAIS l'une contre l'autre.
 *
 * ⚠️ HYPOTHÈSE FORTE : le recouvrement avec Voxflo n'est PAS instruit
 * (INTERFLO_PRODUCT.md §16, inconnue n°1) — on ne sait pas comment un joueur
 * devient « studio ». Défaut 'home' pour tout joueur existant ou nouveau ;
 * l'attribution 'studio' reste à concevoir. À revoir dès l'instruction Voxflo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table): void {
            // 'studio' | 'home' — défaut 'home' (voir docblock : inconnue Voxflo).
            $table->string('population')->default('home')->after('phone_verified_at');
        });

        // Les deux seules populations reconnues (I-1), garanties en base.
        DB::statement("ALTER TABLE players ADD CONSTRAINT players_population_check CHECK (population IN ('studio', 'home'))");
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table): void {
            $table->dropColumn('population');
        });
    }
};

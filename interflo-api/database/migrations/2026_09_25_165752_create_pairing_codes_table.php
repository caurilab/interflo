<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Code d'appairage ROTATIF (I-18) : pointeur public vers chaîne + émission
 * (I-15 / INV-6). Un code expiré ne résout plus (EX-04) ; un code photographié
 * est déjà mort. Le code n'autorise rien — il désigne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pairing_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->string('code')->index();
            // Fenêtre de validité : hors [valid_from, valid_until] le code est mort.
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pairing_codes');
    }
};

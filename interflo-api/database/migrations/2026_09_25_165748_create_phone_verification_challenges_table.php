<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Challenge de vérification d'un numéro de téléphone (I-8). Le code OTP
 * n'est jamais stocké en clair : uniquement son hash. Le joueur peut ne pas
 * exister encore, d'où un numéro simple et non une clé étrangère.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_verification_challenges', function (Blueprint $table): void {
            $table->id();
            // Le joueur peut ne pas exister encore : numéro indexé, pas de FK.
            $table->string('phone')->index();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_verification_challenges');
    }
};

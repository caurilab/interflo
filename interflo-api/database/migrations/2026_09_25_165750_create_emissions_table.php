<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Programme diffusé, copié depuis BOS avant l'émission (I-36). Le rattachement
 * exact côté BOS (Programme / Émission / Édition) est à instruire au source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            // Référence BOS, copiée avant l'émission — jamais interrogée en direct.
            $table->string('external_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emissions');
    }
};

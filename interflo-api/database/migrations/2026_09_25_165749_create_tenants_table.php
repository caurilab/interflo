<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 *
 * Chaîne cliente (tenant). Le cloisonnement est exigé (I-38 / INV-1) mais son
 * mécanisme n'est PAS choisi : pas de Stancl Tenancy, une simple colonne
 * tenant_id sur les entités de jeu suffit à ce stade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Future référence BOS : la liste de ce qui est copié depuis BOS
            // n'est PAS arrêtée (inconnue n°3 de docs/07 §1).
            $table->string('external_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

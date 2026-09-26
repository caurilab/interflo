<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Chaîne cliente (tenant). Cloisonne toutes les entités de jeu (I-38 / INV-1).
 *
 * Le mécanisme de cloisonnement n'est PAS choisi (docs/07 §5) : simple
 * colonne tenant_id à ce stade, pas de Stancl Tenancy.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'external_reference',
    ];

    public function emissions(): HasMany
    {
        return $this->hasMany(Emission::class);
    }

    /** Configuration de jeu du tenant (docs/07 §2.1 — PROVISOIRE). */
    public function gameConfig(): HasOne
    {
        return $this->hasOne(TenantGameConfig::class);
    }
}

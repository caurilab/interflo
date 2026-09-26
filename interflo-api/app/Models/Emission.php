<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Programme diffusé, copié depuis BOS avant l'émission (I-36).
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class Emission extends Model
{
    /** @use HasFactory<EmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'external_reference',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Joueur Interflo, identifié par numéro de téléphone vérifié (I-8).
 *
 * Aucune pièce d'identité (I-8), aucune clé tenant : l'application n'est
 * liée à aucune chaîne et le joueur est générique (I-13).
 *
 * Hérite d'Authenticatable pour être porteur de tokens Sanctum.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class Player extends Authenticatable
{
    /** @use HasFactory<PlayerFactory> */
    use HasApiTokens, HasFactory;

    // Populations possibles (I-1) : elles ne concourent jamais l'une contre
    // l'autre.
    public const POPULATION_STUDIO = 'studio';

    public const POPULATION_HOME = 'home';

    protected $fillable = [
        'phone',
        'phone_verified_at',
        // ⚠️ PROVISOIRE : recouvrement Voxflo non instruit (inconnue §16 n°1) —
        // défaut 'home', l'attribution 'studio' reste à concevoir.
        'population',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
        ];
    }

    /** Attachements du joueur aux sessions de jeu (historique compris). */
    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    /** Attachement actif du joueur, s'il existe (I-17 : au plus un). */
    public function activePlayerSession(): HasOne
    {
        return $this->hasOne(PlayerSession::class)->whereNull('detached_at');
    }

    /** Le numéro est-il vérifié ? Condition d'accès au jeu (I-8). */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /** Le joueur appartient-il à la population concernée par le thème ? (I-1) */
    public function belongsToPopulation(GameTheme $theme): bool
    {
        return $theme->concernsPopulation($this->population);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Question de la banque (format élimination) : 4 propositions (scellé I-4),
 * une seule correcte.
 *
 * EX-40 : AUCUNE question ne part à l'antenne sans validation humaine —
 * tant que validated_at est null, la question n'est pas diffusable (contrôle
 * côté service, EliminationThemeService, pas juste côté UI Filament).
 *
 * ⚠️ INV-2 / R-4 : correct_index ne sort JAMAIS du serveur — aucune
 * ressource API joueur ne le sérialise.
 *
 * PROVISOIRE — docs/07 §5.
 */
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    // Provenances possibles (I-32).
    public const SOURCE_PLATEAU = 'plateau';

    public const SOURCE_GENERAL_CULTURE = 'general_culture';

    protected $fillable = [
        'theme_id',
        'round_number',
        'body',
        'propositions',
        'correct_index',
        'source',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'propositions' => 'array',
            'round_number' => 'integer',
            'correct_index' => 'integer',
            'validated_at' => 'datetime',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(GameTheme::class, 'theme_id');
    }

    /** Agent humain ayant validé la question (I-33). */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * La question est-elle diffusable ? EX-40 : pas de validation humaine,
     * pas de diffusion.
     */
    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    /** La question est-elle déjà programmée sur une manche ? */
    public function isUsed(): bool
    {
        return GameRound::query()->where('question_id', $this->id)->exists();
    }
}

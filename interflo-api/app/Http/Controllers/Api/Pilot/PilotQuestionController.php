<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pilot;

use App\Http\Controllers\Controller;
use App\Http\Resources\Pilot\QuestionListItemResource;
use App\Models\GameRound;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Banque de questions diffusable, pour la console animateur (D-002 §4.2).
 *
 * L'animateur programme une manche en CHOISISSANT une question validée
 * (EX-40) et non encore utilisée, à la manche visée (I-27). Cette route
 * remplace le champ d'id numérique provisoire de la session 4.
 *
 * ⚠️ INV-2 / CA-07 : la liste ne porte JAMAIS correct_index.
 * ⚠️ PROVISOIRE (docs/07 §5) : les questions en banque ne sont pas encore
 * rattachées à un tenant — la liste est globale tant que le modèle de
 * données n'est pas finalisé.
 */
class PilotQuestionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $roundNumber = $request->integer('round_number');

        $questions = Question::query()
            // En banque : pas encore rattachée à un thème.
            ->whereNull('theme_id')
            // EX-40 : pas de validation humaine, pas de diffusion.
            ->whereNotNull('validated_at')
            // Inutilisée : pas déjà programmée sur une manche (défensif).
            ->whereNotIn('id', GameRound::query()->pluck('question_id'))
            ->when($roundNumber > 0, fn ($query) => $query->where('round_number', $roundNumber))
            ->orderBy('round_number')
            ->orderBy('id')
            ->get();

        return QuestionListItemResource::collection($questions);
    }
}

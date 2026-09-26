<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Page « Seuils et réglages » — LECTURE SEULE.
 *
 * Affiche les valeurs actives de config('interflo') : les trois valeurs
 * scellées par décision PO et les valeurs paramétrables (points de départ
 * non validés, docs/03-prd.md §10 point 4). Aucune édition ici : c'est
 * l'environnement qui pilote (variables INTERFLO_*).
 */
class ThresholdsSettings extends Page
{
    protected string $view = 'filament.admin.pages.thresholds-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public function getTitle(): string
    {
        return __('panel.thresholds.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel.thresholds.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav_group_settings');
    }

    /**
     * Lignes du tableau : réglage, valeur active, nature (scellée ou
     * paramétrable) et référence de décision (I-xx / EX-xx).
     *
     * @return list<array{label: string, value: string, nature: string, reference: string}>
     */
    public function rows(): array
    {
        // Nature des réglages : scellée (règle du jeu tranchée) ou
        // paramétrable (point de départ non validé, piloté par l'env).
        $sealed = __('panel.thresholds.nature_sealed');
        $configurable = __('panel.thresholds.nature_configurable');
        $unset = __('panel.thresholds.nature_unset');

        $value = static fn (mixed $raw): string => match (true) {
            is_array($raw) => implode(', ', $raw),
            $raw === null => '—',
            default => (string) $raw,
        };

        return [
            ['label' => __('panel.thresholds.settings.propositions_per_question'), 'value' => $value(config('interflo.sealed.propositions_per_question')), 'nature' => $sealed, 'reference' => 'I-4'],
            ['label' => __('panel.thresholds.settings.elimination_rounds'), 'value' => $value(config('interflo.sealed.elimination_rounds')), 'nature' => $sealed, 'reference' => 'I-27'],
            ['label' => __('panel.thresholds.settings.winner_count_options'), 'value' => $value(config('interflo.sealed.winner_count_options')), 'nature' => $sealed, 'reference' => 'I-28'],
            ['label' => __('panel.thresholds.settings.anti_automation_floor_ms'), 'value' => $value(config('interflo.anti_automation_floor_ms')), 'nature' => $configurable, 'reference' => 'I-30 / EX-18'],
            ['label' => __('panel.thresholds.settings.answer_window_seconds'), 'value' => $value(config('interflo.answer_window_seconds')), 'nature' => $configurable, 'reference' => 'I-31 / EX-19'],
            ['label' => __('panel.thresholds.settings.server_envelope_seconds'), 'value' => $value(config('interflo.server_envelope_seconds')), 'nature' => $configurable, 'reference' => 'EX-20'],
            ['label' => __('panel.thresholds.settings.pairing_code_rotation_seconds'), 'value' => $value(config('interflo.pairing_code_rotation_seconds')), 'nature' => $configurable, 'reference' => 'I-18 / EX-04'],
            ['label' => __('panel.thresholds.settings.short_code_length'), 'value' => $value(config('interflo.short_code_length')), 'nature' => $configurable, 'reference' => 'I-14 / EX-02'],
            ['label' => __('panel.thresholds.settings.short_code_alphabet'), 'value' => $value(config('interflo.short_code_alphabet')), 'nature' => $configurable, 'reference' => 'EX-03'],
            ['label' => __('panel.thresholds.settings.buzzer_max_relaunches'), 'value' => $value(config('interflo.buzzer_max_relaunches')), 'nature' => $configurable, 'reference' => 'I-7 / EX-25'],
            ['label' => __('panel.thresholds.settings.general_culture_percent'), 'value' => $value(config('interflo.general_culture_percent')), 'nature' => $unset, 'reference' => 'I-32 / EX-37'],
            ['label' => __('panel.thresholds.settings.otp_length'), 'value' => $value(config('interflo.otp_length')), 'nature' => $configurable, 'reference' => 'I-8'],
            ['label' => __('panel.thresholds.settings.otp_ttl_seconds'), 'value' => $value(config('interflo.otp_ttl_seconds')), 'nature' => $configurable, 'reference' => 'I-8'],
            ['label' => __('panel.thresholds.settings.otp_max_attempts'), 'value' => $value(config('interflo.otp_max_attempts')), 'nature' => $configurable, 'reference' => 'I-8'],
            ['label' => __('panel.thresholds.settings.otp_request_throttle_per_hour'), 'value' => $value(config('interflo.otp_request_throttle_per_hour')), 'nature' => $configurable, 'reference' => 'I-8'],
            ['label' => __('panel.thresholds.settings.otp_verify_throttle_per_minute'), 'value' => $value(config('interflo.otp_verify_throttle_per_minute')), 'nature' => $configurable, 'reference' => 'I-8'],
        ];
    }
}

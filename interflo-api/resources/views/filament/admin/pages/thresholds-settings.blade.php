<x-filament-panels::page>
    {{-- Lecture seule : l'environnement pilote ces valeurs (INTERFLO_*). --}}
    <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">
            {{ __('panel.thresholds.intro') }}
        </p>
        <p class="text-sm font-medium text-warning-600 dark:text-warning-400">
            {{ __('panel.thresholds.unvalidated_notice') }}
        </p>

        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium">{{ __('panel.thresholds.columns.setting') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('panel.thresholds.columns.value') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('panel.thresholds.columns.nature') }}</th>
                        <th class="px-4 py-3 text-start font-medium">{{ __('panel.thresholds.columns.reference') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($this->rows() as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['label'] }}</td>
                            <td class="px-4 py-3 font-mono">{{ $row['value'] }}</td>
                            <td class="px-4 py-3">{{ $row['nature'] }}</td>
                            <td class="px-4 py-3">{{ $row['reference'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

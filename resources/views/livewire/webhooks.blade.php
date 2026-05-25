<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header
        name="Resonate Webhooks"
        title="Time: {{ number_format($time) }}ms; Run at: {{ $runAt }};"
        details="past {{ $this->periodForHumans() }}"
    />

    <x-pulse::scroll :expand="$expand">
        @php
            $deliveredTotal = $readings['delivered']->sum('count');
            $failedTotal = $readings['failed']->sum('count');
            $deliveredByApp = $readings['delivered']->pluck('count', 'key');
            $failedByApp = $readings['failed']->pluck('count', 'key');
            $apps = $deliveredByApp->keys()->merge($failedByApp->keys())->unique();
        @endphp

        <div class="grid grid-cols-2 gap-4 mb-6 px-2">
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Delivered</div>
                <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                    {{ number_format($deliveredTotal) }}
                </div>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Failed</div>
                <div class="text-3xl font-bold text-rose-600 dark:text-rose-400">
                    {{ number_format($failedTotal) }}
                </div>
            </div>
        </div>

        @if ($apps->isEmpty())
            <x-pulse::no-results message="No webhook traffic in this period" />
        @else
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 text-left">
                        <th class="py-2 px-2 font-medium">App</th>
                        <th class="py-2 px-2 font-medium text-right">Delivered</th>
                        <th class="py-2 px-2 font-medium text-right">Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($apps as $app)
                        <tr wire:key="resonate-webhooks:{{ $app }}" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-2 font-mono text-gray-900 dark:text-gray-100 truncate">{{ $app }}</td>
                            <td class="py-2 px-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($deliveredByApp[$app] ?? 0) }}
                            </td>
                            <td class="py-2 px-2 text-right tabular-nums text-rose-600 dark:text-rose-400">
                                {{ number_format($failedByApp[$app] ?? 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>

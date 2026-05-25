<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header
        name="Resonate Token Auth"
        title="Time: {{ number_format($time) }}ms; Run at: {{ $runAt }};"
        details="past {{ $this->periodForHumans() }}"
    />

    <x-pulse::scroll :expand="$expand">
        @php
            $total = $readings->sum('count');
        @endphp

        <div class="mb-6 px-2">
            <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Token rejections</div>
            <div class="text-3xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($total) }}</div>
        </div>

        @if ($readings->isEmpty())
            <x-pulse::no-results message="No token rejections in this period" />
        @else
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 text-left">
                        <th class="py-2 px-2 font-medium">Reason</th>
                        <th class="py-2 px-2 font-medium text-right">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($readings as $row)
                        <tr wire:key="resonate-token-auth:{{ $row->key }}" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-2 font-mono text-gray-900 dark:text-gray-100">{{ $row->key }}</td>
                            <td class="py-2 px-2 text-right tabular-nums text-amber-600 dark:text-amber-400">
                                {{ number_format($row->count) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>

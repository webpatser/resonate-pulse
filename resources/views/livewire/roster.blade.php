<x-pulse::card :cols="$cols" :rows="$rows" :class="$class" wire:poll.5s="">
    <x-pulse::card-header
        name="Resonate Roster"
        title="Time: {{ number_format($time) }}ms; Run at: {{ $runAt }};"
        details="cluster-wide presence and occupancy"
    />

    <x-pulse::scroll :expand="$expand">
        @if ($snapshot['rooms'] === 0)
            <x-pulse::no-results message="No occupied channels" />
        @else
            <div class="grid grid-cols-3 gap-4 mb-6 px-2">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Rooms</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ number_format($snapshot['rooms']) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Users online</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ number_format($snapshot['users']) }}
                    </div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Connections</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                        {{ number_format($snapshot['connections']) }}
                    </div>
                </div>
            </div>

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 text-left">
                        <th class="py-2 px-2 font-medium">Channel</th>
                        <th class="py-2 px-2 font-medium text-right">Users</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($snapshot['top'] as $channel => $count)
                        <tr wire:key="resonate-roster:{{ $channel }}" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-2 px-2 font-mono text-gray-900 dark:text-gray-100 truncate">
                                {{ $channel }}
                            </td>
                            <td class="py-2 px-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($count) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>

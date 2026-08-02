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

            @php($perApplication = count($snapshot['applications']) > 1)

            @if ($perApplication)
                <table class="min-w-full text-sm mb-6">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 text-left">
                            <th class="py-2 px-2 font-medium">Application</th>
                            <th class="py-2 px-2 font-medium text-right">Rooms</th>
                            <th class="py-2 px-2 font-medium text-right">Users</th>
                            <th class="py-2 px-2 font-medium text-right">Connections</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($snapshot['applications'] as $application => $totals)
                            <tr wire:key="resonate-roster-app:{{ $application }}" class="border-t border-gray-100 dark:border-gray-800">
                                <td class="py-2 px-2 font-mono text-gray-900 dark:text-gray-100 truncate">
                                    {{ $application }}
                                </td>
                                <td class="py-2 px-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                    {{ number_format($totals['rooms']) }}
                                </td>
                                <td class="py-2 px-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                    {{ number_format($totals['users']) }}
                                </td>
                                <td class="py-2 px-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                    {{ number_format($totals['connections']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 text-left">
                        @if ($perApplication)
                            <th class="py-2 px-2 font-medium">Application</th>
                        @endif
                        <th class="py-2 px-2 font-medium">Channel</th>
                        <th class="py-2 px-2 font-medium text-right">Users</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($snapshot['top'] as $room)
                        <tr wire:key="resonate-roster:{{ $room['application'] }}:{{ $room['channel'] }}" class="border-t border-gray-100 dark:border-gray-800">
                            @if ($perApplication)
                                <td class="py-2 px-2 font-mono text-gray-500 dark:text-gray-400 truncate">
                                    {{ $room['application'] }}
                                </td>
                            @endif
                            <td class="py-2 px-2 font-mono text-gray-900 dark:text-gray-100 truncate">
                                {{ $room['channel'] }}
                            </td>
                            <td class="py-2 px-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                {{ number_format($room['users']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-pulse::scroll>
</x-pulse::card>

<?php

namespace Webpatser\ResonatePulse;

use Webpatser\ResonateRoster\RoomRoster;

/**
 * Gathers a single snapshot of cluster-wide roster state.
 *
 * Pure orchestration over {@see RoomRoster}; the recorder and the Livewire
 * card both call this so the live view and the recorded series stay in sync.
 */
class RosterMetrics
{
    /**
     * Create a new gatherer.
     */
    public function __construct(protected RoomRoster $roster)
    {
        //
    }

    /**
     * Snapshot the current state.
     *
     * @return array{
     *     rooms: int,
     *     users: int,
     *     connections: int,
     *     top: array<string, int>,
     * }
     */
    public function gather(int $topLimit = 10): array
    {
        $channels = $this->roster->occupiedChannels();

        $users = [];
        $connections = 0;
        $perChannelUsers = [];

        foreach ($channels as $channel) {
            $channelUsers = $this->roster->users($channel);
            $perChannelUsers[$channel] = count($channelUsers);
            $connections += $this->roster->connectionCount($channel);

            foreach ($channelUsers as $userId) {
                $users[$userId] = true;
            }
        }

        arsort($perChannelUsers);

        return [
            'rooms' => count($channels),
            'users' => count($users),
            'connections' => $connections,
            'top' => array_slice($perChannelUsers, 0, $topLimit, preserve_keys: true),
        ];
    }
}

<?php

namespace Webpatser\ResonatePulse;

/**
 * Gathers a single snapshot of cluster-wide roster state.
 *
 * Pure orchestration over {@see RosterSnapshot}; the recorder and the Livewire
 * card both call this so the live view and the recorded series stay in sync.
 *
 * The gathering used to walk the roster one channel at a time, which cost a
 * full keyspace SCAN per question per channel. It now takes the whole roster in
 * one sweep and aggregates in memory, so a snapshot costs the same whether the
 * cluster is running five channels or five hundred.
 */
class RosterMetrics
{
    /**
     * Create a new gatherer.
     */
    public function __construct(protected RosterSnapshot $snapshot)
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
        $channels = $this->snapshot->channels();

        $users = [];
        $connections = 0;
        $perChannelUsers = [];

        foreach ($channels as $channel => $state) {
            $perChannelUsers[$channel] = count($state['users']);
            $connections += $state['connections'];

            // Users are counted cluster-wide, not per channel: one person in
            // three rooms is one user online and three rooms occupied.
            foreach ($state['users'] as $userId) {
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

<?php

namespace Webpatser\ResonatePulse;

/**
 * Gathers a single snapshot of cluster-wide roster state.
 *
 * Pure orchestration over {@see RosterSnapshot}; the recorder and the Livewire
 * card both call this so the live view and the recorded series stay in sync.
 *
 * The gathering used to walk the roster one channel at a time, which cost a
 * full keyspace SCAN per question per channel. It now takes each application's
 * roster in one sweep and aggregates in memory, so a snapshot costs the same
 * whether the cluster is running five channels or five hundred.
 *
 * Figures are gathered per application and then totalled. A roster belongs to
 * one application, so that is the only honest way to add them up: two
 * applications may both serve a "presence-lobby", and those are two rooms with
 * two separate memberships. Before roster 0.3 the keyspace had no application
 * dimension, so such channels were silently merged into one.
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
     * `users` is the sum of each application's distinct users: within one
     * application, a person in three rooms is one user online and three rooms
     * occupied, and the same user id in two applications is two people.
     *
     * @return array{
     *     rooms: int,
     *     users: int,
     *     connections: int,
     *     top: list<array{application: string, channel: string, users: int}>,
     *     applications: array<string, array{rooms: int, users: int, connections: int}>,
     * }
     */
    public function gather(int $topLimit = 10): array
    {
        $rooms = 0;
        $users = 0;
        $connections = 0;

        /** @var array<string, array{rooms: int, users: int, connections: int}> $applications */
        $applications = [];

        /** @var list<array{application: string, channel: string, users: int}> $top */
        $top = [];

        foreach ($this->snapshot->applications() as $appId) {
            $channels = $this->snapshot->channels($appId);

            $appUsers = [];
            $appConnections = 0;

            foreach ($channels as $channel => $state) {
                $appConnections += $state['connections'];

                foreach ($state['users'] as $userId) {
                    $appUsers[$userId] = true;
                }

                $top[] = [
                    'application' => $appId,
                    'channel' => (string) $channel,
                    'users' => count($state['users']),
                ];
            }

            $applications[$appId] = [
                'rooms' => count($channels),
                'users' => count($appUsers),
                'connections' => $appConnections,
            ];

            $rooms += count($channels);
            $users += count($appUsers);
            $connections += $appConnections;
        }

        // usort is stable, so channels tied on user count keep the order the
        // roster reported them in rather than shuffling between polls.
        usort($top, fn (array $first, array $second): int => $second['users'] <=> $first['users']);

        return [
            'rooms' => $rooms,
            'users' => $users,
            'connections' => $connections,
            'top' => array_slice($top, 0, $topLimit),
            'applications' => $applications,
        ];
    }
}

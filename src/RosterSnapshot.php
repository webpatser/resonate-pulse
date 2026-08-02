<?php

namespace Webpatser\ResonatePulse;

use Webpatser\Resonate\Contracts\ApplicationProvider;
use Webpatser\ResonateRoster\RoomRoster;

/**
 * The roster-facing side of the metrics gathering.
 *
 * It is a thin adapter over {@see RoomRoster::snapshot()}, which returns every
 * occupied channel of one application in a single keyspace sweep plus one
 * pipelined batch of `HGETALL`s. This class used to do that sweep itself, with
 * its own predis client and its own copy of the roster's connection handling;
 * roster 0.3 grew the bulk method, so all that is left here is asking for it
 * once per configured application.
 *
 * A roster belongs to one application, so a cluster-wide figure is the sum over
 * the applications this server serves, never a single merged read: two
 * applications may both run a "presence-lobby", and they are two rooms.
 */
class RosterSnapshot
{
    /**
     * Create a new snapshot reader.
     *
     * @param  RoomRoster  $roster  The roster read side, from roster's own container binding.
     * @param  ApplicationProvider|null  $applications  Resonate's configured applications, when it is installed.
     */
    public function __construct(
        protected RoomRoster $roster,
        protected ?ApplicationProvider $applications = null,
    ) {
        //
    }

    /**
     * The ids of the applications to gather from.
     *
     * An empty list means there is nothing to report: without Resonate's
     * application provider bound there are no applications to name, and the
     * roster has no key space to look in either.
     *
     * @return list<string>
     */
    public function applications(): array
    {
        $applications = $this->applications?->all();

        if ($applications === null) {
            return [];
        }

        $ids = [];

        foreach ($applications as $application) {
            $ids[] = $application->id();
        }

        return $ids;
    }

    /**
     * Every occupied channel of one application, with its users and count.
     *
     * @return array<string, array{users: list<string>, connections: int}>
     */
    public function channels(?string $appId = null): array
    {
        return $this->roster->snapshot($appId);
    }
}

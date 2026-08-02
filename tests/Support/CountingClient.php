<?php

namespace Webpatser\ResonatePulse\Tests\Support;

use Predis\Client;

/**
 * A predis client that counts the commands issued through it.
 *
 * Redis reports command counters itself (`INFO commandstats`), but those are
 * server-wide: the sibling packages' suites run against the same Redis, so a
 * server-side counter cannot tell this snapshot's scans from theirs. Counting
 * in-process keeps the assertion exact and independent of anything else
 * talking to Redis at the time.
 *
 * Commands issued inside a pipeline are queued on the pipeline object, not on
 * the client, so they are deliberately not counted here.
 */
class CountingClient extends Client
{
    /**
     * Commands issued through this client, lowercased, mapped to a call count.
     *
     * @var array<string, int>
     */
    public array $counts = [];

    /**
     * Count the command, then let predis run it.
     *
     * @param  string  $commandID
     * @param  array<int, mixed>  $arguments
     */
    public function __call($commandID, $arguments)
    {
        $name = strtolower((string) $commandID);

        $this->counts[$name] = ($this->counts[$name] ?? 0) + 1;

        return parent::__call($commandID, $arguments);
    }

    /**
     * How many times a command was issued through this client.
     */
    public function callsTo(string $command): int
    {
        return $this->counts[strtolower($command)] ?? 0;
    }
}

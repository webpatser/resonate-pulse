<?php

namespace Webpatser\ResonatePulse;

use Predis\Client;
use Predis\ClientContextInterface;
use Webpatser\ResonateRoster\RoomRoster;
use Webpatser\ResonateRoster\RosterKeys;

/**
 * A single-sweep bulk read of the entire roster keyspace.
 *
 * {@see RoomRoster} answers one question about one
 * channel at a time, and every one of those questions is its own full keyspace
 * SCAN. A dashboard snapshot asks two questions per channel (who is online, how
 * many connections), so gathering C channels through the per-channel API costs
 * 1 + 2C full scans: at 500 channels that is roughly 1000 scans every beat and
 * every dashboard poll, against a Redis that is also carrying the socket
 * server's own traffic.
 *
 * Nothing about the data requires that. One sweep over the roster pattern
 * already yields every hash the snapshot needs: the hash values are the
 * presence user ids and the field count is the connection count. So this class
 * does exactly one SCAN sweep, pipelines the HGETALLs into a single round trip,
 * and groups the result by channel. The cost stops scaling with the number of
 * channels.
 *
 * Two couplings are deliberate and worth knowing about. The key layout comes
 * from roster's own {@see RosterKeys}, never from string literals here, so a
 * change to the schema travels across on a composer update. The connection
 * parameters are read from the published `resonate-roster` config, the same
 * array `RoomRoster` is constructed with. The better long-term home for this is
 * a bulk method on the roster itself; see the README note.
 */
class RosterSnapshot
{
    /**
     * How many keys Redis is asked to look at per SCAN call.
     *
     * Matches the roster's own sweep so the two behave alike under load.
     */
    protected const int SCAN_COUNT = 100;

    /**
     * The roster key schema, owned by the roster package.
     */
    protected RosterKeys $keys;

    /**
     * Create a new bulk reader.
     *
     * The client may be supplied to point the reader at an already configured
     * connection (the test suite injects an instrumented one); left null it is
     * built lazily from the roster connection config on first use.
     *
     * @param  array<string, mixed>  $config  The "resonate-roster" config array.
     */
    public function __construct(protected array $config, protected ?Client $client = null)
    {
        $prefix = $config['key_prefix'] ?? 'roster';

        $this->keys = new RosterKeys(is_string($prefix) ? $prefix : 'roster');
    }

    /**
     * Every occupied channel, with its distinct users and connection count.
     *
     * A channel is reported as occupied whenever it still has a node key, even
     * if that key holds no members, which is what the per-channel API reported
     * too: Redis drops a hash when its last field goes, so an empty one is a
     * momentary state rather than a lasting one.
     *
     * @return array<string, array{users: list<string>, connections: int}>
     */
    public function channels(): array
    {
        $keys = $this->keysMatching($this->keys->allPattern());

        if ($keys === []) {
            return [];
        }

        /** @var array<string, array<string, true>> $users */
        $users = [];

        /** @var array<string, int> $connections */
        $connections = [];

        foreach ($this->hashes($keys) as $index => $hash) {
            $key = $keys[$index] ?? null;

            if ($key === null) {
                continue;
            }

            $channel = (string) $this->keys->channelFromKey($key);

            $users[$channel] ??= [];
            $connections[$channel] = ($connections[$channel] ?? 0) + count($hash);

            // The hash is socket id => presence user id. A blank user id is a
            // non-presence member, which counts as a connection but not as a
            // distinct user, exactly as the roster's own reader treats it.
            foreach ($hash as $userId) {
                if (is_string($userId) && $userId !== '') {
                    $users[$channel][$userId] = true;
                }
            }
        }

        $channels = [];

        foreach ($connections as $channel => $count) {
            $channel = (string) $channel;

            $channels[$channel] = [
                'users' => array_map(strval(...), array_keys($users[$channel] ?? [])),
                'connections' => $count,
            ];
        }

        return $channels;
    }

    /**
     * Collect every key matching a pattern with one non-blocking SCAN sweep.
     *
     * @return list<string>
     */
    protected function keysMatching(string $pattern): array
    {
        $client = $this->client();
        $cursor = '0';
        $keys = [];

        do {
            // predis answers SCAN as [next cursor, matched keys]; both halves
            // are read defensively so an unexpected reply ends the sweep
            // rather than spinning on it.
            $parts = array_values($client->scan($cursor, ['MATCH' => $pattern, 'COUNT' => self::SCAN_COUNT]));

            $next = $parts[0] ?? '0';
            $batch = $parts[1] ?? [];

            if (is_array($batch)) {
                foreach ($batch as $key) {
                    if (is_string($key)) {
                        $keys[] = $key;
                    }
                }
            }

            $cursor = is_scalar($next) ? (string) $next : '0';
        } while ($cursor !== '0');

        return $keys;
    }

    /**
     * Read every key's hash in a single pipelined round trip.
     *
     * Ordering is what makes this usable: predis returns one reply per queued
     * command, in the order queued, so index i of the result belongs to key i.
     *
     * @param  list<string>  $keys
     * @return list<array<array-key, mixed>>
     */
    protected function hashes(array $keys): array
    {
        $results = $this->client()->pipeline(function (ClientContextInterface $pipe) use ($keys): void {
            foreach ($keys as $key) {
                $pipe->hgetall($key);
            }
        });

        if (! is_array($results)) {
            return [];
        }

        $hashes = [];

        foreach (array_values($results) as $result) {
            $hashes[] = is_array($result) ? $result : [];
        }

        return $hashes;
    }

    /**
     * Resolve the predis client, building it on first use.
     */
    protected function client(): Client
    {
        return $this->client ??= new Client($this->parameters());
    }

    /**
     * Translate the roster connection config into predis parameters.
     *
     * @return array<string, mixed>|string
     */
    protected function parameters(): array|string
    {
        $server = $this->config['connection'] ?? [];

        if (! is_array($server)) {
            $server = [];
        }

        $url = $this->stringOrNull($server['url'] ?? null);

        if ($url !== null && $url !== '') {
            return $url;
        }

        $parameters = [
            'scheme' => 'tcp',
            'host' => $this->stringOrNull($server['host'] ?? null) ?? '127.0.0.1',
            'port' => (int) ($this->stringOrNull($server['port'] ?? null) ?? '6379'),
            'database' => (int) ($this->stringOrNull($server['database'] ?? null) ?? '0'),
        ];

        $username = $this->stringOrNull($server['username'] ?? null);
        $password = $this->stringOrNull($server['password'] ?? null);
        $timeout = $this->stringOrNull($server['timeout'] ?? null);

        if ($username !== null && $username !== '') {
            $parameters['username'] = $username;
        }

        if ($password !== null && $password !== '') {
            $parameters['password'] = $password;
        }

        if ($timeout !== null && $timeout !== '') {
            $parameters['timeout'] = (float) $timeout;
        }

        return $parameters;
    }

    /**
     * Narrow a config value to a string, or null when it cannot be one.
     */
    protected function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }
}

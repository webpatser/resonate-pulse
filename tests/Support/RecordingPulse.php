<?php

namespace Webpatser\ResonatePulse\Tests\Support;

use DateTimeInterface;
use Laravel\Pulse\Entry;
use Laravel\Pulse\Pulse;
use UnitEnum;

/**
 * A Pulse instance that collects what a recorder wrote instead of buffering it.
 *
 * The sampling-gate tests care about which beats produced a record and at what
 * timestamp, so this returns a detached {@see Entry} rather than calling
 * through to the real buffer, which would drag Pulse storage into the test.
 */
class RecordingPulse extends Pulse
{
    /**
     * Every record() call, in order.
     *
     * @var list<array{type: string, key: string, value: ?int, timestamp: int}>
     */
    public array $recorded = [];

    /**
     * Capture a record instead of buffering it.
     */
    public function record(
        UnitEnum|string $type,
        UnitEnum|string $key,
        ?int $value = null,
        DateTimeInterface|int|null $timestamp = null,
    ): Entry {
        $at = match (true) {
            $timestamp instanceof DateTimeInterface => $timestamp->getTimestamp(),
            is_int($timestamp) => $timestamp,
            default => 0,
        };

        $entry = new Entry(
            timestamp: $at,
            type: $this->name($type),
            key: $this->name($key),
            value: $value,
        );

        $this->recorded[] = [
            'type' => $entry->type,
            'key' => $entry->key,
            'value' => $value,
            'timestamp' => $at,
        ];

        return $entry;
    }

    /**
     * The timestamps recorded for a single series, in order.
     *
     * @return list<int>
     */
    public function timestampsFor(string $type): array
    {
        $timestamps = [];

        foreach ($this->recorded as $record) {
            if ($record['type'] === $type) {
                $timestamps[] = $record['timestamp'];
            }
        }

        return $timestamps;
    }

    /**
     * Resolve the string form of a type or key.
     */
    protected function name(UnitEnum|string $value): string
    {
        return is_string($value) ? $value : $value->name;
    }
}

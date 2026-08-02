<?php

use Carbon\CarbonImmutable;
use Laravel\Pulse\Events\IsolatedBeat;
use Webpatser\ResonatePulse\Pulse\RosterRecorder;
use Webpatser\ResonatePulse\Tests\Support\RecordingPulse;
use Webpatser\ResonatePulse\Tests\Support\StubRosterMetrics;

/**
 * Drive the recorder with one beat per second and report, in seconds from the
 * start, the offsets at which it sampled.
 *
 * @return list<int>
 */
function sampleOffsets(int $interval, int $seconds, int $startSecond = 0): array
{
    config()->set('resonate-pulse.interval', $interval);

    $pulse = new RecordingPulse(app());
    $recorder = new RosterRecorder($pulse, new StubRosterMetrics);

    $start = CarbonImmutable::create(2026, 8, 2, 12, 0, $startSecond);

    foreach (range(0, $seconds) as $offset) {
        $recorder->record(new IsolatedBeat($start->addSeconds($offset)));
    }

    return array_map(
        fn (int $timestamp): int => $timestamp - $start->getTimestamp(),
        $pulse->timestampsFor('resonate_roster_rooms'),
    );
}

it('samples on the first beat and then every interval', function () {
    expect(sampleOffsets(interval: 15, seconds: 60))->toBe([0, 15, 30, 45, 60]);
});

it('keeps an even gap for an interval that does not divide 60', function () {
    // Second-of-minute modulo fired at :00 and :45, so the gaps alternated
    // between 45 and 15 seconds: offsets 0, 45, 60, 105, 120.
    expect(sampleOffsets(interval: 45, seconds: 135))->toBe([0, 45, 90, 135]);
});

it('honours an interval of a minute or more', function () {
    // Second-of-minute modulo could only ever match at :00 for an interval of
    // 60 or more, collapsing every such setting to one sample a minute:
    // offsets 0, 60, 120, 180.
    expect(sampleOffsets(interval: 90, seconds: 180))->toBe([0, 90, 180]);
});

it('does not depend on where in the minute the process starts', function () {
    // Starting at :07 with a 20 second interval, modulo sampling would have
    // waited until :20 and then run on the minute's grid rather than on the
    // interval, giving offsets 13, 33, 53.
    expect(sampleOffsets(interval: 20, seconds: 60, startSecond: 7))->toBe([0, 20, 40, 60]);
});

it('never samples when the interval is disabled', function () {
    expect(sampleOffsets(interval: 0, seconds: 120))->toBe([])
        ->and(sampleOffsets(interval: -5, seconds: 120))->toBe([]);
});

it('writes all three series on a sample', function () {
    config()->set('resonate-pulse.interval', 15);

    $pulse = new RecordingPulse(app());
    $metrics = new StubRosterMetrics;
    $recorder = new RosterRecorder($pulse, $metrics);

    $recorder->record(new IsolatedBeat(CarbonImmutable::create(2026, 8, 2, 12, 0, 0)));

    expect($metrics->gathered)->toBe(1)
        ->and(array_column($pulse->recorded, 'type'))->toBe([
            'resonate_roster_rooms',
            'resonate_roster_users',
            'resonate_roster_connections',
        ])
        ->and(array_column($pulse->recorded, 'value'))->toBe([2, 3, 4]);
});

it('gathers only on the beats it samples', function () {
    config()->set('resonate-pulse.interval', 30);

    $pulse = new RecordingPulse(app());
    $metrics = new StubRosterMetrics;
    $recorder = new RosterRecorder($pulse, $metrics);

    $start = CarbonImmutable::create(2026, 8, 2, 12, 0, 0);

    foreach (range(0, 59) as $offset) {
        $recorder->record(new IsolatedBeat($start->addSeconds($offset)));
    }

    // A snapshot is the expensive part, so a skipped beat must not gather.
    expect($metrics->gathered)->toBe(2);
});

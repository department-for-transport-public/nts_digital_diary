<?php

namespace App\Tests\Functional\Wizard\Action;

use App\Entity\Metrics\MetricsLog;
use App\Utility\Metrics\Events\DiaryStateEvent;

class MetricsTestAction extends AbstractAction
{
    protected const array IGNORED_METRICS = [
        'Diary: state change',
        'Login: success',
    ];

    /**
     * @param array<string, int> $expectedEvents Map of event name => expected count
     */
    public function __construct(protected array $expectedEvents)
    {
    }

    public function perform(Context $context): void
    {
        $this->outputDebugHeader($context);

        $metricsEm = $context->getConfig('metricsEntityManager');
        $metricsEm->clear();
        $logs = $metricsEm->getRepository(MetricsLog::class)->findAll();
        $events = array_map(fn(MetricsLog $l) => $l->getEvent(), $logs);

        $eventCounts = array_count_values($events);
        $eventCounts = array_filter($eventCounts, fn(string $event) => !in_array($event, self::IGNORED_METRICS), ARRAY_FILTER_USE_KEY);

        $testCase = $context->getTestCase();
        foreach ($this->expectedEvents as $eventName => $expectedCount) {
            $testCase->assertEquals(
                $expectedCount,
                $eventCounts[$eventName] ?? 0,
                "Expected {$expectedCount}x \"{$eventName}\" metric, found " . ($eventCounts[$eventName] ?? 0)
            );
        }

        $unexpected = array_diff_key($eventCounts, $this->expectedEvents);
        $testCase->assertEmpty(
            $unexpected,
            "Unexpected metrics recorded: " . implode(', ', array_map(
                fn(string $event, int $count) => "{$count}x \"{$event}\"",
                array_keys($unexpected),
                array_values($unexpected)
            ))
        );
    }
}

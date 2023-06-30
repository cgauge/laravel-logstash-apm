<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\DurationCalculator;
use CustomerGauge\Logstash\Processors\QueueProcessorInterface;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Monolog\LogRecord;

final class QueueListener
{
    private static array $queue = [];

    public function __construct(private ApmSocket $socket, QueueProcessorInterface $processor)
    {
        $this->socket->pushProcessor($processor);
    }

    public function handle($event): void
    {
        if ($event instanceof JobProcessing) {
            self::$queue[$event->job->getJobId()] = microtime(true);
        }

        if ($event instanceof JobProcessed || $event instanceof JobExceptionOccurred || $event instanceof JobFailed) {
            $start = self::$queue[$event->job->getJobId()];

            unset(self::$queue[$event->job->getJobId()]);

            $duration = DurationCalculator::since($start);

            $success = $event instanceof JobProcessed;

            $record = MetricRecord::queue($success, $duration);

            $this->socket->handle($record);
        }
    }
}

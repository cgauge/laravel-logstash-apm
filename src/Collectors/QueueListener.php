<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\DurationCalculator;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use CustomerGauge\Logstash\Sockets\QueueApmSocket;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

final class QueueListener
{
    private static array $queue = [];

    private QueueApmSocket $socket;

    public function __construct(QueueApmSocket $socket)
    {
        $this->socket = $socket;
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

            $failed = $event instanceof JobProcessed ? false : true;

            $record = [
                'level' => ApmSocket::METRIC_LEVEL,
                'level_name' => ApmSocket::METRIC_LEVEL_NAME,
                'failed' => $failed,
                'duration' => $duration,
            ];

            $this->socket->handle($record);
        }
    }
}

<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\Processors\DurationProcessor;
use CustomerGauge\Logstash\Sockets\BackgroundApmSocket;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

final class WorkerListener
{
    private static array $queue = [];

    private BackgroundApmSocket $socket;

    public function __construct(BackgroundApmSocket $socket)
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

            $duration = DurationProcessor::since($start);

            $failed = $event instanceof JobProcessed ? false : true;

            $record = [
                'duration' => $duration,
                'failed' => $failed,
            ];

            $this->socket->handle($record);
        }
    }
}

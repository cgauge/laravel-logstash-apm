<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\DurationCalculator;
use CustomerGauge\Logstash\Processors\HttpProcessorInterface;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use Illuminate\Foundation\Http\Events\RequestHandled;

final class RequestHandledListener
{
    public function __construct(
        private ApmSocket $socket,
        HttpProcessorInterface $processor
    ) {
        $this->socket->pushProcessor($processor);
    }

    public function handle(RequestHandled $event): void
    {
        // @TODO: this will not work on Octane
        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);

        $record = MetricRecord::http(
            $event->response->getStatusCode(),
            DurationCalculator::since($start),
        );

        $this->socket->handle($record);
    }
}

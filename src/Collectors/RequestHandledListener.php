<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\DurationCalculator;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use CustomerGauge\Logstash\Sockets\HttpApmSocket;
use Illuminate\Foundation\Http\Events\RequestHandled;

final class RequestHandledListener
{
    private $socket;

    public function __construct(HttpApmSocket $socket)
    {
        $this->socket = $socket;
    }

    public function handle(RequestHandled $event): void
    {
        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);

        $this->socket->handle([
            'level' => ApmSocket::METRIC_LEVEL,
            'level_name' => ApmSocket::METRIC_LEVEL_NAME,
            'status' => $event->response->getStatusCode(),
            'duration' => DurationCalculator::since($start),
        ]);
    }
}

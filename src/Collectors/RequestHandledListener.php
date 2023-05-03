<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\DurationCalculator;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use CustomerGauge\Logstash\Sockets\HttpApmSocket;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Http\Events\RequestHandled;

final class RequestHandledListener
{
    private $socket;

    private $config;

    public function __construct(HttpApmSocket $socket, Repository $config)
    {
        $this->socket = $socket;
        $this->config = $config;
    }

    public function handle(RequestHandled $event): void
    {
        $start = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);

        $etag = $this->config->get('logging.apm.etag');

        $attributes = [
            'level' => ApmSocket::METRIC_LEVEL,
            'level_name' => ApmSocket::METRIC_LEVEL_NAME,
            'status' => $event->response->getStatusCode(),
            'duration' => DurationCalculator::since($start),
        ];

        if ($etag) {
            $attributes['etag'] = $event->response->getContent() ? sha1($event->response->getContent()) : 'empty-response';
        }

        $this->socket->handle($attributes);
    }
}

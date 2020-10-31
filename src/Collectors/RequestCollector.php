<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\Sockets\ApmSocket;
use Illuminate\Foundation\Http\Events\RequestHandled;

final class RequestCollector
{
    private ApmSocket $socket;

    public function __construct(ApmSocket $socket)
    {
        $this->socket = $socket;
    }

    public function handle(RequestHandled $event): void
    {
        $this->socket->handle(['status' => $event->response->getStatusCode()]);
    }
}

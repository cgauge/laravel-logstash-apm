<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use CustomerGauge\Logstash\Sockets\HttpApmSocket;
use Illuminate\Foundation\Http\Events\RequestHandled;

final class RequestHandledListener
{
    private HttpApmSocket $socket;

    public function __construct(HttpApmSocket $socket)
    {
        $this->socket = $socket;
    }

    public function handle(RequestHandled $event): void
    {
        $this->socket->handle(['status' => $event->response->getStatusCode()]);
    }
}

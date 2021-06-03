<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Sockets;

final class QueueApmSocket
{
    public $socket;

    public function __construct(ApmSocket $socket)
    {
        $this->socket = $socket;
    }

    public function handle(array $record): bool
    {
        return $this->socket->handle($record);
    }
}

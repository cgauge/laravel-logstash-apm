<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Sockets;

use Monolog\LogRecord;

final class QueueApmSocket
{
    public $socket;

    public function __construct(ApmSocket $socket)
    {
        $this->socket = $socket;
    }

    public function handle(LogRecord $record): bool
    {
        return $this->socket->handle($record);
    }
}

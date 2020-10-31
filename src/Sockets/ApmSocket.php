<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Sockets;

use Monolog\Handler\SocketHandler;

final class ApmSocket extends SocketHandler
{
    public function isHandling(array $record): bool
    {
        // For Application Performance Monitoring we want to use all the power that
        // Monolog offers with the Socket Handler, while ignoring logging level.
        // Anytime metrics are being recorded, we will just always handle it.
        return true;
    }
}

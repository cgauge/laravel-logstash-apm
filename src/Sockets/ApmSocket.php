<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Sockets;

use Exception;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\SocketHandler;
use Throwable;

final class ApmSocket implements HandlerInterface
{
    private SocketHandler $handler;

    public function __construct(SocketHandler $handler)
    {
        $this->handler = $handler;
    }

    public function isHandling(array $record): bool
    {
        // For Application Performance Monitoring we want to use all the power that
        // Monolog offers with the Socket Handler, while ignoring logging level.
        // Anytime metrics are being recorded, we will just always handle it.
        return true;
    }

    public function handle(array $record): bool
    {
        try {
            return $this->handler->handle($record);
        } catch (Throwable | Exception $e) {
            return false;
        }
    }

    public function handleBatch(array $records): void
    {
        $this->handler->handleBatch($records);
    }

    public function close(): void
    {
        $this->handler->close();
    }
}

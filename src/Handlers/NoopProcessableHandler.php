<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Handlers;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Processor\ProcessorInterface;

final class NoopProcessableHandler implements HandlerInterface, ProcessableHandlerInterface
{
    public function isHandling(array $record): bool
    {
        return false;
    }

    public function handle(array $record): bool
    {
        return false;
    }

    public function handleBatch(array $records): void
    {

    }

    public function close(): void
    {

    }

    public function pushProcessor(callable $callback): HandlerInterface
    {
        return $this;
    }

    public function popProcessor(): callable
    {
        return function () {

        };
    }
}

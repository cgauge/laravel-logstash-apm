<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

use Exception;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Throwable;

final class GracefulHandlerAdapter implements HandlerInterface, ProcessableHandlerInterface
{
    private $handler;

    public function __construct(AbstractProcessingHandler $handler)
    {
        $this->handler = $handler;
    }

    public function isHandling(array $record): bool
    {
        return $this->handler->isHandling($record);
    }

    public function handle(array $record): bool
    {
        try {
            $this->handler->handle($record);
        } catch (Throwable | Exception $e) {
            // Returning false means we're letting the record $bubble up the stack
            // and Monolog will process the same record with the next Handler.
            return false;
        }

        // Returning true means we're informing Monolog that we don't want to let
        // the $record bubble up and that this Handler was enough.
        return true;
    }

    public function handleBatch(array $records): void
    {

    }

    public function close(): void
    {
        $this->handler->close();
    }

    public function pushProcessor(callable $callback): HandlerInterface
    {
        $this->handler->pushProcessor($callback);

        return $this;
    }

    public function popProcessor(): callable
    {
        return $this->handler->popProcessor();
    }
}

<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

use Exception;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Throwable;

final class GracefulHandlerAdapter implements HandlerInterface, ProcessableHandlerInterface
{
    private $handler;

    private $stderr;

    public function __construct(AbstractProcessingHandler $handler, StreamHandler $stderr)
    {
        $this->handler = $handler;
        $this->stderr = $stderr;
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
            $message = 'An error occurred while trying to handle a log record. ';

            $this->stderr->handle([
                'level' => Logger::CRITICAL,
                'message' => $message . PHP_EOL . json_encode($record) . PHP_EOL . $e->getMessage(),
            ]);

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

<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

use Aws\Sqs\SqsClient;
use CustomerGauge\Logstash\Handlers\NoopProcessableHandler;
use CustomerGauge\Logstash\Processors\BacktraceProcessor;
use CustomerGauge\Logstash\Processors\ConsoleProcessorInterface;
use CustomerGauge\Logstash\Processors\HttpProcessorInterface;
use CustomerGauge\Logstash\Processors\QueueProcessorInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\SqsHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class LogstashLoggerFactory
{
    private $container;

    private $stderr;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->stderr = $this->stderr();
    }

    public function __invoke(array $config): LoggerInterface
    {
        try {
            // Monolog accepts multiple handlers for logs by providing a stack.
            // We'll use it as a fallback if the handler fails. The order
            // will be: Logstash, SQS and Stderr.
            $handlers = $this->handlers($config);

        } catch (Throwable $t) {
            // If anything goes wrong while building up the Logger Handlers, let's write it onto
            // stderr. The idea here is to keep stderr as simple as possible, without any
            // custom configuration so that it never fails to be written.
            $this->stderr->handle(['level' => Logger::ERROR, 'message' => $t->getMessage()]);

            return new Logger('emergency', [$this->stderr]);
        }

        return new Logger('', $handlers);
    }

    private function handlers(array $config): array
    {
        $level = $config['level'] ?? Logger::DEBUG;

        $socket = $this->socket($config['address'], $level);

        $sqs = $this->sqs($config, $level);

        // Let's set the processors in the Socket and in the SQS handlers.
        // If Logstash fails, we'll try to get the exact same set of data
        // into SQS. If both of them fails, we'll let Monolog write the
        // original dataset into Stderr.
        $handlers = $this->processor([$socket, $sqs], $config['processor'] ?? null);

        $handlers[] = $this->stderr;

        return array_values(array_filter($handlers));
    }

    /**
     * @param ProcessableHandlerInterface[] $handlers
     *
     * @return callable[]
     */
    private function processor(array $handlers, ?string $processor): array
    {
        $backtrace = $this->container->get(BacktraceProcessor::class);

        if ($processor === 'http') {
            $processor = $this->container->make(HttpProcessorInterface::class);
        } elseif ($processor === 'queue') {
            $processor = $this->container->make(QueueProcessorInterface::class);
        } elseif ($processor === 'console') {
            $processor = $this->container->make(ConsoleProcessorInterface::class);
        }

        foreach ($handlers as $handler) {
            if ($processor) {
                $handler->pushProcessor($processor);
            }

            $handler->pushProcessor($backtrace);
        }

        return $handlers;
    }

    private function socket(string $address, /*string|int*/ $level): GracefulHandlerAdapter
    {
        $socket = new SocketHandler($address, $level, false);

        $socket->setConnectionTimeout(1);

        $socket->setFormatter(new JsonFormatter);

        return new GracefulHandlerAdapter($socket, $this->stderr);
    }

    /**
     * @return  GracefulHandlerAdapter | NoopProcessableHandler
     */
    private function sqs(array $config, /*string|int*/ $level) /*: SqsHandler | NoopProcessableHandler*/
    {
        if (! isset($config['fallback']['queue'])) {
            return new NoopProcessableHandler;
        }

        $queue = $config['fallback']['queue'];

        $region = $config['fallback']['region'];

        $client = new SqsClient([
            'region' => $region,
            'version' => '2012-11-05'
        ]);

        $handler = new SqsHandler($client, $queue, $level);

        $handler->setFormatter(new JsonFormatter);

        return new GracefulHandlerAdapter($handler, $this->stderr);
    }

    private function stderr(): StreamHandler
    {
        // This Handler MUST not process any custom project information because if they fail to be processed
        // then we don't have any reliable way to write logs anywhere. Think of this as a log channel for
        // the log library itself. If anything here goes wrong we'll at least have signs of it.
        $stderr = new StreamHandler('php://stderr', Logger::DEBUG);

        $formatter = new JsonFormatter;

        $formatter->includeStacktraces();

        $stderr->setFormatter($formatter);

        return $stderr;
    }
}

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

final class LogstashLoggerFactory
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(array $config): LoggerInterface
    {
        // Monolog accepts multiple handlers for logs by providing a stack.
        // We'll use it as a fallback if the handler fails. The order
        // will be: Logstash, SQS and Stderr.
        $handlers = $this->handlers($config);

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

        $handlers[] = $this->stderr($level);

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

        return new GracefulHandlerAdapter($socket);
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

        return new GracefulHandlerAdapter($handler);
    }

    private function stderr(int $level): StreamHandler
    {
        $stderr = new StreamHandler('php://stderr', $level);

        $formatter = new JsonFormatter;

        $formatter->includeStacktraces();

        $stderr->setFormatter($formatter);

        return $stderr;
    }
}

<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

use Aws\Sqs\SqsClient;
use CustomerGauge\Logstash\Handlers\NoopProcessableHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\SqsHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class LogstashLoggerFactory
{
    private ContainerInterface $container;

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
        $handlers = $this->processors([$socket, $sqs], $config['processors'] ?? []);

        $stderr = new StreamHandler('php://stderr', $level);

        $formatter = new LineFormatter;

        $formatter->includeStacktraces();

        $stderr->setFormatter($formatter);

        $handlers[] = $stderr;

        return array_values(array_filter($handlers));
    }

    /**
     * @param ProcessableHandlerInterface[] $handlers
     *
     * @return callable[]
     */
    private function processors(array $handlers, array $processors): array
    {
        $processors = array_reverse($processors);

        foreach ($processors as $processor) {
            $process = $this->container->make($processor);

            foreach ($handlers as $handler) {
                $handler->pushProcessor($process);
            }
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

        return new GracefulHandlerAdapter($handler);
    }
}

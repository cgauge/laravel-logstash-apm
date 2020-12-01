<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

use Aws\Sqs\SqsClient;
use Monolog\Formatter\JsonFormatter;
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
        $processors = $this->processors($config['processors'] ?? []);

        $handlers = $this->handlers($config);

        return new Logger('', $handlers, $processors);
    }

    /**
     * @return callable[]
     */
    private function processors(array $processors): array
    {
        $processors = array_reverse($processors);

        $instances = [];

        foreach ($processors as $processor) {
            $instances[] = $this->container->make($processor);
        }

        return $instances;
    }

    private function handlers(array $config): array
    {
        $level = $config['level'] ?? Logger::DEBUG;

        $socket = $this->socket($config['address'], $level);

        $sqs = $this->sqs($config, $level);

        $stderr = new StreamHandler('php://stderr', $level);

        return array_filter([$socket, $sqs, $stderr]);
    }

    private function socket(string $address, /*string|int*/ $level): GracefulHandlerAdapter
    {
        $socket = new SocketHandler($address, $level, false);

        $socket->setFormatter(new JsonFormatter);

        return new GracefulHandlerAdapter($socket);
    }

    private function sqs(array $config, /*string|int*/ $level): ?GracefulHandlerAdapter
    {
        if (! isset($config['fallback']['queue'])) {
            return null;
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

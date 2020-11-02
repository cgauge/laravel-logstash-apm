<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\SocketHandler;
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
        $level = $config['level'] ?? Logger::DEBUG;

        $processors = $config['processors'] ?? [];

        $socket = $this->socket($config['address'], $level, $processors);

        return new Logger('', [$socket]);
    }

    private function socket(string $address, /*string|int*/ $level, array $processors): SocketHandler
    {
        $socket = new SocketHandler($address, $level);

        $socket->setFormatter(new JsonFormatter);

        $processors = array_reverse($processors);

        foreach ($processors as $processor) {
            $socket->pushProcessor($this->container->make($processor));
        }

        return $socket;
    }
}

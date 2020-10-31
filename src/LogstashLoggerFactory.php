<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

use CustomerGauge\Logstash\Sockets\LogSocket;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LogstashLoggerFactory
{
    private LogSocket $socket;

    public function __construct(LogSocket $socket)
    {
        $this->socket = $socket;
    }

    public function __invoke(array $config): LoggerInterface
    {
        return new Logger('', [$this->socket]);
    }
}

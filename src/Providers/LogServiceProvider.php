<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Sockets\LogSocket;
use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

final class LogServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(LogSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $address = $config->get('logging.logstash.log.address');

            $level = $config->get('logging.logstash.log.level', Logger::DEBUG);

            $processors = $config->get('logging.logstash.log.processors', []);

            $socket = new LogSocket($address, $level);

            $socket->setFormatter(new JsonFormatter);

            $processors = array_reverse($processors);

            foreach ($processors as $processor) {
                $socket->pushProcessor($this->app->make($processor));
            }

            return $socket;
        });
    }
}

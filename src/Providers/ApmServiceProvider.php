<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Processors\ConsoleProcessorInterface;
use CustomerGauge\Logstash\Processors\HttpProcessorInterface;
use CustomerGauge\Logstash\Processors\QueueProcessorInterface;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use CustomerGauge\Logstash\Sockets\ConsoleApmSocket;
use CustomerGauge\Logstash\Sockets\HttpApmSocket;
use CustomerGauge\Logstash\Sockets\QueueApmSocket;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\JsonFormatter;

final class ApmServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ApmSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $host = $config->get('logging.apm.address');

            $socket = new ApmSocket($host, ApmSocket::METRIC_LEVEL);

            $socket->setFormatter(new JsonFormatter);

            return $socket;
        });

        $this->app->bind(HttpApmSocket::class, function (Container $app) {
            $processor = $app->make(HttpProcessorInterface::class);

            /** @var ApmSocket $socket */
            $socket = clone $app->make(ApmSocket::class);

            $socket->pushProcessor($processor);

            return new HttpApmSocket($socket);
        });

        $this->app->bind(QueueApmSocket::class, function (Container $app) {
            $processor = $app->make(QueueProcessorInterface::class);

            /** @var ApmSocket $socket */
            $socket = clone $app->make(ApmSocket::class);

            $socket->pushProcessor($processor);

            return new QueueApmSocket($socket);
        });

        $this->app->bind(ConsoleApmSocket::class, function (Container $app) {
            $processor = $app->make(ConsoleProcessorInterface::class);

            /** @var ApmSocket $socket */
            $socket = clone $app->make(ApmSocket::class);

            $socket->pushProcessor($processor);

            return new ConsoleApmSocket($socket);
        });
    }
}

<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Sockets\ApmSocket;
use CustomerGauge\Logstash\Sockets\HttpApmSocket;
use CustomerGauge\Logstash\Sockets\QueueApmSocket;
use Illuminate\Config\Repository;
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

        $this->app->bind(HttpApmSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $processor = $config->get('logging.processor.http');

            $socket = $this->prepareSocketWithProcessors($processor);

            return new HttpApmSocket($socket);
        });

        $this->app->bind(QueueApmSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $processor = $config->get('logging.processor.queue');

            $socket = $this->prepareSocketWithProcessors($processor);

            return new QueueApmSocket($socket);
        });
    }

    private function prepareSocketWithProcessors(?string $processor): ApmSocket
    {
        /** @var ApmSocket $socket */
        $socket = clone $this->app->make(ApmSocket::class);

        if ($processor) {
            $socket->pushProcessor($this->app->make($processor));
        }

        return $socket;
    }
}

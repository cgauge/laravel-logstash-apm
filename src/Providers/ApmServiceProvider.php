<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Processors\ApmProcessor;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use CustomerGauge\Logstash\Sockets\HttpApmSocket;
use CustomerGauge\Logstash\Sockets\BackgroundApmSocket;
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

            $socket = new ApmSocket($host, ApmProcessor::METRIC_LEVEL);

            $socket->setFormatter(new JsonFormatter);

            return $socket;
        });

        $this->app->bind(HttpApmSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $processors = $config->get('logging.apm.http', []);

            $socket = $this->prepareSocketWithProcessors($processors);

            return new HttpApmSocket($socket);
        });

        $this->app->bind(BackgroundApmSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $processors = $config->get('logging.apm.background', []);

            $socket = $this->prepareSocketWithProcessors($processors);

            return new BackgroundApmSocket($socket);
        });
    }

    private function prepareSocketWithProcessors(array $processors): ApmSocket
    {
        $socket = clone $this->app->make(ApmSocket::class);

        $processors[] = ApmProcessor::class;

        $processors = array_reverse($processors);

        foreach ($processors as $processor) {
            $socket->pushProcessor($this->app->make($processor));
        }

        return $socket;
    }
}

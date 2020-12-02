<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Processors\ApmProcessor;
use CustomerGauge\Logstash\Sockets\ApmSocket;
use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\JsonFormatter;

final class ApmServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ApmSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $host = $config->get('logging.apm.address');

            $processors = $config->get('logging.apm.processors', []);

            $socket = new ApmSocket($host, ApmProcessor::METRIC_LEVEL);

            $socket->setFormatter(new JsonFormatter);

            $processors[] = ApmProcessor::class;

            $processors = array_reverse($processors);

            foreach ($processors as $processor) {
                $socket->pushProcessor($this->app->make($processor));
            }

            return $socket;
        });
    }
}

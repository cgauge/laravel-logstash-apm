<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Sockets\ApmSocket;
use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\SocketHandler;

final class ApmServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ApmSocket::class, function () {
            $config = $this->app->make(Repository::class);

            $host = $config->get('logging.apm.address');

            $processors = $config->get('logging.apm.processors', []);

            $socket = new SocketHandler($host);

            $socket->setFormatter(new JsonFormatter);

            $processors = array_reverse($processors);

            foreach ($processors as $processor) {
                $socket->pushProcessor($this->app->make($processor));
            }

            return new ApmSocket($socket);
        });
    }
}

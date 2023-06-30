<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Sockets\ApmSocket;
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

            $socket = new ApmSocket($host);

            $socket->setFormatter(new JsonFormatter);

            return $socket;
        });
    }
}

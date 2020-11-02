<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\ServiceProvider;

final class RequestCollector extends ServiceProvider
{
    public function boot()
    {
        $config = $this->app->make(Repository::class);

        $enable = $config->get('logging.apm.enable');

        if ($enable) {
            $dispatcher = $this->app->make(Dispatcher::class);

            $dispatcher->listen(RequestHandled::class, RequestHandledListener::class);
        }
    }
}

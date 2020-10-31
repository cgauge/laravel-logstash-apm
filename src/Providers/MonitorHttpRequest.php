<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Providers;

use CustomerGauge\Logstash\Collectors\RequestCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\ServiceProvider;

final class MonitorHttpRequest extends ServiceProvider
{
    public function boot()
    {
        $dispatcher = $this->app->make(Dispatcher::class);

        /** @var Dispatcher $dispatcher */
        $dispatcher->listen(RequestHandled::class, RequestCollector::class);
    }
}

<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Middleware;

use Closure;
use CustomerGauge\Logstash\Collectors\RequestCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;

final class MonitorHttpRequest
{
    private Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(Request $request, Closure $next)
    {
        $this->dispatcher->listen(RequestHandled::class, RequestCollector::class);

        return $next($request);
    }
}

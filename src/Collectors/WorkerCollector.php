<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;

final class WorkerCollector extends ServiceProvider
{
    public function boot()
    {
        $config = $this->app->make(Repository::class);

        $enable = $config->get('logging.apm.enable');

        if ($enable) {
            $dispatcher = $this->app->make(Dispatcher::class);

            $dispatcher->listen(JobProcessing::class, WorkerListener::class);

            $dispatcher->listen(JobProcessed::class, WorkerListener::class);

            $dispatcher->listen(JobFailed::class, WorkerListener::class);

            $dispatcher->listen(JobExceptionOccurred::class, WorkerListener::class);
        }
    }
}

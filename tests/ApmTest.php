<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Logstash;

use CustomerGauge\Logstash\Collectors\RequestCollector;
use CustomerGauge\Logstash\Collectors\BackgroundCollector;
use CustomerGauge\Logstash\Processors\DurationProcessor;
use CustomerGauge\Logstash\Processors\HttpProcessor;
use CustomerGauge\Logstash\Processors\UuidProcessor;
use CustomerGauge\Logstash\Providers\ApmServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

final class ApmTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ApmServiceProvider::class, RequestCollector::class, BackgroundCollector::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $now = microtime(true);

        $app->bind(DurationProcessor::class, fn() => new DurationProcessor($now));

        $app['config']->set('logging.apm', [
            'enable' => true,
            'address' => 'udp://logstash:9602',
            'http' => [
                UuidProcessor::class,
                HttpProcessor::class,
                DurationProcessor::class,
            ],
            'background' => [
                UuidProcessor::class,
                DurationProcessor::class,
            ],
        ]);
    }

    public function test_request_apm()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(fn() => $uuid);

        Route::get('/my-request', function () {
            return ['success' => true];
        });

        $this->get('/my-request')->assertSuccessful();

        retry(10, function () use ($uuid) {
            $response = Http::post('http://elasticsearch:9200/_search', [
                'query' => [
                    'term' => [
                        'uuid.keyword' => [
                            'value' => $uuid->toString(),
                        ],
                    ],
                ],
            ])->json();

            $this->assertSame(1, $response['hits']['total']['value']);

            $this->assertSame($uuid->toString(), $response['hits']['hits'][0]['_source']['uuid']);

            $this->assertSame('http', $response['hits']['hits'][0]['_source']['type']);

            $this->assertSame('my-request', $response['hits']['hits'][0]['_source']['action']);

            $this->assertSame(-1, $response['hits']['hits'][0]['_source']['user']);

        }, 750);

        Str::createUuidsNormally();
    }

    public function test_background_worker_apm()
    {
        $uuid = Str::uuid();

        Str::createUuidsUsing(fn() => $uuid);

        $job = new SyncJob($this->app, 'payload', 'testing', 'sync');

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app->make(Dispatcher::class);

        $dispatcher->dispatch(new JobProcessing('testing', $job));

        $dispatcher->dispatch(new JobProcessed('testing', $job));

        retry(10, function () use ($uuid) {
            $response = Http::post('http://elasticsearch:9200/_search', [
                'query' => [
                    'term' => [
                        'uuid.keyword' => [
                            'value' => $uuid->toString(),
                        ],
                    ],
                ],
            ])->json();

            $this->assertSame(1, $response['hits']['total']['value']);

            $this->assertSame($uuid->toString(), $response['hits']['hits'][0]['_source']['uuid']);

        }, 750);

        Str::createUuidsNormally();
    }
}

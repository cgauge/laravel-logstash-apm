<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Logstash;

use CustomerGauge\Logstash\Collectors\RequestCollector;
use CustomerGauge\Logstash\Processors\DurationProcessor;
use CustomerGauge\Logstash\Processors\HttpProcessor;
use CustomerGauge\Logstash\Processors\UuidProcessor;
use CustomerGauge\Logstash\Providers\ApmServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

final class ApmTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ApmServiceProvider::class, RequestCollector::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $now = microtime(true);

        $app->bind(DurationProcessor::class, fn() => new DurationProcessor($now));

        $app['config']->set('logging.apm', [
            'enable' => true,
            'address' => 'udp://logstash:9602',
            'processors' => [
                UuidProcessor::class,
                HttpProcessor::class,
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

            $this->assertSame('unauthenticated', $response['hits']['hits'][0]['_source']['user']);

        }, 750);

        Str::createUuidsNormally();
    }
}

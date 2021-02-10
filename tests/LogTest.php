<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Logstash;

use CustomerGauge\Logstash\LogstashLoggerFactory;
use CustomerGauge\Logstash\Processors\BacktraceProcessor;
use CustomerGauge\Logstash\Processors\DurationProcessor;
use CustomerGauge\Logstash\Processors\UuidProcessor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Psr\Log\LoggerInterface;

final class LogTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $now = microtime(true);

        $app->bind(DurationProcessor::class, fn() => new DurationProcessor($now));

        $app['config']->set('logging.channels', [
            'http' => [
                'driver' => 'custom',
                'via' => LogstashLoggerFactory::class,
                'address' => 'tcp://logstash:9601',
                'processors' => [
                    UuidProcessor::class,
                    BacktraceProcessor::class,
                    DurationProcessor::class,
                ],
            ],

            'background' => [
                'driver' => 'custom',
                'via' => LogstashLoggerFactory::class,
                'address' => 'tcp://logstash:9601',
                'processors' => [
                    UuidProcessor::class,
                    BacktraceProcessor::class,
                ],
            ]
        ]);
    }

    public function channels()
    {
        yield ['http'];

        yield ['background'];
    }

    /**
     * @dataProvider channels
     */
    public function test_logstash_log(string $channel)
    {
        $this->app['config']->set('logging.default', $channel);

        $uuid = Str::uuid();

        Str::createUuidsUsing(fn() => $uuid);

        $log = $this->app->make(LoggerInterface::class);

        /** @var LoggerInterface $log */
        $log->debug('testing logstash');

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

            $this->assertSame('testing logstash', $response['hits']['hits'][0]['_source']['message']);

            $this->assertSame($uuid->toString(), $response['hits']['hits'][0]['_source']['uuid']);

        }, 750);

        Str::createUuidsNormally();
    }
}

<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Logstash;

use CustomerGauge\Logstash\LogstashLoggerFactory;
use CustomerGauge\Logstash\Processors\HttpProcessorInterface;
use CustomerGauge\Logstash\Processors\QueueProcessorInterface;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use Psr\Log\LoggerInterface;

final class LogTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->bind(HttpProcessorInterface::class, MyProcessor::class);

        $app->bind(QueueProcessorInterface::class, MyProcessor::class);

        $app['config']->set('logging.channels', [
            'logstash' => [
                'driver' => 'custom',
                'via' => LogstashLoggerFactory::class,
                'address' => 'tcp://logstash:9601',
            ],
        ]);

        $app['config']->set('logging.default', 'logstash');
    }

    public static function channels(): iterable
    {
        yield ['http'];

        yield ['queue'];
    }

    /**
     * @dataProvider channels
     */
    public function test_logstash_log(string $processor)
    {
        $this->app['config']->set('logging.channels.logstash.processor', $processor);

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

<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Logstash;

use CustomerGauge\Logstash\LogstashLoggerFactory;
use CustomerGauge\Logstash\Processors\HttpProcessorInterface;
use CustomerGauge\Logstash\Processors\QueueProcessorInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_logstash_log_http()
    {
        $processor = 'http';

        $this->app['config']->set('logging.channels.logstash.processor', $processor);

        $uuid = Str::uuid();

        Str::createUuidsUsing(fn() => $uuid);

        $log = $this->app->make(LoggerInterface::class);

        /** @var LoggerInterface $log */
        $log->debug('testing logstash http');

        retry(10, function () use ($uuid) {
            $response = Http::post('http://elasticsearch:9200/_search', [
                'query' => [
                    'term' => [
                        'message' => 'http',
                    ],
                ],
            ])->json();

            $this->assertSame(1, $response['hits']['total']['value']);

            $this->assertSame('testing logstash http', $response['hits']['hits'][0]['_source']['message']);

            $this->assertSame($uuid->toString(), $response['hits']['hits'][0]['_source']['extra']['uuid']);

        }, 750);

        Str::createUuidsNormally();
    }

    public function test_logstash_log_queue()
    {
        $processor = 'queue';

        $this->app['config']->set('logging.channels.logstash.processor', $processor);

        $uuid = Str::uuid();

        Str::createUuidsUsing(fn() => $uuid);

        $log = $this->app->make(LoggerInterface::class);

        /** @var LoggerInterface $log */
        $log->debug('testing logstash queue');

        retry(10, function () use ($uuid) {
            $response = Http::post('http://elasticsearch:9200/_search', [
                'query' => [
                    'term' => [
                        'message' => 'queue',
                    ],
                ],
            ])->json();

            $this->assertSame(1, $response['hits']['total']['value']);

            $this->assertSame('testing logstash queue', $response['hits']['hits'][0]['_source']['message']);

            $this->assertSame($uuid->toString(), $response['hits']['hits'][0]['_source']['extra']['uuid']);

        }, 750);

        Str::createUuidsNormally();
    }
}

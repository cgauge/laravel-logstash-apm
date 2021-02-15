<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Logstash;

use CustomerGauge\Logstash\Processors\HttpProcessorInterface;
use CustomerGauge\Logstash\Processors\QueueProcessorInterface;
use Illuminate\Support\Str;

final class MyProcessor implements HttpProcessorInterface, QueueProcessorInterface
{
    public function __invoke(array $record)
    {
        return ['uuid' => Str::uuid()->toString()] + $record;
    }
}

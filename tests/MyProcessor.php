<?php declare(strict_types=1);

namespace Tests\CustomerGauge\Logstash;

use CustomerGauge\Logstash\Processors\HttpProcessorInterface;
use CustomerGauge\Logstash\Processors\QueueProcessorInterface;
use Illuminate\Support\Str;
use Monolog\LogRecord;

final class MyProcessor implements HttpProcessorInterface, QueueProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(['uuid' => Str::uuid()->toString()]);
    }
}

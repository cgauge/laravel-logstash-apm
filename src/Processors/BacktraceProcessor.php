<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final class BacktraceProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record)
    {
        $exception = $record['context']['exception'] ?? null;

        unset($record['context']['exception'], $record['channel'], $record['context']['userId']);

        $backtrace = ['backtrace_php' => optional($exception)->getTraceAsString()];

        return array_filter($record + $backtrace);
    }
}

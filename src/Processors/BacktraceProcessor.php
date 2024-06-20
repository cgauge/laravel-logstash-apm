<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Monolog\Processor\ProcessorInterface;
use Monolog\LogRecord;

final class BacktraceProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record)
    {
        $exception = $record['context']['exception'] ?? null;

        $record['context']['exception'] = null;
        $record['context']['userId'] = null;

        if ($exception) {
            if (empty($record['extra'])) {
                $record['extra'] = [];
            }
            
            $record['extra']['backtrace_php'] = $exception->getTraceAsString();
        }

        return $record;
    }
}

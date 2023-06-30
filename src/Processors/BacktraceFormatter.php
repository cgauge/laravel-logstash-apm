<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

final class BacktraceFormatter extends JsonFormatter
{
    public function format(LogRecord $record): string
    {
        $class = new class extends LogRecord {
            public function toArray(): array
            {
                /** @var \Throwable|null $exception */
                $exception = $this->context['exception'] ?? null;

                return array_filter([
                    'message' => $this->message,
                    'level' => $this->level->value,
                    'backtrace_php' => $exception?->getTraceAsString(),
                ]);
            }
        };

        return parent::format($record);
    }
}

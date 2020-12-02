<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Monolog\Processor\ProcessorInterface;

final class ApmProcessor implements ProcessorInterface
{
    public const METRIC_LEVEL = 10;

    public function __invoke(array $record)
    {
        $record['level'] = self::METRIC_LEVEL;

        $record['level_name'] = 'METRIC';

        return $record;
    }
}

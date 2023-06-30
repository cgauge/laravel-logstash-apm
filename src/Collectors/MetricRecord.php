<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Collectors;

use Monolog\LogRecord;

final class MetricRecord extends LogRecord
{
    private array $attributes = [];

    public function __construct() {}

    public static function queue(bool $success, int $duration): self
    {
        $record = new self();

        $record->attributes['success'] = $success;

        $record->attributes['duration'] = $duration;

        return $record;
    }

    public static function http(int $status, int $since): self
    {
        $record = new self();

        $record->attributes['status'] = $status;

        $record->attributes['duration'] = $since;

        return $record;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Illuminate\Support\Str;
use Monolog\Processor\ProcessorInterface;

final class UuidProcessor implements ProcessorInterface
{
    private string $uuid;

    public function __construct(?string $uuid = null)
    {
        $this->uuid = $uuid ?? Str::uuid()->toString();
    }

    public function __invoke(array $record)
    {
        return ['uuid' => $this->uuid] + $record;
    }
}

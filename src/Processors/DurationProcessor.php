<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Monolog\Processor\ProcessorInterface;

final class DurationProcessor implements ProcessorInterface
{
    private ?float $start;

    public function __construct(?float $start = null)
    {
        $this->start = $start ?? LARAVEL_START;
    }

    public function __invoke(array $record)
    {
        // We multiply by 1000 to go from microseconds to milliseconds and then cast to int
        // to throw any residue microsecond away. Worst-case scenario, we're rouding
        // one millisecond down.
        $duration = (int)((microtime(true) - $this->start) * 1000);

        return ['duration' => $duration] + $record;
    }
}

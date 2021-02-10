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

    public static function since(float $start)
    {
        // We multiply by 1000 to go from microseconds to milliseconds and then cast to int
        // to throw any residue microsecond away. Worst-case scenario, we're rouding
        // one millisecond down.
        return (int) ((microtime(true) - $start) * 1000);
    }

    public function __invoke(array $record)
    {
        $duration = self::since($this->start);

        return ['duration' => $duration] + $record;
    }
}

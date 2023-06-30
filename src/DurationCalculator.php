<?php declare(strict_types=1);

namespace CustomerGauge\Logstash;

final class DurationCalculator
{
    public static function since(float $start): int
    {
        // We multiply by 1000 to go from microseconds to milliseconds and then cast to int
        // to throw any residue microsecond away. Worst-case scenario, we're rounding
        // one millisecond down.
        return (int) ((microtime(true) - $start) * 1000);
    }
}

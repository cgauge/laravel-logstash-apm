<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Sockets;

use Exception;
use Monolog\Handler\SocketHandler;
use Throwable;
use Monolog\LogRecord;

final class ApmSocket extends SocketHandler
{
    public const METRIC_LEVEL = 10;

    public const METRIC_LEVEL_NAME = 'METRIC';

    public function isHandling(LogRecord $record): bool
    {
        // For Application Performance Monitoring we want to use all the power that
        // Monolog offers with the Socket Handler, while ignoring logging level.
        // Anytime metrics are being recorded, we will just always handle it.
        return true;
    }

    public function handle(LogRecord $record): bool
    {
        try {
            return parent::handle($record);
        } catch (Throwable | Exception $e) {
            return false;
        }
    }
}

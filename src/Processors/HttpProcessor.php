<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Illuminate\Http\Request;
use Monolog\Processor\ProcessorInterface;

final class HttpProcessor implements ProcessorInterface
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function __invoke(array $record)
    {
        $request = [
            'type' => 'http',
            'user' => optional($this->request->user())->getAuthIdentifier() ?? -1,
            'action' => $this->request->path(),
        ];

        return $request + $record;
    }
}

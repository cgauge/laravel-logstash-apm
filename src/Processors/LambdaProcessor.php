<?php declare(strict_types=1);

namespace CustomerGauge\Logstash\Processors;

use Bref\Context\Context;
use Monolog\Processor\ProcessorInterface;

final class LambdaProcessor implements ProcessorInterface
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function __invoke(array $record)
    {
        return ['aws' => $this->context->getAwsRequestId()] + $record;
    }
}

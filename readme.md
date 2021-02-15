# Laravel Logstash APM 📋

This library adapts Monolog for Laravel to communicate with Logstash
for logging & metrics.

# Installation

```bash
composer require customergauge/logstash
```

# Usage

### Logs

In the `logging.php`, define a custom channel for Laravel

```php
'http' => [
    'driver' => 'custom',
    'via' => LogstashLoggerFactory::class,
    'address' => sprintf('tcp://%s:9601', env('LOGSTASH_ADDRESS')),
    'processor' => env('LOGSTASH_PROCESSOR'),
],
```

This will instruct Laravel to write any log to Logstash using a TCP
connection on port 9601. TCP will guarantee log delivery. Processors
allow to manipulate the `$record` array that gets sent to Logstash
as JSON. The `processor` attribute accept `http` or `queue`
values.

### Metrics

The library also allows to collect application metrics using an
`apm` configuration inside the `logging.php` file.

```php
    'apm' => [
        'enable' => env('LOGSTASH_APM_ENABLE', true),
        'address' => sprintf('udp://%s:9602', env('LOGSTASH_ADDRESS')),
    ],
```

For metrics, the above configuration will instruct Laravel to 
use an UDP conncetion as a way to fire-and-forget. Delivery
is not guaranteed, but code execution is not delayed by 
acknowledging metric delivery.


### Processors

You may configure Http and Queue processors. Processors must
implement the `\CustomerGauge\Logstash\Processors\HttpProcessorInterface`
or the `\CustomerGauge\Logstash\Processors\QueueProcessorInterface`.
They offer the chance to modify the `$record` that will be
streamed to Logstash by using the `ProcessorInterface` system
provided by Monolog.

```php

    'processor' => [
        'http' => \App\My\HttpProcessor::class,
        'queue' => \App\My\QueueProcessor::class,
    ],
```
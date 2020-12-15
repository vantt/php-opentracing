# tracing-php

## Required reading

In order to understand the library, one must first be familiar with the
[OpenTracing project](http://opentracing.io) and
[specification](http://opentracing.io/documentation/pages/spec.html) more specifically.

## Installation

This library can be installed via Composer:

```bash
composer require quinluong/tracing-php
```

## Usage

### Creating new tracer

```php
use Tracing\Tracer;

$tracer = Tracer::getInstance('tracer_name', [
    'enable' => true,
    'host_port' => 'agent_host:port',
    'sampler_type' => 'const', // const, probabilistic
    'sampler_value' => 1 // const: 0 / 1, probabilistic: 0 -> 1
]);
```

### Extracting span context from request header

```php
use OpenTracing\Formats;

$spanOptions = [
    'tags' => [
        'tag_key_1' => 'tag_value_1',
        'tag_key_2' => 'tag_value_2'
    ]
];

// Extract and use it for next span
$spanContext = $tracer->extract(Formats\TEXT_MAP, getallheaders());

if ($spanContext !== null) {
    $spanOptions['child_of'] = $spanContext;
}

$tracer->startActiveSpan('operation_name', $spanOptions);
```

### Injecting span context into request header

```php
use OpenTracing\Formats;

$tracer->inject($span->getContext(), Formats\TEXT_MAP, $arrHeader);
```

### Creating span

For most use cases, it is recommended that you use the `Tracer::startActiveSpan` function for
creating new spans.

An example of a linear, two level deep span tree using active spans looks like this in PHP code:

```php
// At dispatcher level
$scope = $tracer->startActiveSpan('request');
...
$scope->close();
```

```php
// At controller level
$scope = $tracer->startActiveSpan('controller');
...
$scope->close();
```

```php
// At RPC calls level
$scope = $tracer->startActiveSpan('http');
file_get_contents('http://php.net');
$scope->close();
```

When using the `Tracer::startActiveSpan` function the underlying tracer uses an
abstraction called scope manager to keep track of the currently active span.

Starting an active span will always use the currently active span as a parent.
If no parent is available, then the newly created span is considered to be the
root span of the trace.

Unless you are using asynchronous code that tracks multiple spans at the same
time, such as when using cURL Multi Exec or MySQLi Polling it is recommended that you
use `Tracer::startActiveSpan` everywhere in your application.

The currently active span gets automatically finished when you call `$scope->close()`
as you can see in the previous examples.

#### Creating a child span using automatic active span management

```php
$parent = $tracer->startActiveSpan('parent');
...
/*
 * Since the parent span has been created by using startActiveSpan we don't need
 * to pass a reference for this child span
 */
$child = $tracer->startActiveSpan('my_second_span');
...
$child->close();
...
$parent->close();
```

#### Creating a child span assigning parent manually

```php
$parent = $tracer->startSpan('parent');
...
$child = $tracer->startSpan('child', [
    'child_of' => $parent
]);
...
$child->finish();
...
$parent->finish();
```

### Tags and logs

```php
// Tags are searchable in UI
$span->setTag('http.status', '200');
$span->setTag('http.url', 'abc.com/api/endpoint');

$tracer->startActiveSpan('my_span', [
    'tags' => [
        'foo-1' => 'bar-1',
        'foo-2' => 'bar-2'
        ...
    ]
]);
```

```php
// Log information
$span->log([
    'error' => 'HTTP request timeout'
    'event' => 'soft error',
    'type' => 'cache timeout',
    'waiter.millis' => 1500
    ...
]);
```

### Flushing spans

PHP as a request scoped language has no simple means to pass the collected spans data to a background process without blocking the main request thread/process. The OpenTracing API makes no assumptions about this, but for PHP that might cause problems for Tracer implementations. This is why the PHP API contains a flush method that allows to trigger a span sending out of process.

```php
register_shutdown_function(function() {
    $tracer = Tracer::getInstance('tracer_name');
    // Flush the tracer to the backend
    $tracer->flush();
});
```

### Pause and resume

```php
$tracer->pause();
...
// This function won't be instrumented
doSomething();
...
$tracer->resume();
```

## Semantic conventions

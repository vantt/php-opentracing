<?php

namespace Tracing\Custom;

use OpenTracing\Buildable;
use OpenTracing\BuildableInterface;
use OpenTracing\NoopScope;
use OpenTracing\NoopScopeManager;
use OpenTracing\NoopSpan;
use OpenTracing\NoopSpanContext;
use OpenTracing\Scope;
use OpenTracing\ScopeManager;
use OpenTracing\Span;
use OpenTracing\SpanContext;
use OpenTracing\Tracer;
use Tracing\PauseAbleInterface;

final class NoopTracer implements Tracer, PauseAbleInterface, BuildableInterface {

    use Buildable;

    public static function create() {
        return new self();
    }

    public function getScopeManager(): ScopeManager {
        return NoopScopeManager::create();
    }

    public function getActiveSpan(): ?Span {
        return NoopScope::create();
    }

    public function startActiveSpan(string $operationName, $options = []): Scope {
        return NoopSpan::create();
    }

    public function startSpan(string $operationName, array $options = []): Span {
        return NoopSpan::create();
    }

    public function inject(SpanContext $spanContext, string $format, &$carrier): void {
    }

    public function extract(string $format, $carrier): ?SpanContext {
        return NoopSpanContext::create();
    }

    public function flush(): void {
    }

    public function getSpans(): array {
        return [];
    }

    public function pause() {
    }

    public function resume() {
    }

}
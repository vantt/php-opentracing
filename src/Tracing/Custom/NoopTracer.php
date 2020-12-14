<?php

namespace Tracing\Custom;

use OpenTracing\NoopScope;
use OpenTracing\NoopScopeManager;
use OpenTracing\NoopSpan;
use OpenTracing\NoopSpanContext;
use OpenTracing\SpanContext;
use OpenTracing\Tracer;

final class NoopTracer implements Tracer {

  public static function create() {
    return new self();
  }

  /**
   * {@inheritdoc}
   */
  public function getScopeManager() {
    return NoopScopeManager::create();
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveSpan() {
    return NoopSpan::create();
  }

  /**
   * {@inheritdoc}
   */
  public function startActiveSpan($operationName, $finishSpanOnClose = true, $options = []) {

    return NoopScope::create();
  }

  /**
   * {@inheritdoc}
   */
  public function startSpan($operationName, $options = []) {
    return NoopSpan::create();
  }

  /**
   * {@inheritdoc}
   */
  public function inject(SpanContext $spanContext, $format, &$carrier) {
  }

  /**
   * {@inheritdoc}
   */
  public function extract($format, $carrier) {
    return NoopSpanContext::create();
  }

  /**
   * {@inheritdoc}
   */
  public function flush() {
  }

  public function getSpans() {
    return [];
  }

}

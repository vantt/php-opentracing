<?php

namespace Tracing;

use OpenTracing\NoopSpan;
use OpenTracing\NoopTracer as OpenTracingNoopTracer;

class NoopTracer extends OpenTracingNoopTracer {

  /**
   * {@inheritdoc}
   */
  public function startSpan($operationName, $options = []) {
    // fix bug NoopTracer does not return Span
    return NoopSpan::create();
  }
}

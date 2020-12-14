<?php

namespace Tracing;

use Jaeger\Span as JaegerSpan;

class Span extends JaegerSpan {

  private function microtimeToInt() {
    return intval(microtime(true) * 1000000);
  }

  public function setTag($key, $value) {
    $tmpTags = $this->tags;

    $tmpTags[$key] = $value;

    if (Util::exceedSizeInBytes($tmpTags, Tracing::MAX_TAG_SIZE_PER_SPAN_IN_BYTES)) {
      throw new \Exception('Tags data exceeds the limit, maximum is ' . Tracing::MAX_TAG_SIZE_PER_SPAN_IN_BYTES . ' KB', -1);
    }

    $this->tags = $tmpTags;
  }

  public function log(array $fields = [], $timestamp = null) {
    $log['timestamp'] = $timestamp ? $timestamp : $this->microtimeToInt();
    $log['fields'] = $fields;

    $tmpLogs = $this->logs;

    $tmpLogs[] = $log;

    if (Util::exceedSizeInBytes($tmpLogs, Tracing::MAX_LOG_SIZE_PER_SPAN_IN_BYTES)) {
      throw new \Exception('Tags data exceeds the limit, maximum is ' . Tracing::MAX_LOG_SIZE_PER_SPAN_IN_BYTES . ' KB', -1);
    }

    $this->logs = $tmpLogs;
  }

}

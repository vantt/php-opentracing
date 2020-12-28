<?php

namespace Tracing;

use Jaeger\Config;
use Jaeger\Constants;
use Jaeger\Sampler\ConstSampler;
use Jaeger\Sampler\ProbabilisticSampler;
use OpenTracing\SpanContext;
use Tracing\Custom\NoopTracer;
use Tracing\TracerInterface;
use Tracing\Util\SizeUtil;

class JaegerTracer implements TracerInterface {

  private $_noopTracer = null;

  private $_tracer = null;

  private $_isEnabled = false;

  private $_isPaused = false;

  public function __construct(array $arrConfig = []) {
    $this->_noopTracer = new NoopTracer();

    if (count($arrConfig) === 0) {
      return;
    }

    if (
      !isset($arrConfig['enable'])
      || (bool) $arrConfig['enable'] === false
      || !isset($arrConfig['name'])
      || !isset($arrConfig['host_port'])
      || !isset($arrConfig['sampler_type'])
    ) {
      return;
    }

    $strName = (string) $arrConfig['name'];

    $strHostPort = (string) $arrConfig['host_port'];

    $strSamplerType = (string) $arrConfig['sampler_type'];

    if (
      trim($strName) === ''
      || trim($strHostPort) === ''
      || trim($strSamplerType) === ''
    ) {
      return;
    }

    $strName = trim($strName);

    $strHostPort = trim($strHostPort);

    $strSamplerType = trim($strSamplerType);

    if (
      $strSamplerType !== 'const' && $strSamplerType !== 'probabilistic'
    ) {
      return;
    }

    $tracerConfig = Config::getInstance();

    $tracerConfig->gen128bit();

    $tracerConfig::$propagator = Constants\PROPAGATOR_JAEGER;

    $samplerValue = null;
    if (isset($arrConfig['sampler_value'])) {
      $samplerValue = $arrConfig['sampler_value'];
    }

    switch ($strSamplerType) {
      case 'const':
        $isSampled = false;
        if ($samplerValue !== null) {
          $isSampled = (bool) $samplerValue;
        }

        $tracerConfig->setSampler(new ConstSampler($isSampled));

        break;

      case 'probabilistic':
        $floatValue = 0.0;
        if ($samplerValue !== null) {
          $floatValue = (float) $samplerValue;
          if ($floatValue < 0 || $floatValue > 1) {
            $floatValue = 0.0;
          }
        }

        $tracerConfig->setSampler(new ProbabilisticSampler($floatValue));

        break;
    }

    $this->_tracer = $tracerConfig->initTracer($strName, $strHostPort);

    if ($this->_tracer !== null) {
      $this->_isEnabled = true;
    }
  }

  private function _getTracer() {
    if ($this->_isEnabled === true && $this->_isPaused === false) {
      return $this->_tracer;
    }

    return $this->_noopTracer;
  }

  /**
   * {@inheritdoc}
   */
  public function startActiveSpan($operationName, $options = []) {
    $tracer = $this->_getTracer();

    return $tracer->startActiveSpan($operationName, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function startSpan($operationName, $options = []) {
    $tracer = $this->_getTracer();

    return $tracer->startSpan($operationName, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function inject(SpanContext $spanContext, $format, &$carrier) {
    $tracer = $this->_getTracer();

    $tracer->inject($spanContext, $format, $carrier);
  }

  /**
   * {@inheritdoc}
   */
  public function extract($format, $carrier) {
    $tracer = $this->_getTracer();

    return $tracer->extract($format, $carrier);
  }

  /**
   * {@inheritdoc}
   */
  public function flush() {
    $tracer = $this->_getTracer();

    $arrSpan = $tracer->getSpans();

    if (count($arrSpan) > 0) {
      foreach ($arrSpan as $span) {
        if (count($span->tags) > 0) {
          if (SizeUtil::exceedSizeInBytes($span->tags, SizeUtil::MAX_TAG_SIZE_PER_SPAN_IN_BYTES)) {
            $span->tags = [];

            throw new \Exception('There is one span has tags data exceeds the limit size, maximum size is ' . SizeUtil::MAX_TAG_SIZE_PER_SPAN_IN_BYTES . 'B. Clear tags data automatically.', -1);
          }
        }

        if (count($span->logs) > 0) {
          if (SizeUtil::exceedSizeInBytes($span->logs, SizeUtil::MAX_LOG_SIZE_PER_SPAN_IN_BYTES)) {
            $span->logs = [];

            throw new \Exception('There is one span has logs data exceeds the limit size, maximum size is ' . SizeUtil::MAX_LOG_SIZE_PER_SPAN_IN_BYTES . 'B. Clear logs data automatically.', -1);
          }
        }
      }
    }

    $tracer->flush();
  }

  public function pause() {
    $this->_isPaused = true;
  }

  public function resume() {
    $this->_isPaused = false;
  }

}

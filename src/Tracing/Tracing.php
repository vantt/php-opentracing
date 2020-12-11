<?php

namespace Tracing;

use Jaeger\Config;
use Jaeger\Constants;
use Jaeger\Sampler\ConstSampler;
use Jaeger\Sampler\ProbabilisticSampler;
use OpenTracing\NoopSpan;
use OpenTracing\NoopTracer;
use OpenTracing\SpanContext;
use OpenTracing\Tracer;

class Tracing implements Tracer {

  private static $instances = [];

  private $_tracer;

  private $_isEnabled = false;

  public function __construct(?array $arrConfig) {
    if ($arrConfig === null || count($arrConfig) === 0) {
      $this->_tracer = new NoopTracer();

      return;
    }

    $isEnabled = (bool) $arrConfig['enable'];

    if (
      !$isEnabled
      || !isset($arrConfig['name'])
      || !isset($arrConfig['host_port'])
      || !isset($arrConfig['sampler_type'])
    ) {
      $this->_tracer = new NoopTracer();

      return;
    }

    $this->_isEnabled = $isEnabled;

    $strName = (string) $arrConfig['name'];

    $strHostPort = (string) $arrConfig['host_port'];

    $strSamplerType = (string) $arrConfig['sampler_type'];

    if (
      trim($strName) === ''
      || trim($strHostPort) === ''
      || trim($strSamplerType) === ''
    ) {
      $this->_tracer = new NoopTracer();

      return;
    }

    $strName = trim($strName);

    $strHostPort = trim($strHostPort);

    $strSamplerType = trim($strSamplerType);

    if (
      $strSamplerType !== 'const' && $strSamplerType !== 'probabilistic'
    ) {
      $this->_tracer = new NoopTracer();

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

    if ($this->_tracer === null) {
      $this->_tracer = new NoopTracer();
    }
  }

  /**
   * @param string $strName
   * @param string $arrConfig
   */
  public static function getInstance(string $strName, ?array $arrConfig = null) {
    if (trim($strName) === '') {
      throw new \Exception('Invalid name');
    }

    $strName = trim($strName);

    if (isset(self::$instances[$strName])) {
      return self::$instances[$strName];
    }

    $arrConfig['name'] = $strName;

    self::$instances[$strName] = new Tracing($arrConfig);

    return self::$instances[$strName];
  }

  private function __clone() {
  }

  private function __wakeup() {
  }

  /**
   * {@inheritdoc}
   */
  public function getScopeManager() {
    return $this->_tracer->getScopeManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveSpan() {
    return $this->_tracer->getActiveSpan();
  }

  /**
   * {@inheritdoc}
   */
  public function startActiveSpan($operationName, $options = []) {
    return $this->_tracer->startActiveSpan($operationName, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function startSpan($operationName, $options = []) {
    if ($this->_isEnabled === false) { // fix bug of NoopTracer
      return NoopSpan::create();
    }

    return $this->_tracer->startSpan($operationName, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function inject(SpanContext $spanContext, $format, &$carrier) {
    $this->_tracer->inject($spanContext, $format, $carrier);
  }

  /**
   * {@inheritdoc}
   */
  public function extract($format, $carrier) {
    return $this->_tracer->extract($format, $carrier);
  }

  /**
   * {@inheritdoc}
   */
  public function flush() {
    $this->_tracer->flush();
  }

}

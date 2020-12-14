<?php

namespace Tracing;

use Jaeger\Config;
use Jaeger\Constants;
use Jaeger\Sampler\ConstSampler;
use Jaeger\Sampler\ProbabilisticSampler;
use OpenTracing\SpanContext;
use Tracing\Custom\NoopTracer;
use Tracing\Util\SizeUtil;

class Tracer {

  private static $instances = [];

  private $_noopTracer = null;
  private $_tracer = null;

  private $_isEnabled = false;
  private $_isPaused = false;

  public function __construct(?array $arrConfig) {
    $this->_noopTracer = new NoopTracer();

    if ($arrConfig === null || count($arrConfig) === 0) {
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

  /**
   * @param string $strName
   * @param string $arrConfig
   *
   * @throws Exception
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

    self::$instances[$strName] = new Tracer($arrConfig);

    return self::$instances[$strName];
  }

  private function __clone() {
  }

  private function __wakeup() {
  }

  private function _getTracer() {
    if ($this->_isEnabled === true && $this->_isPaused === false) {
      return $this->_tracer;
    }

    return $this->_noopTracer;
  }

  private function getScopeManager() {
    $tracer = $this->_getTracer();

    return $tracer->getScopeManager();
  }

  private function getActiveSpan() {
    $tracer = $this->_getTracer();

    return $tracer->getActiveSpan();
  }

  /**
   * Starts and returns a new `Span` representing a unit of work.
   *
   * This method differs from `startSpan` because it uses in-process
   * context propagation to keep track of the current active `Span` (if
   * available).
   *
   * Starting a root `Span` with no casual references and a child `Span`
   * in a different function, is possible without passing the parent
   * reference around:
   *
   *  function handleRequest(Request $request, $userId)
   *  {
   *      $rootSpan = $this->tracer->startActiveSpan('request.handler');
   *      $user = $this->repository->getUser($userId);
   *  }
   *
   *  function getUser($userId)
   *  {
   *      // `$childSpan` has `$rootSpan` as parent.
   *      $childSpan = $this->tracer->startActiveSpan('db.query');
   *  }
   *
   * @param string $operationName
   * @param array|StartSpanOptions $options A set of optional parameters:
   *   - Zero or more references to related SpanContexts, including a shorthand for ChildOf and
   *     FollowsFrom reference types if possible.
   *   - An optional explicit start timestamp; if omitted, the current walltime is used by default
   *     The default value should be set by the vendor.
   *   - Zero or more tags
   *   - FinishSpanOnClose option which defaults to true.
   *
   * @return Scope
   */
  public function startActiveSpan($operationName, $options = []) {
    $tracer = $this->_getTracer();

    return $tracer->startActiveSpan($operationName, $options);
  }

  /**
   * Starts and returns a new `Span` representing a unit of work.
   *
   * @param string $operationName
   * @param array|StartSpanOptions $options
   * @return Span
   * @throws InvalidSpanOption for invalid option
   * @throws InvalidReferencesSet for invalid references set
   */
  public function startSpan($operationName, $options = []) {
    $tracer = $this->_getTracer();

    return $tracer->startSpan($operationName, $options);
  }

  /**
   * @param SpanContext $spanContext
   * @param string $format
   * @param mixed $carrier
   *
   * @see Formats
   *
   * @throws UnsupportedFormat when the format is not recognized by the tracer
   * implementation
   */
  public function inject(SpanContext $spanContext, $format, &$carrier) {
    $tracer = $this->_getTracer();

    $tracer->inject($spanContext, $format, $carrier);
  }

  /**
   * @param string $format
   * @param mixed $carrier
   * @return SpanContext|null
   *
   * @see Formats
   *
   * @throws UnsupportedFormat when the format is not recognized by the tracer
   * implementation
   */
  public function extract($format, $carrier) {
    $tracer = $this->_getTracer();

    return $tracer->extract($format, $carrier);
  }

  /**
   * Allow tracer to send span data to be instrumented.
   *
   * This method might not be needed depending on the tracing implementation
   * but one should make sure this method is called after the request is delivered
   * to the client.
   *
   * As an implementor, a good idea would be to use {@see register_shutdown_function}
   * or {@see fastcgi_finish_request} in order to not to delay the end of the request
   * to the client.
   */
  public function flush() {
    $tracer = $this->_getTracer();

    $arrSpan = $tracer->getSpans();

    if (count($arrSpan) > 0) {
      foreach ($arrSpan as $span) {
        if (count($span->tags) > 0) {
          if (SizeUtil::exceedSizeInBytes($span->tags, SizeUtil::MAX_TAG_SIZE_PER_SPAN_IN_BYTES)) {
            $span->tags = [];

            error_log('There is one span has tags data exceeds the limit size, maximum size is ' . SizeUtil::MAX_TAG_SIZE_PER_SPAN_IN_BYTES . 'B. Clear tags data automatically.');
          }
        }

        if (count($span->logs) > 0) {
          if (SizeUtil::exceedSizeInBytes($span->logs, SizeUtil::MAX_LOG_SIZE_PER_SPAN_IN_BYTES)) {
            $span->logs = [];

            error_log('There is one span has logs data exceeds the limit size, maximum size is ' . SizeUtil::MAX_LOG_SIZE_PER_SPAN_IN_BYTES . 'B. Clear logs data automatically.');
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

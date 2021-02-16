<?php

namespace Tracing;

use Exception;
use InvalidArgumentException;
use Jaeger\Config;
use Jaeger\Constants;
use Jaeger\Jaeger;
use Jaeger\Sampler\ConstSampler;
use Jaeger\Sampler\ProbabilisticSampler;
use OpenTracing\Buildable;
use OpenTracing\BuildableInterface;
use OpenTracing\SpanContext;
use OpenTracing\Tracer;
use Tracing\Custom\NoopTracer;

use RuntimeException;

class JaegerTracer implements Tracer, PauseAbleInterface, BuildableInterface {

    use Buildable;

    private $_noopTracer = null;
    private $_tracer     = null;
    private $_isPaused   = false;

    public function __construct(Jaeger $tracer) {
        $this->_noopTracer = new NoopTracer();
        $this->_tracer     = $tracer;
    }

    public function fromConfig(array $arrConfig = []) {
        if (empty($arrConfig['name']) || empty($arrConfig['host_port']) || empty($arrConfig['sampler_type'])) {
            throw new InvalidArgumentException('Missing arguments: [name, host, sampler_type]');
        }

        $strName        = trim((string)$arrConfig['name']);
        $strHostPort    = trim((string)$arrConfig['host_port']);
        $strSamplerType = trim((string)$arrConfig['sampler_type']);

        if ($strSamplerType !== 'const' && $strSamplerType !== 'probabilistic') {
            throw new InvalidArgumentException('Only support sampling type: [const, probabilistic]');
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
                    $isSampled = (bool)$samplerValue;
                }

                $tracerConfig->setSampler(new ConstSampler($isSampled));
                break;

            case 'probabilistic':
                $floatValue = 0.0;
                if ($samplerValue !== null) {
                    $floatValue = (float)$samplerValue;
                    if ($floatValue < 0 || $floatValue > 1) {
                        $floatValue = 0.0;
                    }
                }

                $tracerConfig->setSampler(new ProbabilisticSampler($floatValue));
                break;
        }

        $tracer = $tracerConfig->initTracer($strName, $strHostPort);

        if (!$tracer) {
            throw new RuntimeException('Could not initialize Jaeger');
        }

        return new self($tracer);
    }

    private function _getTracer() {
        if ($this->_isPaused === false) {
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

                        throw new Exception('There is one span has tags data exceeds the limit size, maximum size is ' . SizeUtil::MAX_TAG_SIZE_PER_SPAN_IN_BYTES . 'B. Clear tags data automatically.', -1);
                    }
                }

                if (count($span->logs) > 0) {
                    if (SizeUtil::exceedSizeInBytes($span->logs, SizeUtil::MAX_LOG_SIZE_PER_SPAN_IN_BYTES)) {
                        $span->logs = [];

                        throw new Exception('There is one span has logs data exceeds the limit size, maximum size is ' . SizeUtil::MAX_LOG_SIZE_PER_SPAN_IN_BYTES . 'B. Clear logs data automatically.', -1);
                    }
                }
            }
        }

        $tracer->flush();
    }

    public function getScopeManager() {
        return $this->_getTracer()->getScopeManager();
    }

    public function getActiveSpan() {
        return $this->_getTracer()->getActiveSpan();
    }

    public function getSpans() {
        return $this->_getTracer()->getSpans();
    }

    public function pause() {
        $this->_isPaused = true;
    }

    public function resume() {
        $this->_isPaused = false;
    }
}

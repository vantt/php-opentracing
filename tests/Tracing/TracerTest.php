<?php

namespace Tracing\Tests;

use Jaeger\Jaeger as OriginalJaeger;
use Jaeger\Reporter\RemoteReporter;
use Jaeger\Sampler\ConstSampler;
use Jaeger\ScopeManager;
use Jaeger\Span;
use Jaeger\Transport\TransportUdp;
use OpenTracing\Reference;
use OpenTracing\StartSpanOptions;
use PHPUnit\Framework\TestCase;
use Tracing\JaegerTracer;


/**
 * @covers StartSpanOptions
 */
final class TracerTest extends TestCase {
    const OPERATION_NAME = 'test_operation';

    public function getJaeger() {

        $tranSport    = new TransportUdp();
        $reporter     = new RemoteReporter($tranSport);
        $sampler      = new ConstSampler();
        $scopeManager = new ScopeManager();

        $originJaeger =  new OriginalJaeger('jaeger', $reporter, $sampler, $scopeManager);
        return new JaegerTracer($originJaeger);
    }


    public function testNew() {
        $Jaeger = $this->getJaeger();
        $this->assertInstanceOf(Jaeger::class, $Jaeger);
    }

    public function testStartSpan() {
        $Jaeger = $this->getJaeger();
        $span   = $Jaeger->buildSpan('test')->start();

        $this->assertNotEmpty($span->startTime);
        $this->assertNotEmpty($Jaeger->getSpans());
    }

    public function testStartSpanWithFollowsFromTypeRef() {
        $this->markTestIncomplete('testStartSpanWithFollowsFromTypeRef');

        $jaeger    = $this->getJaeger();
        $rootSpan  = $jaeger->buildSpan('root-a')->start();
        $childSpan = $jaeger->buildSpan('span-a')
                            ->addReference(Reference::FOLLOWS_FROM, $rootSpan)
                            ->start();


        $this->assertSame($childSpan->spanContext->traceIdLow, $rootSpan->spanContext->traceIdLow);
        $this->assertSame(current($childSpan->references)->getContext(), $rootSpan->spanContext);

        $otherRootSpan = $jaeger->buildSpan('root-a')->start();
        $childSpan     = $jaeger->buildSpan('span-b')
                                ->addReference(Reference::FOLLOWS_FROM, $rootSpan)
                                ->addReference(Reference::FOLLOWS_FROM, $otherRootSpan)
                                ->start();

        $this->assertSame($childSpan->spanContext->traceIdLow, $otherRootSpan->spanContext->traceIdLow);
    }


    public function testStartSpanWithChildOfTypeRef() {
        $jaeger        = $this->getJaeger();
        $rootSpan      = $jaeger->buildSpan('root-a')->start();
        $otherRootSpan = $jaeger->buildSpan('root-b')->start();

        $childSpan = $jaeger->buildSpan('span-a')
                            ->addReference(Reference::CHILD_OF, $rootSpan)
                            ->addReference(Reference::CHILD_OF, $otherRootSpan)
                            ->start();

        $this->assertSame($childSpan->spanContext->traceIdLow, $rootSpan->spanContext->traceIdLow);
    }

    public function testStartSpanWithAllRefType() {
        $jaeger        = $this->getJaeger();
        $rootSpan      = $jaeger->buildSpan('root-a')->start();
        $otherRootSpan = $jaeger->buildSpan('root-b')->start();

        $childSpan = $jaeger->buildSpan('span-a')
                            ->addReference(Reference::FOLLOWS_FROM, $rootSpan)
                            ->addReference(Reference::CHILD_OF, $otherRootSpan)
                            ->start();

        $this->assertSame($childSpan->spanContext->traceIdLow, $otherRootSpan->spanContext->traceIdLow);
    }

    public function test__StartSpan__With_AsChildOf() {
        $jaeger        = $this->getJaeger();
        $rootSpan      = $jaeger->buildSpan('root-a')->start();

        $childSpan = $jaeger->buildSpan('span-a')
                            ->asChildOf(Reference::CHILD_OF, $rootSpan)
                            ->start();

        $this->assertSame($childSpan->spanContext->traceIdLow, $rootSpan->spanContext->traceIdLow);
    }


    public function testStartSpanWithCustomStartTime() {
        $jaeger = $this->getJaeger();
        $span   = $jaeger->buildSpan('test')
                         ->withStartTimestamp(1499355363.123456)
                         ->start();

        $this->assertSame(1499355363123456, $span->startTime);
    }

    public function testStartActiveSpan() {
        $Jaeger = $this->getJaeger();
        $Jaeger->buildSpan('test')->startActive();

        $this->assertNotEmpty($Jaeger->getSpans());
    }

    public function testGetActiveSpan() {
        $Jaeger     = $this->getJaeger();
        $scope      = $Jaeger->buildSpan('test')->startActive();
        $activeSpan = $Jaeger->getActiveSpan();

        $this->assertInstanceOf(Span::class, $activeSpan);
        $this->assertEquals($scope->getSpan(), $activeSpan);
    }

    public function testFlush() {
        $Jaeger = $this->getJaeger();
        $Jaeger->buildSpan('test')->start();
        $Jaeger->flush();
        $this->assertEmpty($Jaeger->getSpans());
    }


    public function testNestedSpanBaggage() {
        $tracer = $this->getJaeger();

        $parent = $tracer->buildSpan('parent')->start();
        $parent->addBaggageItem('key', 'value');

        $child = $tracer->buildSpan('child')->asChildOf($parent)->start();

        $this->assertEquals($parent->getBaggageItem('key'), $child->getBaggageItem('key'));
    }

}
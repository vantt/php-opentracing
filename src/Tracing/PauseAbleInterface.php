<?php

namespace Tracing;

use OpenTracing\Tracer;

interface PauseAbleInterface extends Tracer {

  public function pause();

  public function resume();

}


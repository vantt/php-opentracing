<?php

namespace Tracing;

interface PauseAbleInterface {

    public function pause();

    public function resume();

}


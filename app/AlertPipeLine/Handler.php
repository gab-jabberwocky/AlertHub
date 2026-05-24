<?php

namespace App\AlertPipeline;

abstract class Handler
{
    /**
     * Process the payload and context, returning the resulting PipelineState.
     */
    abstract public function process(array &$payload, array &$context): PipelineState;
}
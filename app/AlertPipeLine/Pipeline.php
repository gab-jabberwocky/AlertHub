<?php

namespace App\AlertPipeline;

use App\AlertPipeline\Handlers\NotificationDispatchHandler;

class Pipeline
{
    /** @var Handler[] */
    protected array $handlers = [];

    public function pipe(Handler $handler): self
    {
        $this->handlers[] = $handler;
        return $this;
    }

    public function process(array $payload, array $context): void
    {
        foreach ($this->handlers as $handler) {
            $state = $handler->process($payload, $context);
            
            if ($state === PipelineState::QUIT) {
                break;
            }

            if ($state === PipelineState::SKIP_TO_DISPATCH) {
                // Skip to NotificationDispatchHandler if found in the chain
                foreach ($this->handlers as $dispatchHandler) {
                    if ($dispatchHandler instanceof NotificationDispatchHandler) {
                        $dispatchHandler->process($payload, $context);
                        break;
                    }
                }
                break;
            }
        }
    }
}
<?php

namespace App\AlertPipeline;

enum PipelineState
{
    case CONTINUE;
    case QUIT;
    case SKIP_TO_DISPATCH;
}
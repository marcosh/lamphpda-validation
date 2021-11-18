<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use Eris\Listener\MinimumEvaluations;
use Eris\TestTrait;

final class ValidationTest
{
    use TestTrait;

    public function __construct()
    {
        $this->seedingRandomNumberGeneration();
        $this->listeners = array_filter(
            $this->listeners,
            function ($listener) {
                return !($listener instanceof MinimumEvaluations);
            }
        );
        $this->withRand('rand');
    }
}

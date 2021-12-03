<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use Kahlan\Matcher;
use Marcosh\LamPHPda\Either;

final class ToBeEither
{
    public static function match(Either $actual, Either $expected)
    {
        return $actual->eval(
            fn($actualLeft) => $expected->eval(
                fn($expectedLeft) => $actualLeft === $expectedLeft,
                fn($_)            => false
            ),
            fn($actualRight) => $expected->eval(
                fn($_)             => false,
                fn($expectedRight) => $actualRight === $expectedRight
            )
        );
    }

    public static function description()
    {
        return "strict check on the content of Either";
    }
}

<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\HK\HK2;
use Marcosh\LamPHPda\Typeclass\Profunctor;
use Marcosh\LamPHPda\Validation\Brand\ValidationBrand2;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @implements Profunctor<ValidationBrand2>
 *
 * @psalm-immutable
 */
final class ValidationProfunctor implements Profunctor
{
    /**
     * @template A
     * @template B
     * @template C
     * @template D
     * @template E
     * @param pure-callable(A): B $f
     * @param pure-callable(C): D $g
     * @param HK2<ValidationBrand2<E>, B, C> $a
     * @return Validation<A, E, D>
     */
    public function diMap(callable $f, callable $g, HK2 $a): Validation
    {
        $validation = Validation::fromBrand2($a);

        return new Validation(
            /**
             * @param A $a
             * @return Either<E, D>
             */
            fn($a) => $validation->validate($f($a))->map($g)
        );
    }
}

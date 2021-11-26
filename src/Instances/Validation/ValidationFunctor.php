<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\HK\HK1;
use Marcosh\LamPHPda\Typeclass\Functor;
use Marcosh\LamPHPda\Validation\Brand\ValidationBrand;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @implements Functor<ValidationBrand>
 *
 * @psalm-immutable
 */
final class ValidationFunctor implements Functor
{
    /**
     * @template A
     * @template B
     * @template C
     * @template E
     * @param pure-callable(A): B $f
     * @param HK1<ValidationBrand<C, E>, A> $a
     * @return Validation<C, E, B>
     *
     * @psalm-pure
     */
    public function map(callable $f, HK1 $a): Validation
    {
        $aValidation = Validation::fromBrand($a);

        return new Validation(
            /**
             * @param C $c
             * @return Either<E, B>
             */
            fn($c) => $aValidation->validate($c)->map($f)
        );
    }
}

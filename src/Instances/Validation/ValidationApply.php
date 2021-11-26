<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\HK\HK1;
use Marcosh\LamPHPda\Instances\Either\ValidationApplicative as EitherValidationApplicative;
use Marcosh\LamPHPda\Typeclass\Apply;
use Marcosh\LamPHPda\Typeclass\Semigroup;
use Marcosh\LamPHPda\Validation\Brand\ValidationBrand;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @template C
 * @template E
 * @implements Apply<ValidationBrand<C, E>>
 *
 * @psalm-immutable
 */
final class ValidationApply implements Apply
{
    /** @var Semigroup<E> */
    private Semigroup $eSemigroup;

    /**
     * @param Semigroup<E> $eSemigroup
     */
    public function __construct(Semigroup $eSemigroup)
    {
        $this->eSemigroup = $eSemigroup;
    }

    /**
     * @template A
     * @template B
     * @param pure-callable(A): B $f
     * @param HK1<ValidationBrand<C, E>, A> $a
     * @return Validation<C, E, B>
     *
     * @psalm-pure
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function map(callable $f, HK1 $a): Validation
    {
        return (new ValidationFunctor())->map($f, $a);
    }

    /**
     * @template A
     * @template B
     * @param HK1<ValidationBrand<C, E>, callable(A): B> $f
     * @param HK1<ValidationBrand<C, E>, A> $a
     * @return Validation<C, E, B>
     *
     * @psalm-pure
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function apply(HK1 $f, HK1 $a): Validation
    {
        $fValidation = Validation::fromBrand($f);
        $aValidation = Validation::fromBrand($a);

        return new Validation(
            /**
             * @param C $c
             * @return Either<E, B>
             *
             * @psalm-suppress ArgumentTypeCoercion
             */
            fn($c) => (new EitherValidationApplicative($this->eSemigroup))->apply(
                $fValidation->validate($c),
                $aValidation->validate($c)
            )
        );
    }
}

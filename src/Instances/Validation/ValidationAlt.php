<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\HK\HK1;
use Marcosh\LamPHPda\Instances\Either\EitherAlt;
use Marcosh\LamPHPda\Typeclass\Alt;
use Marcosh\LamPHPda\Typeclass\Semigroup;
use Marcosh\LamPHPda\Validation\Brand\ValidationBrand;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @template C
 * @template E
 * @implements Alt<ValidationBrand<C, E>>
 *
 * @psalm-immutable
 */
final class ValidationAlt implements Alt
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
     * @param callable(A): B $f
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
     * @param HK1<ValidationBrand<C, E>, A> $a
     * @param HK1<ValidationBrand<C, E>, A> $b
     * @return Validation<C, E, A>
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function alt($a, $b): Validation
    {
        $aValidation = Validation::fromBrand($a);
        $bValidation = Validation::fromBrand($b);

        return new Validation(
            /**
             * @param C $c
             * @return Either<E, A>
             *
             * @psalm-suppress ArgumentTypeCoercion
             */
            fn($c) => (new EitherAlt($this->eSemigroup))->alt(
                $aValidation->validate($c),
                $bValidation->validate($c)
            )
        );
    }
}

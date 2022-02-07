<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\HK\HK1;
use Marcosh\LamPHPda\Instances\Either\EitherAlternative;
use Marcosh\LamPHPda\Instances\Either\EitherApplicative;
use Marcosh\LamPHPda\Typeclass\Alternative;
use Marcosh\LamPHPda\Typeclass\Monoid;
use Marcosh\LamPHPda\Validation\Brand\ValidationBrand;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @template C
 * @template E
 * @implements Alternative<ValidationBrand<C, E>>
 *
 * @psalm-immutable
 */
final class ValidationAlternative implements Alternative
{
    /** @var Monoid<E> */
    private Monoid $eMonoid;

    /**
     * @param Monoid<E> $eMonoid
     */
    public function __construct(Monoid $eMonoid)
    {
        $this->eMonoid = $eMonoid;
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
     * @template B
     * @param HK1<ValidationBrand<C, E>, callable(A): B> $f
     * @param HK1<ValidationBrand<C, E>, A> $a
     * @return Validation<C, E, B>
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function apply(HK1 $f, HK1 $a): Validation
    {
        return (new ValidationApply($this->eMonoid))->apply($f, $a);
    }

    /**
     * @template A
     * @param A $a
     * @return Validation<C, E, A>
     *
     * @psalm-pure
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function pure($a): Validation
    {
        return new Validation(
        /**
         * @param C $_
         * @return Either<E, A>
         */
            fn($_) => (new EitherApplicative())->pure($a)
        );
    }

    /**
     * @template A
     * @return Validation<C, E, A>
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     */
    public function empty(): Validation
    {
        return Validation::invalid($this->eMonoid->mempty());
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
            fn($c) => (new EitherAlternative($this->eMonoid))->alt(
                $aValidation->validate($c),
                $bValidation->validate($c)
            )
        );
    }
}

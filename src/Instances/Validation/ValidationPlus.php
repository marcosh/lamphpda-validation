<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\HK\HK1;
use Marcosh\LamPHPda\Typeclass\Monoid;
use Marcosh\LamPHPda\Typeclass\Plus;
use Marcosh\LamPHPda\Validation\Brand\ValidationBrand;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @template C
 * @template E
 * @implements Plus<ValidationBrand<C, E>>
 *
 * @psalm-immutable
 */
class ValidationPlus implements Plus
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
        return (new ValidationAlt($this->eMonoid))->alt($a, $b);
    }
}

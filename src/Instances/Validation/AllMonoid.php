<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Typeclass\Monoid;
use Marcosh\LamPHPda\Typeclass\Semigroup;
use Marcosh\LamPHPda\Validation\Instances\Either\JoinEitherSemigroup;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @template A
 * @template E
 * @template B
 *
 * @implements Monoid<Validation<A, E, B>>
 *
 * @psalm-immutable
 */
final class AllMonoid implements Monoid
{
    /** @var Semigroup<E> */
    private $eSemigroup;

    /**
     * @param Semigroup<E> $eSemigroup
     */
    public function __construct(Semigroup $eSemigroup)
    {
        $this->eSemigroup = $eSemigroup;
    }

    /**
     * @return Validation<A, E, B>
     */
    public function mempty()
    {
        return Validation::valid();
    }

    /**
     * @param Validation<A, E, B> $a
     * @param Validation<A, E, B> $b
     * @return Validation<A, E, B>
     */
    public function append($a, $b)
    {
        return (new ValidationSemigroup(new JoinEitherSemigroup($this->eSemigroup)))->append($a, $b);
    }
}

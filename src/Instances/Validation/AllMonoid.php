<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Instances\Either\JoinEitherSemigroup;
use Marcosh\LamPHPda\Typeclass\Monoid;
use Marcosh\LamPHPda\Typeclass\Semigroup;
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

    /** @var Semigroup<B> */
    private $bSemigroup;

    /**
     * @param Semigroup<E> $eSemigroup
     * @param Semigroup<B> $bSemigroup
     */
    public function __construct(Semigroup $eSemigroup, Semigroup $bSemigroup)
    {
        $this->eSemigroup = $eSemigroup;
        $this->bSemigroup = $bSemigroup;
    }

    /**
     * @return Validation<A, E, B>
     */
    public function mempty(): Validation
    {
        return Validation::valid();
    }

    /**
     * @param Validation<A, E, B> $a
     * @param Validation<A, E, B> $b
     * @return Validation<A, E, B>
     */
    public function append($a, $b): Validation
    {
        return (new ValidationSemigroup(new JoinEitherSemigroup($this->eSemigroup, $this->bSemigroup)))->append($a, $b);
    }
}

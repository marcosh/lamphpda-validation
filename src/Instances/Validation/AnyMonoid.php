<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Instances\Either\MeetEitherSemigroup;
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
final class AnyMonoid implements Monoid
{
    /** @var Monoid<E> */
    private Monoid $eMonoid;

    /** @var Semigroup<B> */
    private Semigroup $bSemigroup;

    /**
     * @param Monoid<E> $eMonoid
     */
    public function __construct(Monoid $eMonoid, Semigroup $bSemigroup)
    {
        $this->eMonoid = $eMonoid;
        $this->bSemigroup = $bSemigroup;
    }

    /**
     * @return Validation<A, E, B>
     */
    public function mempty()
    {
        return Validation::invalid($this->eMonoid->mempty());
    }

    /**
     * @param Validation<A, E, B> $a
     * @param Validation<A, E, B> $b
     * @return Validation<A, E, B>
     */
    public function append($a, $b)
    {
        return (new ValidationSemigroup(new MeetEitherSemigroup($this->eMonoid, $this->bSemigroup)))->append($a, $b);
    }
}

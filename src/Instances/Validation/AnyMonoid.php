<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Typeclass\Monoid;
use Marcosh\LamPHPda\Typeclass\Semigroup;
use Marcosh\LamPHPda\Validation\Instances\Either\JoinEitherSemigroup;
use Marcosh\LamPHPda\Validation\Instances\Either\MeetEitherSemigroup;
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
    private $eMonoid;

    /**
     * @param Monoid<E> $eMonoid
     */
    public function __construct(Monoid $eMonoid)
    {
        $this->eMonoid = $eMonoid;
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
        return (new ValidationSemigroup(new MeetEitherSemigroup($this->eMonoid)))->append($a, $b);
    }
}

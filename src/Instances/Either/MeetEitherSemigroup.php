<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Either;

use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\Typeclass\Semigroup;

/**
 * joins the errors with an E semigroup
 * if one succeeds, then it all succeeds
 * if both validations succeed, we use the result of the first
 *
 * @template E
 * @template B
 *
 * @implements Semigroup<Either<E, B>>
 *
 * @psalm-immutable
 */
final class MeetEitherSemigroup implements Semigroup
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
     * @param Either<E, B> $a
     * @param Either<E, B> $b
     * @return Either<E, B>
     *
     * @psalm-pure
     */
    public function append($a, $b): Either
    {
        return $a->eval(
            /**
             * @param E $ea
             * @return Either<E, B>
             */
            fn($ea) => $b->eval(
                /**
                 * @param E $eb
                 * @return Either<E, B>
                 */
                fn($eb) => Either::left($this->eSemigroup->append($ea, $eb)),
                /**
                 * @param B $vb
                 * @return Either<E, B>
                 */
                fn($vb) => Either::right($vb)
            ),
            /**
             * @param B $va
             * @return Either<E, B>
             */
            fn($va) => Either::right($va)
        );
    }
}

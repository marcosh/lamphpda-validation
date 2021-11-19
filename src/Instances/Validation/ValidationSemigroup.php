<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation\Instances\Validation;

use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\Typeclass\Semigroup;
use Marcosh\LamPHPda\Validation\Validation;

/**
 * @template A
 * @template E
 * @template B
 * @implements Semigroup<Validation<A, E, B>>
 *
 * @psalm-immutable
 */
final class ValidationSemigroup implements Semigroup
{
    /** @var Semigroup<Either<E, B>> */
    private $eitherSemigroup;

    /**
     * @param Semigroup<Either<E, B>> $eitherSemigroup
     */
    public function __construct(Semigroup $eitherSemigroup)
    {
        $this->eitherSemigroup = $eitherSemigroup;
    }

    /**
     * @param Validation<A, E, B> $a
     * @param Validation<A, E, B> $b
     * @return Validation<A, E, B>
     *
     * @psalm-pure
     */
    public function append($a, $b): Validation
    {
        return new Validation(
            /**
             * @param A $c
             * @return Either<E, B>
             */
            function ($c) use ($a, $b) {
                return $this->eitherSemigroup->append(
                    $a->validate($c),
                    $b->validate($c)
                );
            }
        );
    }
}

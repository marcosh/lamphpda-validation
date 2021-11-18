<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\Validation;

use Marcosh\LamPHPda\Either;

/**
 * a validation is nothing else that a function from A to Either<E, B>
 *
 * @template A raw input to the validation
 * @template E potential validation error
 * @template B parsed validation result
 *
 * @psalm-immutable
 */
final class Validation
{
    /** @var callable(A): Either<E, B> */
    private $validation;

    /**
     * @param callable(A): Either<E, B> $validation
     */
    private function __construct(callable $validation)
    {
        $this->validation = $validation;
    }

    /**
     * this is the only thing you can do with a validation: pass to it some data and get back either an error or some
     * valid data
     *
     * @param A $a
     * @return Either<E, B>
     *
     * @psalm-mutation-free
     */
    public function validate($a): Either
    {
        return ($this->validation)($a);
    }

    /**
     * @template C
     * @template F
     * @return Validation<C, F, C>
     */
    public static function valid(): self
    {
        return new self(
            /**
             * @param C $a
             */
            fn($a) => Either::right($a)
        );
    }

    /**
     * @template C
     * @template F
     * @template D
     * @param F $e
     * @return Validation<C, F, D>
     */
    public static function invalid($e): self
    {
        return new self(
            /**
             * @param C $_
             */
            fn($_) => Either::left($e)
        );
    }

    // BASIC VALIDATORS

    /**
     * @template C
     * @template F
     * @param F $e
     * @return Validation<C, F, array>
     */
    public static function isArray($e): self
    {
        return new self(
            /**
             * @param C $a
             */
            function ($a) use ($e) {
                if (!is_array($a)) {
                    /** @var Either<F, array> */
                    return Either::left($e);
                }

                /** @var Either<F, array> */
                return Either::right($a);
            }
        );
    }
}

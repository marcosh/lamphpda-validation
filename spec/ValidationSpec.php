<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use Eris\Generator\IntegerGenerator;
use Eris\Generator\SequenceGenerator;
use Eris\Generator\StringGenerator;
use Eris\Generator\SuchThatGenerator;
use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\Instances\String\ConcatenationMonoid;
use Marcosh\LamPHPda\Validation\Validation as V;

$test = new ValidationTest();

describe('Validation', function () use ($test) {
    describe('valid', function () use ($test) {
        it('always succeeds', function () use ($test) {
            $test->forAll(
                new IntegerGenerator()
            )->then(
                function (int $i) {
                    expect(V::valid()->validate($i))->toEqual(Either::right($i));
                }
            );
        });
    });

    describe('invalid', function () use ($test) {
        it('always fails', function () use ($test) {
            $test->forAll(
                new IntegerGenerator()
            )->then(
                function (int $i) {
                    expect(V::invalid('nope')->validate($i))->toEqual(Either::left('nope'));
                }
            );
        });
    });

    describe('isArray', function () use ($test) {
        it('always succeeds for arrays', function () use ($test) {
            $test->forAll(
                new SequenceGenerator(new IntegerGenerator())
            )->then(
                function (array $a) {
                    expect(V::isArray('nope')->validate($a))->toEqual(Either::right($a));
                }
            );
        });

        it('always fails for integers', function () use ($test) {
            $test->forAll(
                new IntegerGenerator()
            )->then(
                function (int $i) {
                    expect(V::isArray('nope')->validate($i))->toEqual(Either::left('nope'));
                }
            );
        });
    });

    describe('isArray', function () use ($test) {
        it('always succeeds for strings', function () use ($test) {
            $test->forAll(
                new StringGenerator()
            )->then(
                function (string $a) {
                    expect(V::isString('nope')->validate($a))->toEqual(Either::right($a));
                }
            );
        });

        it('always fails for integers', function () use ($test) {
            $test->forAll(
                new IntegerGenerator()
            )->then(
                function (int $i) {
                    expect(V::isString('nope')->validate($i))->toEqual(Either::left('nope'));
                }
            );
        });
    });

    describe('all', function () use ($test) {
        $all = V::all(
            new ConcatenationMonoid(),
            [
                V::satisfies(fn (int $i) => $i % 2 === 0, 'not multiple of 2'),
                V::satisfies(fn (int $i) => $i % 3 === 0, 'not multiple of 3')
            ]
        );

        it('succeeds if every validator succeeds', function () use ($test, $all) {
            $test->forAll(
                new IntegerGenerator()
            )->then(
                function (int $i) use ($all) {
                   expect($all->validate($i * 6))->toEqual(Either::right($i * 6));
                }
            );
        });

        it('fails if one of the validators fails', function () use ($test, $all) {
            $test->forAll(
                new SuchThatGenerator(
                    function (int $i) {
                        return $i % 3 !== 0;
                    },
                    new IntegerGenerator()
                )
            )->then(
                function (int $i) use ($all) {
                    expect($all->validate($i * 2))->toEqual(Either::left('not multiple of 3'));
                }
            );
        });

        it('fails if all validator fails combining the errors', function () use ($test, $all) {
            $test->forAll(
                new SuchThatGenerator(
                    function (int $i) {
                        return $i % 3 !== 0 && $i % 2 !== 0;
                    },
                    new IntegerGenerator()
                )
            )->then(
                function (int $i) use ($all) {
                    expect($all->validate($i))->toEqual(Either::left('not multiple of 3not multiple of 2'));
                }
            );
        });
    });

    describe('any', function () use ($test) {
        $any = V::any(
            new ConcatenationMonoid(),
            [
                V::satisfies(fn (int $i) => $i % 2 === 0, 'not multiple of 2'),
                V::satisfies(fn (int $i) => $i % 3 === 0, 'not multiple of 3')
            ]
        );

        it('succeeds if every validator succeeds', function () use ($test, $any) {
            $test->forAll(
                new IntegerGenerator()
            )->then(
                function (int $i) use ($any) {
                    expect($any->validate($i * 6))->toEqual(Either::right($i * 6));
                }
            );
        });

        it('succeeds if one of the validators succeeds', function () use ($test, $any) {
            $test->forAll(
                new SuchThatGenerator(
                    function (int $i) {
                        return $i % 3 !== 0;
                    },
                    new IntegerGenerator()
                )
            )->then(
                function (int $i) use ($any) {
                    expect($any->validate($i * 2))->toEqual(Either::right($i * 2));
                }
            );
        });

        it('fails if any validator fails combining the errors', function () use ($test, $any) {
            $test->forAll(
                new SuchThatGenerator(
                    function (int $i) {
                        return $i % 3 !== 0 && $i % 2 !== 0;
                    },
                    new IntegerGenerator()
                )
            )->then(
                function (int $i) use ($any) {
                    expect($any->validate($i))->toEqual(Either::left('not multiple of 3not multiple of 2'));
                }
            );
        });
    });
});

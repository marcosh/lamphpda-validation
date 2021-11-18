<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use Eris\Generator\IntegerGenerator;
use Eris\Generator\SequenceGenerator;
use Eris\Generator\StringGenerator;
use Marcosh\LamPHPda\Either;
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
});

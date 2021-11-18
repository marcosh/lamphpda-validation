<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use Eris\Generator\IntegerGenerator;
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
});

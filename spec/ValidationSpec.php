<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use Eris\Generator\IntegerGenerator;
use Eris\Generator\SequenceGenerator;
use Eris\Generator\StringGenerator;
use Eris\Generator\SuchThatGenerator;
use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\Instances\String\ConcatenationMonoid;
use Marcosh\LamPHPda\Optics\Lens;
use Marcosh\LamPHPda\Validation\Validation as V;

$test = new ValidationTest();

describe('Validation', function () use ($test) {

    describe('Trivial combinators', function () use ($test) {

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

    describe('Basic combinators', function () use ($test) {

        describe('hasKey', function () use ($test) {
            it('succeeds if the array has the key', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        $a[42] = 42;

                        expect(V::hasKey(42, 'nope')->validate($a))->toEqual(Either::right($a));
                    }
                );
            });

            it('fails if the array does not have the key', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        unset($a[3]);

                        expect(V::hasKey(3, 'nope')->validate($a))->toEqual(Either::left('nope'));
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

        describe('isString', function () use ($test) {
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

        describe('notEmptyArray', function () use ($test) {
            it('always succeeds for non-empty arrays', function () use ($test) {
                $test->forAll(
                    new SuchThatGenerator(
                        fn(array $s) => !empty($s),
                        new SequenceGenerator(new IntegerGenerator())
                    )
                )->then(
                    function (array $s) {
                        expect(V::nonEmptyArray('nope')->validate(($s)))->toEqual(Either::right($s));
                    }
                );
            });

            it('fails for the empty array', function () {
                expect(V::nonEmptyArray('nope')->validate(([])))->toEqual(Either::left('nope'));
            });
        });

        describe('notEmptyString', function () use ($test) {
            it('always succeeds for non-empty strings', function () use ($test) {
                $test->forAll(
                    new SuchThatGenerator(
                        fn(string $s) => $s !== '',
                        new StringGenerator()
                    )
                )->then(
                    function (string $s) {
                        expect(V::nonEmptyString('nope')->validate(($s)))->toEqual(Either::right($s));
                    }
                );
            });

            it('fails for the empty string', function () {
                expect(V::nonEmptyString('nope')->validate(('')))->toEqual(Either::left('nope'));
            });
        });
    });

    describe('Combinators', function () use ($test) {

        describe('all', function () use ($test) {
            $all = V::all(
                new ConcatenationMonoid(),
                [
                    V::satisfies(fn(int $i) => $i % 2 === 0, 'not multiple of 2'),
                    V::satisfies(fn(int $i) => $i % 3 === 0, 'not multiple of 3')
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
                    V::satisfies(fn(int $i) => $i % 2 === 0, 'not multiple of 2'),
                    V::satisfies(fn(int $i) => $i % 3 === 0, 'not multiple of 3')
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

        describe('focus', function () use ($test) {
            /** @var Lens<array{foo: string}, array{foo:int}, string, int> $lens */
            $lens = Lens::lens(
                /**
                 * @param array{foo: string} $a
                 * @return string
                 */
                fn (array $a) => $a['foo'],
                /**
                 * @param array{foo: string} $a
                 * @return array{foo: int}
                 */
                function (array $a, int $b): array {
                    $a['foo'] = $b;

                    return $a;
                }
            );

            it('fails if the validation on the focus fails', function () use ($lens) {
                $validation = V::invalid('nope');

                expect(V::focus($lens, $validation)->validate(['foo' => 'a string']))
                    ->toEqual(Either::left('nope'));
            });

            it('succeeds if the validation on the focus succeeds', function () use ($lens) {
                $validation = new V(fn(string $s) => Either::right(strlen($s)));

                expect(V::focus($lens, $validation)->validate(['foo' => 'a string']))
                    ->toEqual(Either::right(['foo' => 8]));
            });
        });
    });
});

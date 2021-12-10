<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use DateTime;
use DateTimeImmutable;
use Eris\Generator\AssociativeArrayGenerator;
use Eris\Generator\BooleanGenerator;
use Eris\Generator\DateGenerator;
use Eris\Generator\FloatGenerator;
use Eris\Generator\IntegerGenerator;
use Eris\Generator\SequenceGenerator;
use Eris\Generator\StringGenerator;
use Eris\Generator\SuchThatGenerator;
use Exception;
use Kahlan\Matcher;
use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\Instances\FirstSemigroup;
use Marcosh\LamPHPda\Instances\ListL\ConcatenationMonoid;
use Marcosh\LamPHPda\Instances\String\ConcatenationMonoid as StringConcatenationMonoid;
use Marcosh\LamPHPda\Optics\Lens;
use Marcosh\LamPHPda\Validation\Validation as V;

Matcher::register('toBeEither', ToBeEither::class);

$test = new ValidationTest();

describe('Validation', function () use ($test) {

    describe('Trivial validators', function () use ($test) {

        describe('valid', function () use ($test) {
            it('always succeeds', function () use ($test) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) {
                        expect(V::valid()->validate($i))->toBeEither(Either::right($i));
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
                        expect(V::invalid('nope')->validate($i))->toBeEither(Either::left('nope'));
                    }
                );
            });
        });
    });

    describe('Then', function () {
        it('fails if the first validation fails', function () {
            $validation1 = V::invalid('nope');
            $validation2 = V::valid();

            expect($validation1->then($validation2)->validate(42))->toBeEither(Either::left('nope'));
        });

        it('fails if the second validation fails', function () {
            $validation1 = V::valid();
            $validation2 = V::invalid('nope');

            expect($validation1->then($validation2)->validate(42))->toBeEither(Either::left('nope'));
        });

        it('succeeds if both validation succeed', function () {
            $validation1 = V::valid();
            $validation2 = V::valid();

            expect($validation1->then($validation2)->validate(42))->toBeEither(Either::right(42));
        });
    });

    describe('Or', function () {
        it('fails if both validations fail combining the errors', function () {
            $validation1 = V::invalid('nope');
            $validation2 = V::invalid('epon');

            expect($validation1->or(new StringConcatenationMonoid(), $validation2)->validate(42))
               ->toBeEither(Either::left('nopeepon'));
        });

        it('succeeds if the first validation succeeds', function () {
            $validation1 = V::valid();
            $validation2 = V::invalid('nope');

            expect($validation1->or(new StringConcatenationMonoid(), $validation2)->validate(42))
                ->toBeEither(Either::right(42));
        });

        it('succeeds if the second validation succeeds', function () {
            $validation1 = V::invalid('nope');
            $validation2 = V::valid();

            expect($validation1->or(new StringConcatenationMonoid(), $validation2)->validate(42))
                ->toBeEither(Either::right(42));
        });

        it('succeeds if both validations succeed', function () {
            $validation1 = V::valid();
            $validation2 = V::valid();

            expect($validation1->or(new StringConcatenationMonoid(), $validation2)->validate(42))
                ->toBeEither(Either::right(42));
        });
    });

    describe('Basic validators', function () use ($test) {

        describe('hasKey', function () use ($test) {
            it('succeeds if the array has the key', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        $a[42] = 42;

                        expect(V::hasKey(42, 'nope')->validate($a))->toBeEither(Either::right($a));
                    }
                );
            });

            it('fails if the array does not have the key', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        unset($a[3]);

                        expect(V::hasKey(3, 'nope')->validate($a))->toBeEither(Either::left('nope'));
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
                        expect(V::isArray('nope')->validate($a))->toBeEither(Either::right($a));
                    }
                );
            });

            it('always fails for integers', function () use ($test) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) {
                        expect(V::isArray('nope')->validate($i))->toBeEither(Either::left('nope'));
                    }
                );
            });
        });

        describe('isBool', function () use ($test) {
            it('always succeeds for booleans', function () use ($test) {
                $test->forAll(
                    new BooleanGenerator()
                )->then(
                    function (bool $b) {
                        expect(V::isBool('nope')->validate($b))->toBeEither(Either::right($b));
                    }
                );
            });

            it('always fails for integers', function () use ($test) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) {
                        expect(V::isBool('nope')->validate($i))->toBeEither(Either::left('nope'));
                    }
                );
            });
        });

        describe('isDate', function () use ($test) {
            it('always succeeds for dates', function () use ($test) {
                $test->forAll(
                    new DateGenerator(
                        new DateTime("@0"),
                        new DateTime("@" . ((2 ** 31) - 1))
                    )
                )->then(
                    function (DateTime $date) {
                        expect(V::isDate(fn ($e) => $e)->validate($date->format('Y:m:d H:i:s')))
                            ->toEqual(Either::right(DateTimeImmutable::createFromMutable($date)));
                    }
                );
            });

            it('always fails for invalid dates', function () use ($test) {
                $test->forAll(
                    new SuchThatGenerator(
                        function (string $s) {
                            try {
                                new DateTimeImmutable($s);

                                return false;
                            } catch (Exception $e) {
                                return true;
                            }
                        },
                        new StringGenerator()
                    )
                )->then(
                    function (string $i) {
                        expect(V::isDate(fn ($_) => 'nope')->validate($i))->toBeEither(Either::left('nope'));
                    }
                );
            });
        });

        describe('isFloat', function () use ($test) {
            it('always succeeds for floats', function () use ($test) {
                $test->forAll(
                    new FloatGenerator()
                )->then(
                    function (float $i) {
                        expect(V::isFloat('nope')->validate($i))->toBeEither(Either::right($i));
                    }
                );
            });

            it('always fails for strings', function () use ($test) {
                $test->forAll(
                    new StringGenerator()
                )->then(
                    function (string $s) {
                        expect(V::isFloat('nope')->validate($s))->toBeEither(Either::left('nope'));
                    }
                );
            });
        });

        describe('isInteger', function () use ($test) {
            it('always succeeds for integers', function () use ($test) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) {
                        expect(V::isInteger('nope')->validate($i))->toBeEither(Either::right($i));
                    }
                );
            });

            it('always fails for strings', function () use ($test) {
                $test->forAll(
                    new StringGenerator()
                )->then(
                    function (string $s) {
                        expect(V::isInteger('nope')->validate($s))->toBeEither(Either::left('nope'));
                    }
                );
            });
        });

        describe('isList', function () use ($test) {
            it('always succeeds for lists', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        expect(V::isList('nope')->validate($a))->toBeEither(Either::right($a));
                    }
                );
            });

            it('always fails for arrays with non integer keys', function () use ($test) {
                $test->forAll(
                    new SuchThatGenerator(
                        fn(string $s) => !is_numeric($s),
                        new StringGenerator()
                    ),
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (string $s, array $a) {
                        $a[$s] = 0;

                        expect(V::isList('nope')->validate($a))->toBeEither(Either::left('nope'));
                    }
                );
            });

            it('always fails for arrays with non consecutive keys', function () use ($test) {
                $test->forAll(
                    new SuchThatGenerator(
                        fn (array $a) => !empty($a),
                        new SequenceGenerator(new IntegerGenerator())
                    )
                )->then(
                    function (array $a) {
                        $maxKey = max(array_keys($a));
                        $a[$maxKey + 2] = 0;

                        expect(V::isList('nope')->validate($a))->toBeEither(Either::left('nope'));
                    }
                );
            });
        });

        describe('isNull', function () use ($test) {
            it('always succeeds for null', function () {
                expect(V::isNull('nope')->validate(null))->toBeEither(Either::right(null));
            });

            it('always fails for integers', function () use ($test) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) {
                        expect(V::isNull('nope')->validate($i))->toBeEither(Either::left('nope'));
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
                        expect(V::isString('nope')->validate($a))->toBeEither(Either::right($a));
                    }
                );
            });

            it('always fails for integers', function () use ($test) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) {
                        expect(V::isString('nope')->validate($i))->toBeEither(Either::left('nope'));
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
                        expect(V::nonEmptyArray('nope')->validate(($s)))->toBeEither(Either::right($s));
                    }
                );
            });

            it('fails for the empty array', function () {
                expect(V::nonEmptyArray('nope')->validate(([])))->toBeEither(Either::left('nope'));
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
                        expect(V::nonEmptyString('nope')->validate(($s)))->toBeEither(Either::right($s));
                    }
                );
            });

            it('fails for the empty string', function () {
                expect(V::nonEmptyString('nope')->validate(('')))->toBeEither(Either::left('nope'));
            });
        });
    });

    describe('Combinators', function () use ($test) {

        describe('all', function () use ($test) {
            $all = V::all(
                new ConcatenationMonoid(),
                new FirstSemigroup(),
                [
                    V::satisfies(fn(int $i) => $i % 2 === 0, ['not multiple of 2']),
                    V::satisfies(fn(int $i) => $i % 3 === 0, ['not multiple of 3'])
                ]
            );

            it('succeeds if every validator succeeds', function () use ($test, $all) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) use ($all) {
                        expect($all->validate($i * 6))->toBeEither(Either::right($i * 6));
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
                        expect($all->validate($i * 2))->toBeEither(Either::left(['not multiple of 3']));
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
                        expect($all->validate($i))->toBeEither(Either::left(['not multiple of 2', 'not multiple of 3']));
                    }
                );
            });
        });

        describe('any', function () use ($test) {
            $any = V::any(
                new ConcatenationMonoid(),
                new FirstSemigroup(),
                [
                    V::satisfies(fn(int $i) => $i % 2 === 0, ['not multiple of 2']),
                    V::satisfies(fn(int $i) => $i % 3 === 0, ['not multiple of 3'])
                ]
            );

            it('succeeds if every validator succeeds', function () use ($test, $any) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(
                    function (int $i) use ($any) {
                        expect($any->validate($i * 6))->toBeEither(Either::right($i * 6));
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
                        expect($any->validate($i * 2))->toBeEither(Either::right($i * 2));
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
                        expect($any->validate($i))->toBeEither(Either::left(['not multiple of 2', 'not multiple of 3']));
                    }
                );
            });
        });

        describe('anyElement', function () use ($test) {
            it('fails it the validation fails for every element', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        $anyElement = V::anyElement(V::invalid('nope'), 'nope nope');

                        expect($anyElement->validate($a))->toBeEither(Either::left('nope nope'));
                    }
                );
            });

            it('succeeds it the validation succeeds for at least one element', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        $anyElement = V::anyElement(V::satisfies(fn($x) => $x === 42, 'nope'), 'nope nope');

                        $a[] = 42;

                        expect($anyElement->validate($a))->toBeEither(Either::right($a));
                    }
                );
            });
        });

        describe('associativeArray', function () use ($test) {
            $validation = V::associativeArray(
                [
                    'int' => V::isInteger(['int is not an integer']),
                    'string' => V::isString(['string is not a string'])
                ],
                ['not an array'],
                new ConcatenationMonoid(),
                fn($key) => [sprintf('key %s is missing', $key)],
                fn($key, $error) => [$key => $error]
            );

            it('succeeds when the validation of every field succeeds', function () use ($test, $validation) {
                $test->forAll(
                    new AssociativeArrayGenerator([
                        'int' => new IntegerGenerator(),
                        'string' => new StringGenerator()
                    ])
                )->then(function (array $a) use ($validation) {
                    expect($validation->validate($a))->toBeEither(Either::right($a));
                });
            });

            it('fails when the input is not an array', function () use ($test, $validation) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(function (int $i) use ($validation) {
                    expect($validation->validate($i))->toBeEither(Either::left(['not an array']));
                });
            });

            it('fails when a key is missing', function () use ($test, $validation) {
                $test->forAll(
                    new AssociativeArrayGenerator([
                        'int' => new IntegerGenerator()
                    ])
                )->then(function (array $a) use ($validation) {
                    expect($validation->validate($a))->toBeEither(Either::left(['key string is missing']));
                });
            });

            it('fails when a key contains a value which does not pass validation', function () use ($test, $validation) {
                $test->forAll(
                    new AssociativeArrayGenerator([
                        'int' => new IntegerGenerator(),
                        'string' => new IntegerGenerator()
                    ])
                )->then(function (array $a) use ($validation) {
                    expect($validation->validate($a))->toBeEither(Either::left(['string' => ['string is not a string']]));
                });
            });

            it('preserves the type changing validations at the field level', function () use ($test) {
                $validation = V::associativeArray(
                    [
                        'aaa' => V::valid(),
                        'foo' => new V(fn(int $i) => Either::right((string)$i)),
                        'bar' => new V(fn(int $i) => Either::right((float)$i)),
                        'baz' => V::isInteger('not an integer'),
                        'gaf' => V::isString('not a string')
                    ],
                    ['not an array'],
                    new ConcatenationMonoid(),
                    fn($key) => [sprintf('key %s is missing', $key)],
                    fn($key, $error) => [$key => $error]
                );

                $data = [
                    'aaa' => null,
                    'foo' => 42,
                    'bar' => 37,
                    'baz' => 29,
                    'gaf' => 'a string'
                ];

                expect($validation->validate($data))
                    ->toBeEither(Either::right([
                        'aaa' => null,
                        'foo' => '42',
                        'bar' => (float)37,
                        'baz' => 29,
                        'gaf' => 'a string'
                    ]));
            });
        });

        describe('everyElement', function () use ($test) {
            it('fails if the validation fails for at least one element', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(
                    function (array $a) {
                        $shouldNotContain42 = V::everyElement(
                            new FirstSemigroup(),
                            V::satisfies(fn(int $x) => $x !== 42, ['not 42'])
                        );

                        $a[] = 42;

                        expect($shouldNotContain42->validate($a))->toBeEither(Either::left(['not 42']));
                    }
                );
            });

            it('succeeds if the validation succeeds for every element', function () use ($test) {
                $test->forAll(
                    new SequenceGenerator(new IntegerGenerator())
                )->then(function (array $a) {
                    $everyElementIsFine = V::everyElement(
                        new FirstSemigroup(),
                        V::valid()
                    );

                    expect($everyElementIsFine->validate($a))->toBeEither(Either::right($a));
                });
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
                    ->toBeEither(Either::left('nope'));
            });

            it('succeeds if the validation on the focus succeeds', function () use ($lens) {
                $validation = new V(fn(string $s) => Either::right(strlen($s)));

                expect(V::focus($lens, $validation)->validate(['foo' => 'a string']))
                    ->toBeEither(Either::right(['foo' => 8]));
            });
        });

        describe('nullable', function () use ($test) {
            $validation = V::nullable(new ConcatenationMonoid(), ['not null'], V::isInteger(['not an integer']));

            it('fails if the value does not satisfy the validation', function () use ($test, $validation) {
                $test->forAll(
                    new StringGenerator()
                )->then(function (string $s) use ($validation) {
                    expect($validation->validate($s))->toBeEither(Either::left(['not null', 'not an integer']));
                });
            });

            it('succeeds if the value satisfies the validation', function () use ($test, $validation) {
                $test->forAll(
                    new IntegerGenerator()
                )->then(function (int $i) use ($validation) {
                    expect($validation->validate($i))->toBeEither(Either::right($i));
                });
            });

            it('succeeds if the value is null', function () use ($validation) {
                expect($validation->validate(null))->toBeEither(Either::right(null));
            });
        });
    });
});

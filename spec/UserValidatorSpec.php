<?php

declare(strict_types=1);

use Eris\Generator\AssociativeArrayGenerator;
use Eris\Generator\IntegerGenerator;
use Eris\Generator\SequenceGenerator;
use Eris\Generator\StringGenerator;
use Eris\Generator\SuchThatGenerator;
use Marcosh\LamPHPda\Either;
use Marcosh\LamPHPda\Instances\ListL\ConcatenationMonoid;
use Marcosh\LamPHPda\Optics\Lens;
use Marcosh\LamPHPda\Validation\Validation as V;
use Marcosh\LamPHPda\ValidationSpec\ValidationTest;

$test = new ValidationTest();

/*
 * suppose we have an object
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class User
{
    /** @var non-empty-string */
    private $name;

    /** @var positive-int */
    private $age;

    /**
     * @param non-empty-string $name
     * @param positive-int $age
     */
    public function __construct(string $name, int $age)
    {
        $this->name = $name;
        $this->age = $age;
    }

    /**
     * @param array{name: non-empty-string, age: positive-int} $rawData
     */
    public static function fromRawData(array $rawData): self
    {
        return new self($rawData['name'], $rawData['age']);
    }
}

/*
 * and we receive from somewhere some raw data like
 *
 * [
 *     'name' => $name,
 *     'age' => $age
 * ]
 *
 * that we need to validate and parse into our User object.
 *
 * We basically need to write a validator of type Validator<mixed, string[], User>, where string[] represents our
 * failure type.
 *
 * To do this, we need lenses to focus an array of type array{name: A, age: B} to its fields name of type A and age of
 * type B
 */

$nameLens = Lens::lens(
    fn(array $rawUser) => $rawUser['name'],
    function (array $rawUser, $newName) {
        $rawUser['name'] = $newName;

        return $rawUser;
    }
);

$ageLens = Lens::lens(
    fn(array $rawUser) => $rawUser['age'],
    function (array $rawUser, $newAge) {
        $rawUser['age'] = $newAge;

        return $rawUser;
    }
);

/*
 * Now we can write our validator. Let's build it up step by step.
 *
 * First we want validators for non-empty strings and for positive integers.
 *
 * They both check whether the input is of the correct type and then perform the further check.
 */

/** @var V<mixed, string[], non-empty-string> $nonEmptyString */
$nonEmptyString = V::isString(['name is not a string'])->then(V::nonEmptyString(['name is empty']));

/** @var V<mixed, string[], positive-int> $positiveInteger */
$positiveInteger = V::isInteger(['age is not an integer'])
    ->then(V::satisfies(fn(int $i) => $i > 0, ['age is not a positive integer']));

/*
 * Next we want to use these validators to build new validators which check whether an array has a "name" key containing
 * a non-empty string and has an "age" key containing a positive integer
 *
 * First we need to check whether the array contains the key. Once we know that, we can focus on the value contained at
 * that key and use the validators defined above.
 */

/** @var V<array, string[], array{name: non-empty-string}> $validation */
$hasNameKeyContainingNonEmptyString = V::hasKey('name', ['missing "name" key'])->then(
    V::focus(
        $nameLens,
        $nonEmptyString
    )
);

/** @var V<array, string[], array{age: positive-int}> $hasAgeKeyContainingPositiveInteger */
$hasAgeKeyContainingPositiveInteger = V::hasKey('age', ['missing "age" key'])->then(
    V::focus(
        $ageLens,
        $positiveInteger
    )
);

/*
 * Now we have all the ingredients to write the complete validator.
 *
 * First we check that the input is actually an array.
 * Then we check that both fields are present and contain correct data using the validators defined above; if both
 * validation fail we keep track of both error messages using the ConcatenationMonoid with just merges the lists of
 * errors.
 * Last we map the result into the `User` object.
 */

/** @var V<mixed, string[], User> $validation */
$validation =
    V::isArray(['not an array'])->then(
        V::all(new ConcatenationMonoid(), [
            $hasNameKeyContainingNonEmptyString,
            $hasAgeKeyContainingPositiveInteger
        ])
    )->rmap([User::class, 'fromRawData']);

/*
 * Now we can check that the validator actually behaves how we expect
 */

describe('UserValidation', function () use ($test, $validation) {
    it('succeeds if it receives valid input', function () use ($test, $validation) {
        $test->forAll(
            new AssociativeArrayGenerator([
                'name' => new SuchThatGenerator(
                    fn(string $s) => $s !== '',
                    new StringGenerator()
                ),
                'age' => new SuchThatGenerator(
                    fn(int $i) => $i > 0,
                    new IntegerGenerator()
                )
            ])
        )->then(function (array $rawData) use ($validation) {
            expect($validation->validate($rawData))->toEqual(Either::right(new User($rawData['name'], $rawData['age'])));
        });
    });

    it('fails if the input is not an array', function () use ($test, $validation) {
        $test->forAll(
            new IntegerGenerator()
        )->then(function (int $i) use ($validation) {
            expect($validation->validate($i))->toEqual(Either::left(['not an array']));
        });
    });

    it('fails if it is an array but does not contain any of the required keys', function () use ($test, $validation) {
        $test->forAll(
            new SequenceGenerator(new IntegerGenerator())
        )->then(function (array $a) use ($validation) {
            expect($validation->validate($a))->toEqual(Either::left([
                'missing "age" key',
                'missing "name" key'
            ]));
        });
    });

    it('fails if it is an array but does not contain the "name" key', function () use ($test, $validation) {
        $test->forAll(
            new AssociativeArrayGenerator([
                'age' => new SuchThatGenerator(
                    fn(int $i) => $i > 0,
                    new IntegerGenerator()
                )
            ])
        )->then(function (array $a) use ($validation) {
            expect($validation->validate($a))->toEqual(Either::left([
                'missing "name" key'
            ]));
        });
    });

    it('fails if it is an array but does not contain the "age" key', function () use ($test, $validation) {
        $test->forAll(
            new AssociativeArrayGenerator([
                'name' => new SuchThatGenerator(
                    fn(string $s) => $s !== '',
                    new StringGenerator()
                )
            ])
        )->then(function (array $a) use ($validation) {
            expect($validation->validate($a))->toEqual(Either::left([
                'missing "age" key'
            ]));
        });
    });

    it('fails if name is not a string', function () use ($test, $validation) {
        $test->forAll(
            new AssociativeArrayGenerator([
                'name' => new IntegerGenerator(),
                'age' => new SuchThatGenerator(
                    fn(int $i) => $i > 0,
                    new IntegerGenerator()
                )
            ])
        )->then(function (array $rawData) use ($validation) {
            expect($validation->validate($rawData))->toEqual(Either::left(['name is not a string']));
        });
    });

    it('fails if name is not a non-empty string', function () use ($test, $validation) {
        $test->forAll(
            new AssociativeArrayGenerator([
                'name' => '',
                'age' => new SuchThatGenerator(
                    fn(int $i) => $i > 0,
                    new IntegerGenerator()
                )
            ])
        )->then(function (array $rawData) use ($validation) {
            expect($validation->validate($rawData))->toEqual(Either::left(['name is empty']));
        });
    });

    it('fails if age is not an integer', function () use ($test, $validation) {
        $test->forAll(
            new AssociativeArrayGenerator([
                'name' => new SuchThatGenerator(
                    fn(string $s) => $s !== '',
                    new StringGenerator()
                ),
                'age' => new StringGenerator()
            ])
        )->then(function (array $rawData) use ($validation) {
            expect($validation->validate($rawData))->toEqual(Either::left(['age is not an integer']));
        });
    });

    it('fails if name is not a non-empty string', function () use ($test, $validation) {
        $test->forAll(
            new AssociativeArrayGenerator([
                'name' => new SuchThatGenerator(
                    fn(string $s) => $s !== '',
                    new StringGenerator()
                ),
                'age' => new SuchThatGenerator(
                    fn(int $i) => $i <= 0,
                    new IntegerGenerator()
                )
            ])
        )->then(function (array $rawData) use ($validation) {
            expect($validation->validate($rawData))->toEqual(Either::left(['age is not a positive integer']));
        });
    });
});

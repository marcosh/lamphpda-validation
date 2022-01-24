<?php

declare(strict_types=1);

namespace Marcosh\LamPHPda\ValidationSpec;

use Closure;
use Generator;
use Marcosh\LamPHPda\Instances\ListL\ConcatenationMonoid;
use Marcosh\LamPHPda\Validation\Validation;
use Marcosh\LamPHPda\Validation\Validation as V;

final class TinValidator
{
    /** @var array<string, int> */
    private static array $dateType = [];

    public static function isFollowBelgiumRule1(string $tin): bool
    {
        $divisionRemainderBy97 = (int) (substr($tin, 0, 9)) % 97;

        return 97 - $divisionRemainderBy97 === (int) (substr($tin, 9, 3));
    }

    public static function isFollowBelgiumRule2(string $tin): bool
    {
        $divisionRemainderBy97 = (int) ('2' . substr($tin, 0, 9)) % 97;

        return 97 - $divisionRemainderBy97 === (int) (substr($tin, 9, 3));
    }

    public static function getDateType(string $tin): int
    {
        if (array_key_exists($tin, self::$dateType)) {
            return self::$dateType[$tin];
        }

        $year = (int) (substr($tin, 0, 2));
        $month = (int) (substr($tin, 2, 2));
        $day = (int) (substr($tin, 4, 2));

        $y1 = checkdate($month, $day, 1900 + $year);
        $y2 = checkdate($month, $day, 2000 + $year);

        if (0 === $day || 0 === $month || ($y1 && $y2)) {
            $dateType = 3;
        } elseif ($y1) {
            $dateType = 1;
        } elseif ($y2) {
            $dateType = 2;
        } else {
            $dateType = 0;
        }

        self::$dateType[$tin] = $dateType;

        return $dateType;
    }

    public static function hasValidDateType(string $tin): bool
    {
        return 0 !== self::getDateType($tin);
    }

    public static function followRule1(string $tin): bool
    {
        $dateType = self::getDateType($tin);

        return self::isFollowBelgiumRule1($tin) && (1 === $dateType || 3 === $dateType);
    }

    public static function followRule2(string $tin): bool
    {
        $dateType = self::getDateType($tin);

        return self::isFollowBelgiumRule2($tin) && 2 <= $dateType;
    }

    public static function hasValidLength(string $tin): bool
    {
        return 11 === strlen($tin);
    }

    public static function hasValidPattern(string $tin): bool
    {
        $pattern = '\\d{2}[0-1]\\d[0-3]\\d{6}';

        return 1 === preg_match(sprintf('/%s/', $pattern), $tin);
    }

    public static function belgian(): Validation
    {
        $r = V::isString(['TIN type must be a string.'])
            ->then(V::satisfies([TinValidator::class, 'hasValidLength'], ['TIN length is invalid.']))
            ->then(V::satisfies([TinValidator::class, 'hasValidPattern'], ['TIN pattern is invalid.']))
            ->then(V::satisfies([TinValidator::class, 'hasValidDateType'], ['TIN date is invalid.']));

        // Branch 1
        $br1 = V::satisfies([TinValidator::class, 'followRule1'], ['TIN validation of rule 1 failed.']);
        // Branch 2
        $br2 = V::satisfies([TinValidator::class, 'followRule2'], ['TIN validation of rule 2 failed.']);

        return $r->then($br1->or(new ConcatenationMonoid(), $br2));
    }
}

describe('Use Case Validation Spec', function () {
    describe('Validate Belgian TIN numbers', function () {
        $testCases = function (): Generator {
            yield ['Invalidate TIN number having wrong type', 123456, 'TIN type must be a string.'];
            yield ['Invalidate TIN number having invalid length', '0123456789', 'TIN length is invalid.'];
            yield ['Invalidate TIN number having invalid pattern', 'wwwwwwwwwww', 'TIN pattern is invalid.'];
            yield ['Invalidate TIN number having invalid date', '81023011101', 'TIN date is invalid.'];
            yield ['Validate TIN number', '01062624339', '01062624339'];
            yield ['Invalidate TIN number', '81092499999', 'TIN validation of rule 2 failed.'];
        };

        foreach ($testCases() as list($testCase, $tinNumber, $expected)) {
            it($testCase, function () use ($tinNumber, $expected): void {
                $ifLeft = static fn (array $i): string => current($i);
                $ifRight = static fn (string $i): string => $i;

                expect(
                    TinValidator::Belgian()->validate($tinNumber)->eval($ifLeft, $ifRight)
                )->toBe($expected);
            });
        }
    });
});

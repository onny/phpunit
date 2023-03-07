<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Metadata;

use function array_merge;
use function range;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\CodeCoverageException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Metadata\Api\CodeCoverage;
use PHPUnit\TestFixture\CoverageClassNothingTest;
use PHPUnit\TestFixture\CoverageClassTest;
use PHPUnit\TestFixture\CoverageClassWithoutAnnotationsTest;
use PHPUnit\TestFixture\CoverageCoversOverridesCoversNothingTest;
use PHPUnit\TestFixture\CoverageFunctionParenthesesTest;
use PHPUnit\TestFixture\CoverageFunctionParenthesesWhitespaceTest;
use PHPUnit\TestFixture\CoverageFunctionTest;
use PHPUnit\TestFixture\CoverageMethodNothingCoversMethod;
use PHPUnit\TestFixture\CoverageMethodNothingTest;
use PHPUnit\TestFixture\CoverageMethodOneLineAnnotationTest;
use PHPUnit\TestFixture\CoverageMethodParenthesesTest;
use PHPUnit\TestFixture\CoverageMethodParenthesesWhitespaceTest;
use PHPUnit\TestFixture\CoverageMethodTest;
use PHPUnit\TestFixture\CoverageNamespacedFunctionTest;
use PHPUnit\TestFixture\CoverageNoneTest;
use PHPUnit\TestFixture\IgnoringCodeUnitsTest;
use PHPUnit\TestFixture\InterfaceTargetTest;
use PHPUnit\TestFixture\InvalidClassTargetWithAnnotationTest;
use PHPUnit\TestFixture\InvalidClassTargetWithAttributeTest;
use PHPUnit\TestFixture\InvalidFunctionTargetTest;
use PHPUnit\TestFixture\MoreThanOneCoversDefaultClassAnnotationTest;
use PHPUnit\TestFixture\MoreThanOneUsesDefaultClassAnnotationTest;
use PHPUnit\TestFixture\NamespaceCoverageClassTest;
use PHPUnit\TestFixture\NamespaceCoverageCoversClassPublicTest;
use PHPUnit\TestFixture\NamespaceCoverageCoversClassTest;
use PHPUnit\TestFixture\NamespaceCoverageMethodTest;
use PHPUnit\TestFixture\Test3194;

#[\PHPUnit\Framework\Attributes\CoversClass(CodeCoverage::class)]
#[Small]
final class CodeCoverageFacadeTest extends TestCase
{
    public static function linesToBeCoveredProvider(): array
    {
        return [
            [
                CoverageNoneTest::class,
                [],
            ],
            [
                CoverageClassTest::class,
                [
                    TEST_FILES_PATH . 'CoveredClass.php' => range(29, 46),
                ],
            ],
            [
                CoverageMethodTest::class,
                [
                    TEST_FILES_PATH . 'CoveredClass.php' => range(31, 35),
                ],
            ],
            [
                CoverageMethodOneLineAnnotationTest::class,
                [
                    TEST_FILES_PATH . 'CoveredClass.php' => range(31, 35),
                ],
            ],
            [
                CoverageFunctionTest::class,
                [
                    TEST_FILES_PATH . 'CoveredFunction.php' => range(10, 12),
                ],
            ],
            [
                NamespaceCoverageClassTest::class,
                [
                    TEST_FILES_PATH . 'NamespaceCoveredClass.php' => range(29, 46),
                ],
            ],
            [
                NamespaceCoverageMethodTest::class,
                [
                    TEST_FILES_PATH . 'NamespaceCoveredClass.php' => range(31, 35),
                ],
            ],
            [
                NamespaceCoverageCoversClassTest::class,
                [
                    TEST_FILES_PATH . 'NamespaceCoveredClass.php' => array_merge(range(43, 45), range(37, 41), range(31, 35), range(24, 26), range(19, 22), range(14, 17)),
                ],
            ],
            [
                NamespaceCoverageCoversClassPublicTest::class,
                [
                    TEST_FILES_PATH . 'NamespaceCoveredClass.php' => range(31, 35),
                ],

            ],
            [
                CoverageClassNothingTest::class,
                false,
            ],
            [
                CoverageMethodNothingTest::class,
                false,
            ],
            [
                CoverageCoversOverridesCoversNothingTest::class,
                [
                    TEST_FILES_PATH . 'CoveredClass.php' => range(31, 35),
                ],
            ],
            [
                CoverageMethodNothingCoversMethod::class,
                false,
            ],
        ];
    }

    public static function canSkipCoverageProvider(): array
    {
        return [
            [CoverageClassTest::class, false],
            [CoverageClassWithoutAnnotationsTest::class, false],
            [CoverageCoversOverridesCoversNothingTest::class, false],
            [CoverageClassNothingTest::class, true],
            [CoverageMethodNothingTest::class, true],
        ];
    }

    /**
     * @psalm-param class-string $test
     */
    #[DataProvider('linesToBeCoveredProvider')]
    public function testGetLinesToBeCovered(string $test, array|false $expected): void
    {
        $this->assertEqualsCanonicalizing(
            $expected,
            (new CodeCoverage)->linesToBeCovered(
                $test,
                'testSomething'
            )
        );
    }

    #[\PHPUnit\Framework\Attributes\TestDox('More than one @coversDefaultClass annotation is rejected')]
    public function testMoreThanOneCoversDefaultClassAnnotationIsRejected(): void
    {
        $this->expectException(CodeCoverageException::class);
        $this->expectExceptionMessage('More than one @coversDefaultClass annotation for class');

        (new CodeCoverage)->linesToBeCovered(MoreThanOneCoversDefaultClassAnnotationTest::class, 'testOne');
    }

    #[\PHPUnit\Framework\Attributes\TestDox('More than one @usesDefaultClass annotation is rejected')]
    public function testMoreThanOneUsesDefaultClassAnnotationIsRejected(): void
    {
        $this->expectException(CodeCoverageException::class);
        $this->expectExceptionMessage('More than one @usesDefaultClass annotation for class');

        (new CodeCoverage)->linesToBeUsed(MoreThanOneUsesDefaultClassAnnotationTest::class, 'testOne');
    }

    public function testRejectsInterfaceClassTarget(): void
    {
        $this->expectException(CodeCoverageException::class);
        $this->expectExceptionMessage('Trying to @cover interface "\Throwable".');

        (new CodeCoverage)->linesToBeCovered(InterfaceTargetTest::class, 'testOne');
    }

    public function testRejectsInvalidClassTargetWithAttribute(): void
    {
        $this->expectException(CodeCoverageException::class);
        $this->expectExceptionMessage('Class "InvalidClass" is not a valid target for code coverage');

        (new CodeCoverage)->linesToBeCovered(InvalidClassTargetWithAttributeTest::class, 'testOne');
    }

    public function testRejectsInvalidClassTargetWithAnnotation(): void
    {
        $this->expectException(CodeCoverageException::class);
        $this->expectExceptionMessage('"@covers InvalidClass" is invalid');

        (new CodeCoverage)->linesToBeCovered(InvalidClassTargetWithAnnotationTest::class, 'testOne');
    }

    public function testRejectsInvalidFunctionTarget(): void
    {
        $this->expectException(CodeCoverageException::class);
        $this->expectExceptionMessage('Function "::invalid_function" is not a valid target for code coverage');

        (new CodeCoverage)->linesToBeCovered(InvalidFunctionTargetTest::class, 'testOne');
    }

    public function testFunctionParenthesesAreAllowed(): void
    {
        $this->assertSame(
            [TEST_FILES_PATH . 'CoveredFunction.php' => range(10, 12)],
            (new CodeCoverage)->linesToBeCovered(
                CoverageFunctionParenthesesTest::class,
                'testSomething'
            )
        );
    }

    public function testFunctionParenthesesAreAllowedWithWhitespace(): void
    {
        $this->assertSame(
            [TEST_FILES_PATH . 'CoveredFunction.php' => range(10, 12)],
            (new CodeCoverage)->linesToBeCovered(
                CoverageFunctionParenthesesWhitespaceTest::class,
                'testSomething'
            )
        );
    }

    public function testMethodParenthesesAreAllowed(): void
    {
        $this->assertSame(
            [TEST_FILES_PATH . 'CoveredClass.php' => range(31, 35)],
            (new CodeCoverage)->linesToBeCovered(
                CoverageMethodParenthesesTest::class,
                'testSomething'
            )
        );
    }

    public function testMethodParenthesesAreAllowedWithWhitespace(): void
    {
        $this->assertSame(
            [TEST_FILES_PATH . 'CoveredClass.php' => range(31, 35)],
            (new CodeCoverage)->linesToBeCovered(
                CoverageMethodParenthesesWhitespaceTest::class,
                'testSomething'
            )
        );
    }

    public function testNamespacedFunctionCanBeCoveredOrUsed(): void
    {
        $this->assertEquals(
            [
                TEST_FILES_PATH . 'NamespaceCoveredFunction.php' => range(12, 15),
            ],
            (new CodeCoverage)->linesToBeCovered(
                CoverageNamespacedFunctionTest::class,
                'testFunc'
            )
        );
    }

    public function testCoversAnnotationIncludesTraitsUsedByClass(): void
    {
        $this->assertSame(
            [
                TEST_FILES_PATH . '3194.php' => array_merge(range(14, 20), range(22, 30)),
            ],
            (new CodeCoverage)->linesToBeCovered(
                Test3194::class,
                'testOne'
            )
        );
    }

    /**
     * @psalm-param class-string $testCase
     */
    #[DataProvider('canSkipCoverageProvider')]
    public function testCanSkipCoverage(string $testCase, bool $expectedCanSkip): void
    {
        $test             = new $testCase('testSomething');
        $coverageRequired = (new CodeCoverage)->shouldCodeCoverageBeCollectedFor($test::class, $test->name());
        $canSkipCoverage  = !$coverageRequired;

        $this->assertEquals($expectedCanSkip, $canSkipCoverage);
    }

    public function testCanIgnoreCodeUnits(): void
    {
        $this->assertSame(
            [
                TEST_FILES_PATH . 'CoveredClass.php'    => array_merge(range(12, 27), range(37, 41)),
                TEST_FILES_PATH . 'CoveredFunction.php' => range(10, 12),
            ],
            (new CodeCoverage)->linesToBeIgnored(TestSuite::fromClassName(IgnoringCodeUnitsTest::class))
        );
    }
}

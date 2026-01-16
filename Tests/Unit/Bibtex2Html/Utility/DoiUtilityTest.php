<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Unit\Tests\Bibtex2Html\Utility;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Uniolweb\Bibtex\Bibtex2Html\Utility\DoiUtility;

class DoiUtilityTest extends UnitTestCase
{
    /**
     * @return \Generator<string,array<string,string>>
     */
    public static function doiProvider(): \Generator
    {
        yield 'Simple entry' => [
            'doi' => '10.3390/nu14030400',
            'expectedResult' => '10.3390/nu14030400'
        ];
        yield 'URL as doi entry' => [
            'doi' => 'http://dx.doi.org/10.1093/comjnl/39.6.496',
            'expectedResult' => '10.1093/comjnl/39.6.496',
        ];
        yield 'DOI with ()' => [
            'doi' => '10.1016/S1744-1161(11)70077-1',
            'expectedResult' => '10.1016/S1744-1161(11)70077-1',
        ];
        yield 'DOI with {\\textunderscore }' => [
            'doi' => '10.1007/978-3-662-63099-0{\\textunderscore }5',
            'expectedResult' => '10.1007/978-3-662-63099-0_5',
        ];
        yield 'DOI with $\\backslash$textunderscore' => [
            'doi' => '10.1007/978-3-030-29196-9$\\backslash$textunderscore',
            'expectedResult' => '10.1007/978-3-030-29196-9',
        ];
    }

    /**
     * @test
     * @dataProvider doiProvider
     */
    public function normalizeDoiReturnsDoi(string $doi, string $expectedResult): void
    {
        $result = DoiUtility::normalizeDoi($doi);

        self::assertEquals($expectedResult, $result);
    }
}

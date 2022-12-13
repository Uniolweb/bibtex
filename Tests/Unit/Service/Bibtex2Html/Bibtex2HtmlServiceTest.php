<?php
declare(strict_types=1);
namespace Uniolit\Bibtex\Unit\Tests\Service\Bibtex2Html;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Uniolit\Bibtex\Configuration\BibtexSettings;
use Uniolit\Bibtex\Service\Bibtex2Html\Bibtex2HtmlService;

class Bibtex2HtmlServiceTest extends UnitTestCase
{
    protected function loadBibtexFile(string $relativePathBibtex): string
    {
        $thisDir = realpath(__DIR__);
        return file_get_contents($thisDir . '/' . $relativePathBibtex);
    }

    protected function replaceNewline(string $content): string
    {
        $content = trim($content);
        $content = \str_replace("\r", "\n", $content);
        $content = \str_replace("\t", '', $content);
        $content = \str_replace('"', '\'', $content);
        $content = \preg_replace("#(^|>) *(<|$)#", '\1\2', $content);
        return $content;
    }

    /**
     * @return \Generator<string,array<string,string>>
     */
    public function bibtex2HtmlReturnsCorrectHtmlDataProvider(): \Generator
    {
        yield "Test 1" => [
            'bibtexfile' => 'Fixtures/CBO_DP_1item/CBO_DP.bib',
            'htmlfile' => 'Fixtures/CBO_DP_1item/CBO_DP.html',
        ];
    }

    /**
     * @test
     * @dataProvider bibtex2HtmlReturnsCorrectHtmlDataProvider
     */
    public function bibtex2HtmlReturnsCorrectHtml(string $bibtexfile, string $htmlfile): void
    {
        $bibtexSettings = new BibtexSettings('none', false);
        $absoluteBibtexfile = realpath(__DIR__) . '/' . $bibtexfile;
        //$content = $this->loadBibtexFile($bibtexfile);
        $bibtex2HtmlService = new Bibtex2HtmlService();
        $actualResult = $this->replaceNewline(
            $bibtex2HtmlService->bibtex2Html($absoluteBibtexfile, $bibtexSettings, 0, true)
        );
        $expectedResult = $this->replaceNewline(
            file_get_contents(realpath(__DIR__) . '/' . $htmlfile)
        );
        $this->assertEquals($expectedResult, $actualResult);
    }
}

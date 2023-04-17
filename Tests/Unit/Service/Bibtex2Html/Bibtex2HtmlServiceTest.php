<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Unit\Tests\Service\Bibtex2Html;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Uniolit\Bibtex\Bibtex2Html\Service\Bibtex2HtmlService;

class Bibtex2HtmlServiceTest extends UnitTestCase
{
    protected function loadBibtexFile(string $relativePathBibtex): string
    {
        $thisDir = realpath(__DIR__);
        return file_get_contents($thisDir . '/' . $relativePathBibtex);
    }

    protected function instantiateBibtex2HtmlService(): Bibtex2HtmlService
    {
        return new Bibtex2HtmlService(
            null,
            Bibtex2HtmlService::DEFAULT_STYLES_PATH,
            Bibtex2HtmlService::DEFAULT_OSBIBPATH
        );
    }

    /**
     * @return \Generator<string,array<string,string>>
     */
    public function bibtexPreProcessReturnsCorrectEntriesProvider(): \Generator
    {
        yield 'Simple entry' => [
            'bibtexContent' => '@TechReport{V-400-17,
                author      = {Christoph B{\"o}hringer and Thomas F. Rutherford},
                institution = {Oldenburger Diskussionspapiere},
                title       = {Paris after Trump: An inconvenient insight},
                year        = {2017},
                url         = {https://uol.de/f/2/dept/wire/fachgebiete/vwl/V-432-20.pdf},
                }',
            'expectedEntry' => [
                'bibtexEntryType'   => 'techreport',
                'bibtexCitation'    => 'V-400-17',
                // do not check bibtexEntry here
                //'bibtexEntry'       => '@TechReport{V-400-17, institution = {Oldenburger Diskussionspapiere, V-400-17, }, title       = {Paris after Trump: An inconvenient insight}, year        = {2017}, url         = {https://uol.de/f/2/dept/wire/fachgebiete/vwl/V-432-20.pdf}',
                'institution'       => '{Oldenburger Diskussionspapiere, V-400-17, }',
                'title'             => 'Paris after Trump: An inconvenient insight',
                'year'              => '2017',
                'url'               => 'https://uol.de/f/2/dept/wire/fachgebiete/vwl/V-432-20.pdf',

            ],
        ];
    }

    /**
     * @test
     * @dataProvider bibtexPreProcessReturnsCorrectEntriesProvider
     * @param string $bibtexContent
     * @param array<int,array<string,string>> $expectedResult
     */
    public function bibtexPreProcessReturnsCorrectEntries(string $bibtexContent, array $expectedResult): void
    {
        $bibtex2HtmlService = $this->instantiateBibtex2HtmlService();
        $bibtex2HtmlService->autoloadClasses();
        $actualResults = $bibtex2HtmlService->bibtexPreProcess($bibtexContent);
        $entry = reset($actualResults);

        self::assertTrue(isset($entry['bibtexEntry']));
        unset($entry['bibtexEntry']);

        self::assertEquals(ksort($expectedResult), ksort($entry));
    }

    /**
     * @return \Generator<mixed>
     */
    public function bibtexPreProcessTrimsTitleDataProvider(): \Generator
    {
        yield 'Trim title' => [
            '@BOOK{,
Author = {Uslar, Mathias; Specht, Michael; D{\"a}nekas, Christian; Trefke, J{\"o}rn; Rohjans, Sebastian; Gonzalez, Jose; Rosinger, Christine; Bleiker, Robert},
Title = { Standardization in Smart Grids},
Year = {2013},
Month = {01},
Note = {Besides the regulatory and market aspects, the technical level dealing with the knowledge from multiple disciplines and the aspects of technical system integration to achieve interoperability and integration has been a strong focus in the Smart Grid. This topic is typically covered by the means of using (technical) standards for processes, data models, functions and communication links. Standardization is a key issue for Smart Grids due to the involvement of many different sectors along the value chain from the generation to the appliances. The scope of Smart Grid is broad, therefore, the standards landscape is unfortunately very large and complex. This is why the three European Standards Organizations ETSI, CEN and CENELEC created a so called Joint Working Group (JWG). This was the first harmonized effort in Europe to bring together the needed disciplines and experts delivering the final report in May 2011. After this approach proved useful, the Commission used the Mandate M/490: Standardization Mandate to European Standardization Organizations (ESOs) to support European Smart Grid deployment. The focal point addressing the ESOs response to M/490 will be the CEN, CENELEC and ETSI Smart Grids Coordination Group (SG--CG). Based on this mandate, meaningful standardization of architectures, use cases, communication technologies, data models and security standards takes place in the four existing working groups.
This book provides an overview on the various building blocks and standards identified as the most prominent ones by the JWG report as well as by the first set of standards group -- IEC 61850 and CIM, IEC PAS 62559 for documenting Smart Grid use cases, security requirements from the SGIS groups and an introduction on how to apply the Smart Grid Architecture Model SGAM for utilities. In addition, future standards from ENTSO--E for market communications, standards for electric vehicles and future industrial automation, OPC UA are introduced. },
Publisher = {Springer},
Series = {Power Systems},
Isbn = {978--3--642--34915--7},
Booktitle = {Standardization in Smart Grids: Introduction to IT--Related Methodologies, Architectures and Standards}
}',
            'expectedTitle' => 'Standardization in Smart Grids'
        ];
    }

    /**
     * @test
     * @dataProvider bibtexPreProcessTrimsTitleDataProvider
     * @param string $bibtexContent
     * @param string $expectedTitle
     */
    public function bibtexPreProcessTrimsTitle(string $bibtexContent, string $expectedTitle): void
    {
        $bibtex2HtmlService = $this->instantiateBibtex2HtmlService();
        $bibtex2HtmlService->autoloadClasses();
        $actualResults = $bibtex2HtmlService->bibtexPreProcess($bibtexContent);
        $firstEntry = reset($actualResults);

        self::assertTrue(isset($firstEntry['title']));
        $actualTitle = $firstEntry['title'];

        self::assertEquals($expectedTitle, $actualTitle);
    }

    /**
     * @return \Generator<mixed>
     */
    public function bibtexPreProcessHandlesUnicodeInAuthorsDataProvider(): \Generator
    {
        yield 'Handle unicode in autors' => [
            '@INPROCEEDINGS{,
Author = {Trefke, Jörn; Rohjans, Sebastian; Uslar, Mathias; Lehnhoff, Sebastian; Nordström, Lars; Saleem, Arshad},
Title = {Smart Grid Architecture Model Use Case Management in a large European Smart Grid Project},
Year = {2013},
Month = {10},
Organization = {IEEE}
}',
            'expectedAuthors' => 'Trefke, Jörn; Rohjans, Sebastian; Uslar, Mathias; Lehnhoff, Sebastian; Nordström, Lars; Saleem, Arshad'
        ];
    }

    /**
     * @test
     * @dataProvider bibtexPreProcessHandlesUnicodeInAuthorsDataProvider
     * @param string $bibtexContent
     * @param string $expectedAuthors
     */
    public function bibtexPreProcessHandlesUnicodeInAuthors(string $bibtexContent, string $expectedAuthors): void
    {
        $bibtex2HtmlService = $this->instantiateBibtex2HtmlService();
        $bibtex2HtmlService->autoloadClasses();
        $actualResults = $bibtex2HtmlService->bibtexPreProcess($bibtexContent);
        $firstEntry = reset($actualResults);

        self::assertTrue(isset($firstEntry['author']));
        $actualAuthors = $firstEntry['author'];

        self::assertEquals($expectedAuthors, $actualAuthors);
    }

    /**
     * @return \Generator<mixed>
     */
    public function bibtexPreProcessHandlesCitationKeyWithParenthesisWithoutEndlessLoopDataProvider(): \Generator
    {
        yield 'Handle entry with citation key with ()' => [
            '@misc{RohlJHHellmersSDiekmannRHeinA(2022):.2022,
 author = {{R{\"o}hl, JH, Hellmers, S, Diekmann, R, Hein A (2022):}},
 year = {2022},
 title = {Concept of an Observation-driven Android Robot-Patient with individualized Communication Skills. Conference for Biomedical Robotics and Biomechatronic},
 url = {https://ieeexplore.ieee.org/document/9925488.},
 urldate = {07.11.22}
}',
            'expectedAuthors' => 'Röhl, JH, Hellmers, S, Diekmann, R, Hein A (2022):'
        ];
    }

    /**
     * @todo This test currently fails because of work-around against endless loop
     * @dataProvider bibtexPreProcessHandlesCitationKeyWithParenthesisWithoutEndlessLoopDataProvider
     * @param string $bibtexContent
     * @param string $expectedAuthors
     */
    public function bibtexPreProcessHandlesCitationKeyWithParenthesisWithoutEndlessLoop(string $bibtexContent, string $expectedAuthors): void
    {
        $bibtex2HtmlService = $this->instantiateBibtex2HtmlService();
        $bibtex2HtmlService->autoloadClasses();
        $actualResults = $bibtex2HtmlService->bibtexPreProcess($bibtexContent);
        $firstEntry = reset($actualResults);

        self::assertTrue(isset($firstEntry['author']));
        $actualAuthors = $firstEntry['author'];

        self::assertEquals($expectedAuthors, $actualAuthors);
    }
}

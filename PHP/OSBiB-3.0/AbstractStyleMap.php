<?php

declare(strict_types=1);

abstract class AbstractStyleMap implements StyleMapInterface
{
    protected array $unpublished = [];
    protected array $music_score = [];
    protected array $music_track = [];
    protected array $music_album = [];
    protected array $proceedings = [];
    protected array $personal = [];
    protected array $patent = [];
    protected array $statute = [];
    protected array $chart = [];
    protected array $map = [];
    protected array $manuscript = [];
    protected array $database = [];
    protected array $hearing = [];
    protected array $report = [];
    protected array $government_report = [];
    protected array $miscellaneous = [];
    protected array $conference_paper = [];
    protected array $classical = [];
    protected array $bill = [];
    protected array $legal_ruling = [];
    protected array $case = [];
    protected array $audiovisual = [];
    protected array $artwork = [];
    protected array $software = [];
    protected array $broadcast = [];
    protected array $film = [];
    protected array $web_article = [];
    protected array $thesis = [];
    protected array $proceedings_article = [];
    protected array $magazine_article = [];
    protected array $newspaper_article = [];
    protected array $journal_article = [];
    protected array $book_article = [];
    protected array $book = [];
    protected array $genericMisc = [];
    protected array $genericArticle = [];
    protected array $genericBook = [];
    protected array $basic = [];

    /**
     * Use this type for unknown type
     *
     * @var string
     */
    const DEFAULT_TYPE = 'genericMisc';

    protected const DEFAULT_CITATION = [
        'creator' => 'creator',
        'title'	=> 'title',
        'year' => 'year',
        'pages' => 'pages',
    ];
    /**
     * What fields are available to the in-text citation template? This array should NOT be changed.
     */
    protected array $citation = [];

    protected const DEFAULT_CITATION_ENDNOTE_IN_TEXT = [
        'id' => 'id',
        'pages' => 'pages',
    ];

    /**
     * What fields are available to the in-text citation template for endnote-style citations? This array should NOT be changed.
     */
    protected array $citationEndnoteInText = [];

    protected const DEFAULT_CITATION_ENDNOTE = [
        'citation' => 'citation',
        'creator' => 'creator',
        'title'	=> 'title',
        'year' => 'year',
        'pages' => 'pages',
    ];

    /**
     * What fields are available to the endnote citation template for endnote-style citations? This array should NOT be changed.
     */
    protected array $citationEndnote = [];

    protected array $types = [];

    public function __construct()
    {
        $this->loadMap();
    }

    public function mapType(string $type): string
    {
        $mappedType = array_search($type, $this->types);

        return $mappedType;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getCitation(): array
    {
        return $this->citation;
    }

    public function getCitationEndNote(): array
    {
        return $this->citationEndnote;
    }

    public function getCitationEndNoteInText(): array
    {
        return $this->citationEndnoteInText;
    }

    /**
     * @param string $propertyName
     * @return string|array
     */
    public function getDynamicProperty(string $propertyName)
    {
        return $this->$propertyName;
    }

    public function getDynamicPropertyArrayElement(string $propertyName, string $arrayElement): string
    {
        return $this->$propertyName[$arrayElement];
    }
}

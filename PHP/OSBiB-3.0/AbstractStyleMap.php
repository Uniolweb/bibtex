<?php

declare(strict_types=1);

abstract class AbstractStyleMap implements StyleMapInterface
{
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

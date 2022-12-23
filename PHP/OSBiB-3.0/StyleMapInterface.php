<?php

declare(strict_types=1);

interface StyleMapInterface
{
    public function loadMap();

    public function getTypes(): array;

    public function getCitation(): array;

    public function getCitationEndNote(): array;

    public function getCitationEndNoteInText(): array;

    /**
     * @param string $propertyName
     * @return string|array
     */
    public function getDynamicProperty(string $propertyName);

    public function getDynamicPropertyArrayElement(string $propertyName, string $arrayElement): string;
}

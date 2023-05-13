<?php

namespace BarrelStrength\Sprout\uris\components\links;

use craft\helpers\Cp;

abstract class AbstractElementLink extends AbstractLink
{
    abstract public static function elementType(): string;

    public static function displayName(): string
    {
        $elementType = static::elementType();

        return $elementType::displayName();
    }

    public function getInputHtml(): ?string
    {
        $element = null;

        $elementType = static::elementType();

        return Cp::elementSelectHtml([
            //'label' => $elementType::displayName(),
            'name' => 'elementId',
            'elements' => $element ? [$element] : [],
            'elementType' => $elementType,
            'selectionLabel' => 'Choose a ' . $elementType::displayName(),
            'single' => true,
            //'sources' => $this->sources(),
            //'criteria' => $this->criteria(),
            //'condition' => $this->selectionCondition(),
        ]);
    }
}

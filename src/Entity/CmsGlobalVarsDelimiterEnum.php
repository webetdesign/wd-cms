<?php

namespace WebEtDesign\CmsBundle\Entity;

/**
 * @deprecated not functional since version 4.0.0
 */
class CmsGlobalVarsDelimiterEnum
{
    const DOUBLE_UNDERSCORE      = 'DOUBLE_UNDERSCORE';
    const SQUARE_BRACKETS        = 'SQUARE_BRACKETS';
    const DOUBLE_SQUARE_BRACKETS = 'DOUBLE_SQUARE_BRACKETS';

    /** @var array user friendly named type */
    protected static $typeName = [
        self::DOUBLE_UNDERSCORE      => 'DOUBLE_UNDERSCORE',
        self::SQUARE_BRACKETS        => 'SQUARE_BRACKETS',
        self::DOUBLE_SQUARE_BRACKETS => 'DOUBLE_SQUARE_BRACKETS',
    ];

    /**
     * @param  string $typeShortName
     * @return string
     */
    public static function getName($typeShortName)
    {
        if (!isset(static::$typeName[$typeShortName])) {
            return "Unknown type ($typeShortName)";
        }

        return static::$typeName[$typeShortName];
    }

    /**
     * @return array<string>
     */
    public static function getAvailableTypes()
    {
        return [
            self::DOUBLE_UNDERSCORE,
            self::SQUARE_BRACKETS,
            self::DOUBLE_SQUARE_BRACKETS,
        ];
    }

    public static function getChoices()
    {
        $choices = [];
        foreach (self::getAvailableTypes() as $availableType) {
            $choices[$availableType] = self::getName($availableType);
        }
        return array_flip($choices);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 18/01/2019
 * Time: 10:47
 */

namespace WebEtDesign\CmsBundle\Entity;


class CmsMenuTypeEnum
{
    const DEFAULT   = 'DEFAULT';

    /** @var array user friendly named type */
    protected static array $typeName = [
        self::DEFAULT   => 'DEFAULT',
    ];

    /**
     * @param string $typeShortName
     * @return string
     */
    public static function getName(string $typeShortName): string
    {
        if (!isset(static::$typeName[$typeShortName])) {
            return "Unknown type ($typeShortName)";
        }

        return static::$typeName[$typeShortName];
    }

    /**
     * @return array<string>
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::DEFAULT,
        ];
    }

    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::getAvailableTypes() as $availableType) {
            $choices[$availableType] = self::getName($availableType);
        }
        return array_flip($choices);
    }
}

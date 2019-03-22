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
    const ROOT      = 'ROOT';
    const MENU      = 'MENU';
    const MENU_ITEM = 'MENU_ITEM';

    /** @var array user friendly named type */
    protected static $typeName = [
        self::ROOT      => 'ROOT',
        self::MENU      => 'MENU',
        self::MENU_ITEM => 'MENU_ITEM',
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
            self::ROOT,
            self::MENU,
            self::MENU_ITEM,
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

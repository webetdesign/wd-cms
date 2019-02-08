<?php

namespace WebEtDesign\CmsBundle\Entity;


class CmsMenuLinkTypeEnum
{
    const CMS_PAGE  = 'CMS_PAGE';
    const ROUTENAME = 'ROUTENAME';
    const URL       = 'URL';
    const PATH      = 'PATH';

    /** @var array user friendly named type */
    protected static $typeName = [
        self::CMS_PAGE  => 'Page cms',
        self::ROUTENAME => 'Route name',
        self::URL       => 'Url',
        self::PATH      => 'Static path',
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
            self::CMS_PAGE,
            self::ROUTENAME,
            self::URL,
            self::PATH,
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

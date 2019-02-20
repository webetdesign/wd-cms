<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 18/01/2019
 * Time: 10:47
 */

namespace WebEtDesign\CmsBundle\Entity;


class CmsContentTypeEnum
{
    const TEXT     = 'TEXT';
    const TEXTAREA = 'TEXTAREA';
    const WYSYWYG  = 'WYSYWYG';
    const MEDIA    = 'MEDIA';
    const SLIDER    = 'SLIDER';

    /** @var array user friendly named type */
    protected static $typeName = [
        self::TEXT     => 'Text',
        self::TEXTAREA => 'Textarea',
        self::WYSYWYG  => 'WYSYWYG',
        self::MEDIA    => 'Media',
        self::SLIDER    => 'Slider',
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
            self::TEXT,
            self::TEXTAREA,
            self::WYSYWYG,
            self::MEDIA,
            self::SLIDER,
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

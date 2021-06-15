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
    const WYSIWYG  = 'WYSIWYG';
    const CHECKBOX = "CHECKBOX";

    /** @var array user friendly named type */
    protected static array $typeName = [
        self::TEXT     => 'Text',
        self::TEXTAREA => 'Textarea',
        self::WYSIWYG  => 'WYSIWYG',
        self::CHECKBOX => 'CheckBox',
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
            self::TEXT,
            self::TEXTAREA,
            self::WYSIWYG,
            self::CHECKBOX,
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

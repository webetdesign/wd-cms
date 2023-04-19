<?php

namespace WebEtDesign\CmsBundle\Enum;

enum CmsVarsDelimiterEnum: string
{

    case DOUBLE_UNDERSCORE = "DOUBLE_UNDERSCORE";
    case SQUARE_BRACKETS = "SQUARE_BRACKETS";
    case DOUBLE_SQUARE_BRACKETS = "DOUBLE_SQUARE_BRACKETS";

    public function start(): string
    {
        return match ($this) {
            self::DOUBLE_UNDERSCORE      => '__',
            self::SQUARE_BRACKETS        => '[',
            self::DOUBLE_SQUARE_BRACKETS => '[[',
        };
    }

    public function end(): string
    {
        return match ($this) {
            self::DOUBLE_UNDERSCORE      => '__',
            self::SQUARE_BRACKETS        => ']',
            self::DOUBLE_SQUARE_BRACKETS => ']]',
        };
    }

}

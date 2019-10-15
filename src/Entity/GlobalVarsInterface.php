<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-07
 * Time: 17:47
 */

namespace WebEtDesign\CmsBundle\Entity;


interface GlobalVarsInterface
{
    public static function getAvailableVars(): array;
}

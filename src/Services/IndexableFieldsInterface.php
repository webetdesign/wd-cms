<?php


namespace WebEtDesign\CmsBundle\Services;


use WebEtDesign\CmsBundle\Entity\CmsContent;

interface IndexableFieldsInterface
{
    public function getIndexableFieldData(): string;
}

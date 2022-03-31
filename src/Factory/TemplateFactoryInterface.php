<?php

namespace WebEtDesign\CmsBundle\Factory;


use WebEtDesign\CmsBundle\CmsTemplate\TemplateInterface;

interface TemplateFactoryInterface
{
    public function get($code): TemplateInterface;

    public function getTemplateList($collection = null): array;

    public function getTemplateChoices($collection = null): array;
}

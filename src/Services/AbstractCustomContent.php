<?php

namespace WebEtDesign\CmsBundle\Services;

use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;

abstract class AbstractCustomContent implements CustomContentInterface
{
    abstract function getFormOptions(): array;

    abstract function getFormType(): string;

    abstract function getCallbackTransformer(): DataTransformerInterface;

    abstract function render(CmsContent $content);
}

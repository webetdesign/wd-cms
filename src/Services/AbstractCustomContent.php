<?php

namespace WebEtDesign\CmsBundle\Services;


use Symfony\Component\Form\CallbackTransformer;
use WebEtDesign\CmsBundle\Entity\CmsContent;

abstract class AbstractCustomContent implements CustomContentInterface
{
    abstract function getFormOptions(): array;

    abstract function getFormType(): string;

    abstract function getCallbackTransformer(): CallbackTransformer;

    abstract function render(CmsContent $content);
}

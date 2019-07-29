<?php

namespace WebEtDesign\CmsBundle\Services;


use Symfony\Component\Form\CallbackTransformer;
use WebEtDesign\CmsBundle\Entity\CmsContent;

interface CustomContentInterface
{
    public function getFormOptions(): array;

    public function getFormType(): string;

    public function getCallbackTransformer(): CallbackTransformer;

    public function render(CmsContent $content);
}

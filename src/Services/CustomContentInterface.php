<?php

namespace WebEtDesign\CmsBundle\Services;

use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;

interface CustomContentInterface
{
    public function getFormOptions(): array;

    public function getFormType(): string;

    public function getCallbackTransformer(): DataTransformerInterface;

    public function render(CmsContent $content);
}

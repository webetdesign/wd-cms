<?php

namespace WebEtDesign\CmsBundle\Services;

use Symfony\Component\Form\DataTransformerInterface;
use WebEtDesign\CmsBundle\Entity\CmsContent;

abstract class AbstractCustomContent implements CustomContentInterface
{
    protected array $contentOptions = [];

    abstract function getFormOptions(): array;

    abstract function getFormType(): string;

    abstract function getCallbackTransformer(): DataTransformerInterface;

    abstract function render(CmsContent $content);

    /**
     * @return array
     */
    public function getContentOptions(): array
    {
        return $this->contentOptions;
    }

    /**
     * @param array $contentOptions
     */
    public function setContentOptions(array $contentOptions): void
    {
        $this->contentOptions = $contentOptions;
    }
}

<?php

namespace WebEtDesign\CmsBundle\CmsTemplate;

use WebEtDesign\CmsBundle\DependencyInjection\Models\BlockDefinition;

interface TemplateInterface
{
    public function getLabel(): string;
    public function getCode(): ?string;
    public function setCode(?string $code): TemplateInterface;
    public function getTemplate(): ?string;
    public function setTemplate(?string $template): TemplateInterface;


    /**
     * @return null|BlockDefinition[]
     */
    public function getBlocks(): ?iterable;
    public function getBlock(string $code): ?BlockDefinition;
    public function getCollections(): ?array;
}

<?php

namespace WebEtDesign\CmsBundle\CMS\Template;


use WebEtDesign\CmsBundle\CMS\Configuration\BlockDefinition;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

interface ComponentInterface
{
    public function getLabel(): string;

    public function getCode(): ?string;

    public function setCode(?string $code): ComponentInterface;

    public function getTemplate(): ?string;

    public function setTemplate(?string $template): ComponentInterface;

    /**
     * @return null|BlockDefinition[]
     */
    public function getBlocks(): ?iterable;

    public function getBlock(string $code): ?BlockDefinition;

    public function getCollections(): ?array;

    public function getVarsBag(): ?CmsVarsBag;

    public function setVarsBag(?CmsVarsBag $varsBag): self;

    public function configureVars(CmsVarsBag $varsBag): void;
}

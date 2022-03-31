<?php

namespace WebEtDesign\CmsBundle\CmsTemplate;

interface PageInterface
{
    public function isSection(): bool;
    public function getMethods(): array;
    public function getController(): ?string;
    public function setController(?string $controller): PageInterface;
}

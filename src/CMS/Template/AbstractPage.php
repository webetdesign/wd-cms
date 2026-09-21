<?php

namespace WebEtDesign\CmsBundle\CMS\Template;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\CMS\Configuration\RouteDefinition;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Models\BreadcrumbItem;

abstract class AbstractPage extends AbstractComponent implements PageInterface
{
    public bool $section = false;

    public function getRoute(): ?RouteDefinition
    {
        return RouteDefinition::new();
    }

    /**
     * @return bool
     */
    public function isSection(): bool
    {
        return $this->section;
    }

    /**
     * @param bool $section
     * @return AbstractPage
     */
    public function setSection(bool $section): AbstractPage
    {
        $this->section = $section;

        return $this;
    }

    public function buildBreadcrumbItem(
        EntityManagerInterface $em,
        Request                $request,
        CmsPage                $page,
        callable               $generateUrl,
        string                 $defaultTitle): ?BreadcrumbItem
    {
        return new BreadcrumbItem($defaultTitle, $generateUrl($page->getRoute()->getName()));
    }
}

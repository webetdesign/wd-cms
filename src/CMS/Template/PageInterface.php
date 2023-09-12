<?php

namespace WebEtDesign\CmsBundle\CMS\Template;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\CMS\Configuration\RouteDefinition;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Models\BreadcrumbItem;
use WebEtDesign\CmsBundle\Vars\CmsVarsBag;

interface PageInterface
{
    public function isSection(): bool;

    public function getRoute(): ?RouteDefinition;

    public function buildBreadcrumbItem(
        EntityManagerInterface $em,
        Request                $request,
        CmsPage                $page,
        callable               $generateUrl,
        string                 $defaultTitle): ?BreadcrumbItem;
}

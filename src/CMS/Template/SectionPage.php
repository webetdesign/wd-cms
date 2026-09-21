<?php

namespace WebEtDesign\CmsBundle\CMS\Template;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Attribute\AsCmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Models\BreadcrumbItem;

#[AsCmsPage(code: self::code)]
class SectionPage extends AbstractPage
{
    const code = 'ADMIN_SECTION';

    public bool $section = true;

    protected ?string $label = 'Admin section';

    public function buildBreadcrumbItem(EntityManagerInterface $em,
                                        Request                $request,
                                        CmsPage                $page,
                                        callable               $generateUrl,
                                        string                 $defaultTitle): ?BreadcrumbItem
    {
        return null;
    }

}

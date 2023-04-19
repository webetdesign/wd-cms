<?php

namespace WebEtDesign\CmsBundle\CMS\Template;

use WebEtDesign\CmsBundle\Attribute\AsCmsPage;

#[AsCmsPage(code: self::code)]
class SectionPage extends AbstractPage
{
    const code = 'ADMIN_SECTION';

    public bool $section = true;

    protected ?string $label = 'Admin section';

}

<?php
declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Models;

class BreadcrumbItem
{

    public function __construct(public string $title, public string $url)
    {
    }

}

<?php

namespace WebEtDesign\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class BaseCmsController extends AbstractController
{
    /** @var CmsPage */
    protected $page;

    /** @var string */
    protected $locale;

    /** @var boolean */
    protected $granted;

    /** @var TemplateProvider */
    protected $provider;

    /**
     * BaseCmsController constructor.
     * @param TemplateProvider $provider
     */
    public function __construct(TemplateProvider $provider) { $this->provider = $provider; }


    protected function defaultRender(array $params)
    {
        /** @var CmsPage $page */
        $page = $this->getPage();

        return $this->render($this->provider->getTemplate($page->getTemplate()), [
            $params,
            [
                'page' => $page,
            ]
        ]);
    }

    /**
     * @return bool
     */
    public function isPageGranted(): bool
    {
        return $this->granted;
    }

    /**
     * @param bool $granted
     * @return CmsController
     */
    public function setGranted(bool $granted): BaseCmsController
    {
        $this->granted = $granted;
        return $this;
    }

    /**
     * @param CmsPage $page
     * @return CmsController
     */
    public function setPage(CmsPage $page): BaseCmsController
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @return CmsPage
     */
    public function getPage(): CmsPage
    {
        return $this->page;
    }

    /**
     * @param mixed $locale
     * @return CmsController
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }
}

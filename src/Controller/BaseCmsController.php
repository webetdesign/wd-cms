<?php

namespace WebEtDesign\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\GlobalVarsInterface;
use WebEtDesign\CmsBundle\Services\AbstractCmsGlobalVars;
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

    /** @var AbstractCmsGlobalVars */
    protected $globalVars;

    private $cmsConfig;

    public function setVarsObject(GlobalVarsInterface $object)
    {
        if ($this->globalVars) {
            $this->globalVars->setObject($object);
        }
    }

    /**
     * @param mixed $globalVars
     */
    public function setGlobalVars($globalVars): void
    {
        $this->globalVars = $globalVars;
    }

    /**
     * @return TemplateProvider
     */
    public function getProvider(): TemplateProvider
    {
        return $this->provider;
    }

    /**
     * @param TemplateProvider $provider
     */
    public function setProvider(TemplateProvider $provider): void
    {
        $this->provider = $provider;
    }

    protected function defaultRender(array $params)
    {
        /** @var CmsPage $page */
        $page       = $this->getPage();
        $baseParams = ['page' => $page];

        if ($this->getCmsConfig()['declination']) {
            $baseParams['declination'] = $this->getDeclination($page);
        }

        $extension = $this->getExtension();
        $rootDir   = $extension && $extension !== 'html' ? $extension . '/' : '';

        return $this->render($rootDir . $this->provider->getTemplate($page->getTemplate()), array_merge($params, $baseParams));
    }

    /**
     * @param CmsPage $page
     * @return CmsPageDeclination|null
     */
    public function getDeclination($page)
    {
        /** @var RequestStack $requestStack */
        $requestStack     = $this->get('request_stack');
        $request          = $requestStack->getCurrentRequest();
        $path             = $request->getRequestUri();
        $path             = preg_replace('(\?.*)', '', $path);
        $withoutExtension = $this->getCmsConfig()['page_extension'] ? preg_replace('/\.([a-z]+)$/', '', $path) : false;

        /** @var CmsPageDeclination $declination */
        foreach ($page->getDeclinations() as $declination) {
            if ($declination->getPath() == $path || $declination->getPath() === $withoutExtension) {
                return $declination;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    private function getExtension()
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request      = $requestStack->getCurrentRequest();
        $path         = $request->getRequestUri();

        preg_match('/\.([a-z]+)($|\?)/', $path, $extension);

        return isset($extension[1]) ? $extension[1] : null;
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
    public function setPage(?CmsPage $page): BaseCmsController
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return CmsPage
     */
    public function getPage(): ?CmsPage
    {
        return $this->page;
    }

    /**
     * @param mixed $locale
     * @return self
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

    /**
     * @return mixed
     */
    public function getCmsConfig()
    {
        return $this->cmsConfig;
    }

    /**
     * @param mixed $cmsConfig
     * @return BaseCmsController
     */
    public function setCmsConfig($cmsConfig)
    {
        $this->cmsConfig = $cmsConfig;

        return $this;
    }
}

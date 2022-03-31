<?php

namespace WebEtDesign\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\GlobalVarsInterface;
use WebEtDesign\CmsBundle\Factory\PageFactory;
use WebEtDesign\CmsBundle\Services\AbstractCmsGlobalVars;

class BaseCmsController extends AbstractController
{
    /** @var CmsPage|null */
    protected ?CmsPage $page;

    /** @var string */
    protected string $locale;

    /** @var boolean */
    protected bool $granted;

    /** @var AbstractCmsGlobalVars */
    protected AbstractCmsGlobalVars $globalVars;

    protected PageFactory $templateFactory;

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


    protected function defaultRender(array $params): Response
    {
        /** @var CmsPage $page */
        $page       = $this->getPage();
        $baseParams = ['page' => $page];

        if ($this->getCmsConfig()['declination']) {
            $baseParams['declination'] = $this->getDeclination($page);
        }

        $templateConfig = $this->templateFactory->get($page->getTemplate());

        return $this->render($templateConfig->getTemplate(), array_merge($params, $baseParams));
    }

    /**
     * @param CmsPage $page
     * @return CmsPageDeclination|null
     */
    public function getDeclination(CmsPage $page): ?CmsPageDeclination
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
    private function getExtension(): ?string
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request      = $requestStack->getCurrentRequest();
        $path         = $request->getRequestUri();

        preg_match('/\.([a-z]+)($|\?)/', $path, $extension);

        return $extension[1] ?? null;
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
     * @param PageFactory $templateFactory
     * @return BaseCmsController
     */
    public function setTemplateFactory(PageFactory $templateFactory): BaseCmsController
    {
        $this->templateFactory = $templateFactory;
        return $this;
    }

    /**
     * @return PageFactory
     */
    public function getTemplateFactory(): PageFactory
    {
        return $this->templateFactory;
    }

    /**
     * @param CmsPage|null $page
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
    public function setLocale($locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
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

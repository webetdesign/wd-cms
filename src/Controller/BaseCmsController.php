<?php

namespace WebEtDesign\CmsBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;

class BaseCmsController extends AbstractController
{
    protected ?ConfigurationInterface $configuration = null;

    /** @var CmsPage|null */
    protected ?CmsPage $page;

    /** @var string */
    protected string $locale;

    /** @var boolean */
    protected bool $granted;

    protected TemplateRegistry $templateRegistry;

    protected ?Response $response = null;

    private $cmsConfig;

    protected function defaultRender(array $params): Response
    {
        /** @var CmsPage $page */
        $page       = $this->getPage();
        $baseParams = ['page' => $page];

        if ($this->getCmsConfig()['declination']) {
            $baseParams['declination'] = $this->getDeclination($page);
        }

        $templateConfig = $this->templateRegistry->get($page->getTemplate());

        return $this->render(
            $templateConfig->getTemplate(),
            array_merge($params, $baseParams),
            $this->response ?: null
        );
    }

    public function addEsiHeaders($ttl)
    {
        $this->response = new Response();
        $this->response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        $this->response->setMaxAge($ttl);
        $this->response->setSharedMaxAge($ttl);
        $this->response->setPublic();
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
     * @param TemplateRegistry $templateRegistry
     * @return BaseCmsController
     */
    public function setTemplateRegistry(TemplateRegistry $templateRegistry): BaseCmsController
    {
        $this->templateRegistry = $templateRegistry;
        return $this;
    }

    /**
     * @return TemplateRegistry
     */
    public function getTemplateRegistry(): TemplateRegistry
    {
        return $this->templateRegistry;
    }

    /**
     * @param CmsPage|null $page
     * @return CmsController
     */
    public function setPage(?CmsPage $page): BaseCmsController
    {
        $this->configuration->setCurrentPage($page);
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

    /**
     * @return ConfigurationInterface|null
     */
    public function getConfiguration(): ?ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * @param ConfigurationInterface|null $configuration
     * @return BaseCmsController
     */
    public function setConfiguration(?ConfigurationInterface $configuration): BaseCmsController
    {
        $this->configuration = $configuration;
        return $this;
    }
}

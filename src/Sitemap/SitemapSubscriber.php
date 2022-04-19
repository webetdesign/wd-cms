<?php


namespace WebEtDesign\CmsBundle\Sitemap;


use JetBrains\PhpStorm\ArrayShape;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\GoogleMultilangUrlDecorator;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

class SitemapSubscriber implements EventSubscriberInterface
{

    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface  $urlGenerator;
    private CmsSiteRepository      $cmsSiteRepository;
    private ParameterBagInterface  $parameterBag;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param CmsSiteRepository $cmsSiteRepository
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        CmsSiteRepository $cmsSiteRepository,
        ParameterBagInterface $parameterBag
    ) {
        $this->urlGenerator      = $urlGenerator;
        $this->cmsSiteRepository = $cmsSiteRepository;
        $this->parameterBag      = $parameterBag;
    }

    #[ArrayShape([SitemapPopulateEvent::ON_SITEMAP_POPULATE => "string"])]
    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::ON_SITEMAP_POPULATE => 'populate',
        ];
    }

    /**
     * @param SitemapPopulateEvent $event
     */
    public function populate(SitemapPopulateEvent $event): void
    {
        $this->registerCmsPages($event->getUrlContainer());
    }

    /**
     * @param UrlContainerInterface $urls
     */
    public function registerCmsPages(UrlContainerInterface $urls): void
    {
        $cms_config = $this->parameterBag->get('wd_cms.cms');

        $sites = $this->cmsSiteRepository->findAll();

        foreach ($sites as $site) {
            if ($site->getHost() !== null) {
                $context = $this->urlGenerator->getContext();
                $context->setHost($site->getHost());
                $this->urlGenerator->setContext($context);
            }

            $pages = $site->getPages();

            foreach ($pages as $page) {
                /** @var CmsRoute $route */
                $route = $page->getRoute();
                if ($page->isActive() && $route) {
                    if (!$route->isDynamic()) {
                        $url = new UrlConcrete(
                            $this->urlGenerator->generate(
                                $route->getName(),
                                [],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                            $page->getUpdatedAt()
                        );

                        $decoratedUrl = new GoogleMultilangUrlDecorator($url);


                        if ($cms_config['multilingual']) {

                            foreach ($page->getCrossSitePages() as $crossSitePage) {
                                $crossRoute = $crossSitePage->getRoute();
                                if ($crossRoute && !$crossRoute->isDynamic()) {
                                    $decoratedUrl->addLink($this->urlGenerator->generate(
                                        $crossRoute->getName(),
                                        [],
                                        UrlGeneratorInterface::ABSOLUTE_URL
                                    ), $crossSitePage->getSite()->getLocale());
                                }
                            }
                        }

                        $urls->addUrl($decoratedUrl, $site->getSlug());
                    }
                }
            }
        }
    }
}

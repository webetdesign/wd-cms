<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-04
 * Time: 13:19
 */

namespace WebEtDesign\CmsBundle\EventListener;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;
use WebEtDesign\CmsBundle\CMS\ConfigurationInterface;
use WebEtDesign\CmsBundle\Controller\BaseCmsController;
use WebEtDesign\CmsBundle\Registry\TemplateRegistry;
use WebEtDesign\CmsBundle\Services\CmsHelper;

class CmsControllerListener
{

    protected CmsHelper              $helper;
    protected TemplateRegistry       $templateRegistry;
    protected Environment            $twig;
    protected array                  $cmsConfig;
    protected EntityManagerInterface $em;
    private ConfigurationInterface   $configuration;

    /**
     * CmsControllerListener constructor.
     * @param CmsHelper $cmsHelper
     * @param TemplateRegistry $templateRegistry
     * @param Environment $environment
     * @param ParameterBagInterface $parameterBag
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        CmsHelper $cmsHelper,
        TemplateRegistry $templateRegistry,
        Environment $environment,
        ParameterBagInterface $parameterBag,
        ConfigurationInterface $configuration,
    ) {
        $this->helper           = $cmsHelper;
        $this->templateRegistry = $templateRegistry;
        $this->cmsConfig        = $parameterBag->get('wd_cms.cms');
        $this->twig             = $environment;
        $this->configuration    = $configuration;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();
        if (is_array($controller)) {
            $controller = $controller[0];
        }
        $request = $event->getRequest();

        if ($controller instanceof BaseCmsController) {
            $controller->setConfiguration($this->configuration);

            if (!$this->helper->isGranted()) {
                $event->setController(function () {
                    $content = $this->twig->render('@WebEtDesignCms/page/page_access_denied.html.twig');
                    return new Response($content, 403);
                });
            }

            $page   = $this->helper->getPage();
            $locale = $this->helper->getLocale();
            if (!empty($locale)) {
                $request->setLocale($locale);
                $controller->setLocale($locale);
            }
            $controller->setPage($page);


            $controller->setGranted($this->helper->isGranted());
            $controller->setTemplateRegistry($this->templateRegistry);
            $controller->setCmsConfig($this->cmsConfig);

            $this->configuration->setCurrentPage($page);
            $this->configuration->autoPopulateVars();
        }
    }

}

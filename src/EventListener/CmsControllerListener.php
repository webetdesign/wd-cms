<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-04
 * Time: 13:19
 */

namespace WebEtDesign\CmsBundle\EventListener;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Twig\Environment;
use WebEtDesign\CmsBundle\Controller\BaseCmsController;
use WebEtDesign\CmsBundle\Factory\TemplateFactoryInterface;
use WebEtDesign\CmsBundle\Services\CmsHelper;

class CmsControllerListener
{

    protected CmsHelper                $helper;
    protected TemplateFactoryInterface $templateFactory;
    protected Environment              $twig;
    protected                          $globalVars;
    protected                          $cmsConfig;

    /**
     * CmsControllerListener constructor.
     * @param CmsHelper $cmsHelper
     * @param TemplateFactoryInterface $templateFactory
     * @param Container $container
     * @param $globalVarsDefinition
     * @param $cmsConfig
     * @param Environment $environment
     * @throws \Exception
     */
    public function __construct(
        CmsHelper $cmsHelper,
        TemplateFactoryInterface $templateFactory,
        Container $container,
        $globalVarsDefinition,
        $cmsConfig,
        Environment $environment
    ) {
        $this->helper          = $cmsHelper;
        $this->templateFactory = $templateFactory;
        $this->cmsConfig       = $cmsConfig;
        $this->twig            = $environment;
        if ($globalVarsDefinition['enable']) {
            $this->globalVars = $container->get($globalVarsDefinition['global_service']);
            $this->globalVars->setDelimiter($globalVarsDefinition['delimiter']);
        }
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();
        if (is_array($controller)) {
            $controller = $controller[0];
        }
        $request = $event->getRequest();

        if ($controller instanceof BaseCmsController) {

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
            $controller->setTemplateFactory($this->templateFactory);
            $controller->setCmsConfig($this->cmsConfig);

            if ($this->globalVars) {
                $controller->setGlobalVars($this->globalVars);
            }

        }
    }

}

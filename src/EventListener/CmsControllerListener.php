<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-04
 * Time: 13:19
 */

namespace WebEtDesign\CmsBundle\EventListener;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Contracts\EventDispatcher\Event;
use Twig\Environment;
use WebEtDesign\CmsBundle\Controller\BaseCmsController;
use WebEtDesign\CmsBundle\Controller\CmsController;
use WebEtDesign\CmsBundle\Services\CmsHelper;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsControllerListener
{

    protected $helper;
    protected $globalVars;
    protected $provider;
    protected $cmsConfig;
    protected $environment;

    /**
     * CmsControllerListener constructor.
     * @param CmsHelper $cmsHelper
     * @param TemplateProvider $provider
     * @param Container $container
     * @param $globalVarsDefinition
     * @throws \Exception
     */
    public function __construct(CmsHelper $cmsHelper, TemplateProvider $provider, Container $container, $globalVarsDefinition, $cmsConfig, Environment $environment)
    {
        $this->helper = $cmsHelper;
        $this->provider = $provider;
        $this->cmsConfig = $cmsConfig;
        $this->environment = $environment;
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

            if(!$this->helper->isGranted($request)){
                $event->setController(function() {
                    $content = $this->environment->render('@WebEtDesignCms/page/page_access_denied.html.twig');
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


            $controller->setGranted($this->helper->isGranted($request));
            $controller->setProvider($this->provider);
            $controller->setCmsConfig($this->cmsConfig);

            if ($this->globalVars) {
                $controller->setGlobalVars($this->globalVars);
            }

        }
    }

}

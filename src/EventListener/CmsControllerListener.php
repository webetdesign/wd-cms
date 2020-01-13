<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-04
 * Time: 13:19
 */

namespace WebEtDesign\CmsBundle\EventListener;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use WebEtDesign\CmsBundle\Controller\BaseCmsController;
use WebEtDesign\CmsBundle\Services\CmsHelper;
use WebEtDesign\CmsBundle\Services\TemplateProvider;

class CmsControllerListener
{

    protected $helper;

    protected $globalVars;
    /** @var TemplateProvider */
    protected $provider;

    protected $cmsConfig;

    /**
     * CmsControllerListener constructor.
     * @param CmsHelper $cmsHelper
     * @param TemplateProvider $provider
     * @param Container $container
     * @param $globalVarsDefinition
     * @throws \Exception
     */
    public function __construct(CmsHelper $cmsHelper, TemplateProvider $provider, Container $container, $globalVarsDefinition, $cmsConfig)
    {
        $this->helper = $cmsHelper;
        $this->provider = $provider;
        $this->cmsConfig = $cmsConfig;
        if ($globalVarsDefinition['enable']) {
            $this->globalVars = $container->get($globalVarsDefinition['global_service']);
            $this->globalVars->setDelimiter($globalVarsDefinition['delimiter']);
        }
    }

    public function onKernelController($event)
    {
        $controller = $event->getController();
        if (is_array($controller)) {
            $controller = $controller[0];
        }
        $request = $event->getRequest();

        if ($controller instanceof BaseCmsController) {

            $page   = $this->helper->getPage($request);
            $locale = $this->helper->getLocale($request);
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

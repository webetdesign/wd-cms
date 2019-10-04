<?php
/**
 * Created by PhpStorm.
 * User: Clement
 * Date: 2019-10-04
 * Time: 13:19
 */

namespace WebEtDesign\CmsBundle\EventListener;


use Symfony\Component\HttpKernel\Event\ControllerEvent;
use WebEtDesign\CmsBundle\Controller\BaseCmsController;
use WebEtDesign\CmsBundle\Services\CmsHelper;

class CmsControllerListener
{

    protected $helper;

    /**
     * CmsControllerListener constructor.
     * @param CmsHelper $cmsHelper
     */
    public function __construct(CmsHelper $cmsHelper)
    {
        $this->helper = $cmsHelper;
    }

    public function onKernelController(ControllerEvent $event)
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

        }
    }

}

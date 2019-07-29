<?php

namespace WebEtDesign\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsController extends Controller
{
    public function index(Request $request)
    {
        $helper = $this->get('cms.helper');

        if (!$helper->isGranted($request)) {
            return $this->forward('WebEtDesign\CmsBundle\Controller\CmsController::pageAccessDenied');
        };

        return $helper->getDefaultRender($request, [
            'controller_name' => 'CmsController',
        ]);
    }

    public function pageDisabled(Request $request)
    {
        $content = $this->renderView('@WebEtDesignCms/page/page_disabled.html.twig');
        return new Response($content, 404);
    }

    public function pageAccessDenied(Request $request)
    {
        $content = $this->renderView('@WebEtDesignCms/page/page_access_denied.html.twig');
        return new Response($content, 403);
    }
}

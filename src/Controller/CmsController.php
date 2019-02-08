<?php

namespace WebEtDesign\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsController extends Controller
{
    public function index(Request $request)
    {
        return $this->get('cms.helper')->getDefaultRender($request, [
            'controller_name' => 'CmsController',
        ]);
    }

    public function pageDisabled(Request $request)
    {
        $content = $this->renderView('cms/page_disabled.html.twig');
        return new Response($content, 404);
    }
}

<?php

namespace WebEtDesign\CmsBundle\Services;


use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use WebEtDesign\CmsBundle\Services\PageProvider;

class CmsHelper
{
    private $em;
    private $provider;
    private $twig;

    /**
     * @inheritDoc
     */
    public function __construct(EntityManager $em, PageProvider $provider, Twig_Environment $twig)
    {
        $this->em       = $em;
        $this->provider = $provider;
        $this->twig     = $twig;
    }

    public function getPage(Request $request)
    {
        $route = $this->em->getRepository(CmsRoute::class)->findOneBy(['name' => $request->attributes->get('_route')]);

        return $route ? $route->getPage() : null;
    }

    public function getDefaultRender(Request $request, array $params)
    {
        /** @var CmsPage $page */
        $page = $this->getPage($request);

        return new Response(
            $this->twig->render(
                $this->provider->getTemplate($page->getTemplate()),
                array_merge(
                    $params,
                    [
                        'page' => $page,
                    ]
                )
            )
        );
    }


}

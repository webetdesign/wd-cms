<?php

namespace WebEtDesign\CmsBundle\Services;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsRoute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;

class CmsHelper
{
    private $em;
    private $provider;
    private $twig;
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /**
     * @param EntityManagerInterface $em
     * @param TemplateProvider $provider
     * @param Twig_Environment $twig
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(EntityManagerInterface $em, TemplateProvider $provider, Twig_Environment $twig, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->em                   = $em;
        $this->provider             = $provider;
        $this->twig                 = $twig;
        $this->authorizationChecker = $authorizationChecker;
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

    public function isGranted(Request $request)
    {
        /** @var CmsPage $page */
        $page = $this->getPage($request);


        if (sizeof($page->getRoles()) < 1) {
            return true;
        }

        foreach ($page->getRoles() as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }

        return false;
    }


}

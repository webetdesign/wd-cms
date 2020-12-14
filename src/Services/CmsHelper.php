<?php

namespace WebEtDesign\CmsBundle\Services;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
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

    private $page;

    private $roleHierarchy;

    private $tokenStorage;

    /**
     * @param EntityManagerInterface $em
     * @param TemplateProvider $provider
     * @param Twig_Environment $twig
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        EntityManagerInterface $em,
        TemplateProvider $provider,
        Twig_Environment $twig,
        AuthorizationCheckerInterface $authorizationChecker,
        RoleHierarchyInterface  $roleHierarchy,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em                   = $em;
        $this->provider             = $provider;
        $this->twig                 = $twig;
        $this->authorizationChecker = $authorizationChecker;
        $this->roleHierarchy = $roleHierarchy;
        $this->tokenStorage = $tokenStorage;
    }

    public function getPage(Request $request)
    {
        if ($this->page === null) {
            $this->page = $this->em->getRepository(CmsPage::class)->findByRouteName($request->attributes->get('_route'));
        }
        return $this->page;
    }

    /**
     * @param Request $request
     * @param array $params
     * @return Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @deprecated since 1.2.0, use CmsController::defaultRender instead
     */
    public function getDefaultRender(Request $request, array $params)
    {
        /** @var CmsPage $page */
        $page = $this->getPage($request);

        $request->setLocale($this->getLocale($request));

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

    public function getLocale(Request $request)
    {
        /** @var CmsPage $page */
        $page = $this->getPage($request);

        if (!$page) {
            return null;
        }

        return $page->getSite() ? $page->getSite()->getLocale() : null;

    }

    public function isGranted(Request $request)
    {
        /** @var CmsPage $page */
        $page = $this->getPage($request);

        if (!$page) {
            return true;
        }


        if (sizeof($page->getRoles()) < 1) {
            return true;
        }

        $roles = $this->roleHierarchy->getReachableRoleNames($page->getRoles());

        foreach ($roles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }

        /**
         * Permet de mettre un role anonyme valable que pour les users non LOG. Ne marche pas avec IS_AUTHENTICATED_ANONYMOUSLY
         * @TODO : remove en SF5
         **/
        if ($this->tokenStorage->getToken() !== null){
            if (!in_array('IS_ANONYMOUS', $roles) || (in_array('IS_ANONYMOUS', $roles) && $this->tokenStorage->getToken()->getUser() === 'anon.')){
                return true;
            }
        }

        return false;
    }


}

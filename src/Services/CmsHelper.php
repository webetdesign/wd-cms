<?php

namespace WebEtDesign\CmsBundle\Services;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Twig\Environment;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Factory\PageFactory;

class CmsHelper
{
    private EntityManagerInterface $em;
    private PageFactory            $pageFactory;
    private Environment            $twig;
    /** @var AuthorizationCheckerInterface */
    private AuthorizationCheckerInterface $authorizationChecker;

    private $page;

    private RoleHierarchyInterface $roleHierarchy;

    private TokenStorageInterface $tokenStorage;
    private ParameterBagInterface $parameterBag;
    private RequestStack          $requestStack;

    /**
     * @param EntityManagerInterface $em
     * @param PageFactory $pageFactory
     * @param Environment $twig
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param RoleHierarchyInterface $roleHierarchy
     * @param TokenStorageInterface $tokenStorage
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $requestStack
     */
    public function __construct(
        EntityManagerInterface $em,
        PageFactory $pageFactory,
        Environment $twig,
        AuthorizationCheckerInterface $authorizationChecker,
        RoleHierarchyInterface $roleHierarchy,
        TokenStorageInterface $tokenStorage,
        ParameterBagInterface $parameterBag,
        RequestStack $requestStack
    ) {
        $this->em                   = $em;
        $this->pageFactory          = $pageFactory;
        $this->twig                 = $twig;
        $this->authorizationChecker = $authorizationChecker;
        $this->roleHierarchy        = $roleHierarchy;
        $this->tokenStorage         = $tokenStorage;
        $this->parameterBag         = $parameterBag;
        $this->requestStack         = $requestStack;
    }

    public function getPage()
    {
        $request = $this->getRequest();

        if ($this->page === null) {
            $this->page = $this->em->getRepository(CmsPage::class)->findByRouteName($request->attributes->get('_route'));
        }
        return $this->page;
    }

    public function getLocale(): ?string
    {
        /** @var CmsPage $page */
        $page = $this->getPage();

        if (!$page) {
            return null;
        }

        return $page->getSite() ? $page->getSite()->getLocale() : null;

    }

    public function isGranted(): bool
    {
        /** @var CmsPage $page */
        $page = $this->getPage();

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
        if ($this->tokenStorage->getToken() !== null) {
            if ((in_array('IS_ANONYMOUS',
                    $roles) && $this->tokenStorage->getToken()->getUser() === 'anon.')) {
                return true;
            }
        }

        return false;
    }

    protected function getBrowserLocale()
    {
        $request        = $this->getRequest();
        $browserLocales = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $locale = $request->getDefaultLocale();
        foreach ($browserLocales as $browserLocale) {
            $split = explode(';', $browserLocale);
            if (in_array($split[0], $this->parameterBag->get('locales'))) {
                $locale = $split[0];
                break;
            }
        }

        return $locale;
    }

    public function retrievePageByRouteName(string $route, ?string $locale = null): ?CmsPage
    {

        $cmsConfig = $this->parameterBag->get('wd_cms.cms');

        if (!$locale) {
            $locale = $this->getBrowserLocale();
        }

        /** @var QueryBuilder $qb */
        $qb = $this->em->getRepository(CmsPage::class)
            ->createQueryBuilder('p');
        $qb->innerJoin('p.route', 'r');
        $qb->innerJoin('p.site', 's');

        if ($cmsConfig['multilingual']) {
            $qb->andWhere('s.locale = :locale');
            $qb->setParameter('locale', $locale);
        }

        $qb->andWhere($qb->expr()->like('r.name', ':route'));
        $qb->setParameter('route', $route);

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }


}

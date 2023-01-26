<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebEtDesign\CmsBundle\Services;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Provider\MenuProviderInterface;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Event\ConfigureMenuEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

/**
 * Sonata menu builder.
 *
 * @author Martin Hasoň <martin.hason@gmail.com>
 * @author Alexandru Furculita <alex@furculita.net>
 */
final class SonataAdminCmsSidebarMenu
{
    private Pool $pool;

    private FactoryInterface $factory;

    private MenuProviderInterface $provider;

    private EventDispatcherInterface $eventDispatcher;

    private CmsSiteRepository $siteRepository;

    private RouterInterface $router;

    private RequestStack $requestStack;

    public function __construct(
        Pool $pool,
        FactoryInterface $factory,
        MenuProviderInterface $provider,
        EventDispatcherInterface $eventDispatcher,
        CmsSiteRepository $siteRepository,
        RouterInterface $router,
        RequestStack $requestStack,
    ) {
        $this->pool            = $pool;
        $this->factory         = $factory;
        $this->provider        = $provider;
        $this->eventDispatcher = $eventDispatcher;
        $this->siteRepository  = $siteRepository;
        $this->router          = $router;
        $this->requestStack    = $requestStack;
    }

    /**
     * Builds sidebar menu.
     */
    public function createSidebarMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $siteItemActif = false;
        foreach ($this->siteRepository->findBy(['visible' => true], ['label' => 'ASC']) as $site) {

            $extras = [
                'icon'               => '<i class="fa fa-puzzle-piece"></i>',
                'translation_domain' => 'SonataAdminBundle',
                //                'roles'              => $group['roles'],
                'sonata_admin'       => true,
            ];

            $subMenu = $menu->addChild($site->__toString())->setExtras($extras);

            $subMenu->addChild('Arborescence de page', [
                'uri' => $this->router->generate('admin_webetdesign_cms_cmssite_cmspage_tree', ['id' => $site->getId()])
            ])->setCurrent($this->isCurrent($site, 'cmspage'));

            $subMenu->addChild('Arborescence de menu', [
                'uri' => $this->router->generate('admin_webetdesign_cms_cmssite_cmsmenu_tree', ['id' => $site->getId()])
            ])->setCurrent($this->isCurrent($site, 'cmsmenu'));

            $subMenu->addChild('Blocs partagés', [
                'uri' => $this->router->generate('admin_webetdesign_cms_cmssite_cmssharedblock_list', ['id' => $site->getId()])
            ])->setCurrent($this->isCurrent($site, 'cmssharedblock'));

            if ($this->isCurrent($site, 'cmspage') || $this->isCurrent($site, 'cmsmenu') || $this->isCurrent($site, 'cmssharedblock')) {
                $siteItemActif = true;
            }
        }

        foreach ($this->pool->getAdminGroups() as $name => $group) {
            $extras = [
                'icon'               => $group['icon'],
                'translation_domain' => $group['translation_domain'],
                'label_catalogue'    => $group['label_catalogue'] ?? '', // NEXT_MAJOR: Remove this line.
                'roles'              => $group['roles'],
                'sonata_admin'       => true,
            ];

            $menuProvider = $group['provider'] ?? 'sonata_group_menu';

            $subMenu = $this->provider->get(
                $menuProvider,
                [
                    'name'  => $name,
                    'group' => $group,
                ]
            );

            foreach ($subMenu->getChildren() as $child) {
                if ($child->getUri() === '/admin/webetdesign/cms/cmssite/list' && $siteItemActif) {
                    $child->setCurrent(false);
                }
            }

            $subMenu = $menu->addChild($subMenu);
            $subMenu->setExtras(array_merge($subMenu->getExtras(), $extras));
        }

        $event = new ConfigureMenuEvent($this->factory, $menu);
        $this->eventDispatcher->dispatch($event, ConfigureMenuEvent::SIDEBAR);

        return $event->getMenu();
    }

    private function isCurrent(CmsSite $cmsSite, string $crud): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (preg_match('/^\/admin\/webetdesign\/cms\/cmssite\/' . $cmsSite->getId() . '\/' . $crud . '/', $request->getPathInfo())) {
            return true;
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Admin\BreadcrumbsBuilder;

use Knp\Menu\ItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\BreadcrumbsBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use WebEtDesign\CmsBundle\Entity\CmsSite;

/**
 * Stateless breadcrumbs builder (each method needs an Admin object).
 *
 * @author Grégoire Paris <postmaster@greg0ire.fr>
 */
final class PageBreadcrumbsBuilder implements BreadcrumbsBuilderInterface
{
    /**
     * @var string[]
     */
    private array $config;

    /**
     * @param string[] $config
     */
    public function __construct(array $config = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->config = $resolver->resolve($config);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'child_admin_route' => 'edit',
        ]);
    }

    public function getBreadcrumbs(AdminInterface $admin, $action): array
    {
        $breadcrumbs = [];
        if ($admin->isChild()) {
            return $this->getBreadcrumbs($admin->getParent(), $action);
        }

        $menu = $this->buildBreadcrumbs($admin, $action);

        do {
            $breadcrumbs[] = $menu;
        } while ($menu = $menu->getParent());

        $breadcrumbs = array_reverse($breadcrumbs);
        array_shift($breadcrumbs);

        return $breadcrumbs;
    }

    /**
     * NEXT_MAJOR : make this method private.
     * @param AdminInterface $admin
     * @param $action
     * @param ItemInterface|null $menu
     * @return ItemInterface
     */
    public function buildBreadcrumbs(
        AdminInterface $admin,
        $action,
        ?ItemInterface $menu = null
    ): ItemInterface {
        if (!$menu) {
            $menu = $admin->getMenuFactory()->createItem('root');

            $menu = $menu->addChild(
                'link_breadcrumb_dashboard',
                [
                    'uri'    => $admin->getRouteGenerator()->generate('sonata_admin_dashboard'),
                    'extras' => ['translation_domain' => 'SonataAdminBundle'],
                ]
            );
        }

        $menu = $this->createMenuItem(
            $admin,
            $menu,
            sprintf('%s_%s', $admin->getClassnameLabel(), 'tree'),
            $admin->getTranslationDomain()
        );

        if ('create' !== $action && 'tree' !== $action && $admin->hasSubject()) {
            $menu = $this->createMenuItem(
                $admin,
                $menu,
                $admin->getSubject()->getSite()->__toString(),
                $admin->getTranslationDomain()
            );

            $menu->setUri($admin->generateUrl('tree', ['id' => $admin->getSubject()->getSite()->getId()]));

            return $menu->addChild($admin->toString($admin->getSubject()), [
                'extras' => [
                    'translation_domain' => false,
                ],
            ]);
        } else {
            $site = $admin->getEntityManager()->getRepository(CmsSite::class)
                ->find($admin->getRequest()->attributes->get("id"));
            
            $menu = $this->createMenuItem(
                $admin,
                $menu,
                $site->__toString(),
                $admin->getTranslationDomain()
            );
        }


        return $menu;
    }

    /**
     * Creates a new menu item from a simple name. The name is normalized and
     * translated with the specified translation domain.
     *
     * @param AdminInterface $admin used for translation
     * @param ItemInterface $menu will be modified and returned
     * @param string $name the source of the final label
     * @param string|null $translationDomain for label translation
     * @param array<string, mixed> $options menu item options
     * @return ItemInterface
     */
    private function createMenuItem(
        AdminInterface $admin,
        ItemInterface $menu,
        string $name,
        ?string $translationDomain = null,
        array $options = []
    ): ItemInterface {
        $options = array_merge([
            'extras' => [
                'translation_domain' => $translationDomain,
            ],
        ], $options);

        return $menu->addChild(
            $admin->getLabelTranslatorStrategy()->getLabel(
                $name,
                'breadcrumb',
                'link'
            ),
            $options
        );
    }
}

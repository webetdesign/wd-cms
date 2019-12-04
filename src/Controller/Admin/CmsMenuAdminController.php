<?php

namespace WebEtDesign\CmsBundle\Controller\Admin;

use Knp\Menu\Renderer\TwigRenderer;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormRenderer;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Command\DebugCommand;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\MoveForm;

class CmsMenuAdminController extends CRUDController
{
    public function moveAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $object = $em->getRepository(CmsMenuItem::class)->find($id);

        $form = $this->createForm(MoveForm::class, $object, [
            'data_class' => CmsMenuItem::class,
            'object'     => $object,
            'action'     => $this->admin->generateObjectUrl('move', $object)
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CmsMenuItem $object */
            $object = $form->getData();

            $this->moveItems($object);

            return $this->redirect($this->admin->generateUrl('tree',[
                'id'   => $object->getRoot()->getSite()->getId(),
                '_tab' => 'tab_' . $object->getMenuCode()
            ]));
        }

        if ($request->isXmlHttpRequest()) {
            return $this->renderWithExtraParams('@WebEtDesignCms/admin/nestedTreeMoveAction/moveForm.html.twig', [
                'form'   => $form->createView(),
                'object' => $object
            ]);
        }

        return $this->renderWithExtraParams('@WebEtDesignCms/admin/nestedTreeMoveAction/move.html.twig', [
            'form'   => $form->createView(),
            'object' => $object
        ]);
    }

    public function generateFromPageAction($id = null)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var CmsSite $site */
        if ($id == null) {
            $site = $this->em->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
        } else {
            $site  = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->find($id);
        }
        $pages = $em->getRepository('WebEtDesignCmsBundle:CmsPage')->getPagesBySite($site);
        $rootPage = $site->getRootPage();

        $menu = $em->getRepository('WebEtDesignCmsBundle:CmsMenu')->findOneBy(['site' => $site, 'type' => CmsMenuTypeEnum::PAGE_ARBO]);

        if ($menu) {
            dump('xx');
            die();
        } else {
            $menu = new CmsMenu();
            $menu->setLabel($site->getLabel());
            $menu->setCode((!empty($site->getTemplateFilter()) ? $site->getTemplateFilter() .'_' : "") . 'main_arbo' );
            $menu->setType(CmsMenuTypeEnum::PAGE_ARBO);
            $menu->setSite($site);
            $menu->initRoot = false;
            $em->persist($menu);
        }

        $root = new CmsMenuItem();
        $root->setName('root ' . $menu->getSite() . " " . $menu->getCode());
        $root->setMenu($menu);
        $em->persist($root);


        $items = [];
        /** @var CmsPage $page */
        foreach ($pages as $page) {
            if ($page->getLvl() === 0) {
                continue;
            }
            $items[$page->getId()] = new CmsMenuItem();
            $items[$page->getId()]->setMenu($menu);

            $items[$page->getId()]->setIsVisible($page->isActive());
            $items[$page->getId()]->setLinkType(CmsMenuLinkTypeEnum::CMS_PAGE);
            $items[$page->getId()]->setPage($page);

            $items[$page->getId()]->setName($page->getTitle());
            if ($page->getParent()->getId() === $rootPage->getId()) {
                $items[$page->getId()]->setParent($root);
            } else {
                $items[$page->getId()]->setParent($items[$page->getParent()->getId()]);
            }

            $em->persist($items[$page->getId()]);

        }
        $em->flush();

        return $this->redirectToList();

    }

    public function treeAction($id)
    {
        $em = $this->getDoctrine();
        $datagrid = $this->admin->getDatagrid();

        if ($id === null) {
            $defaultSite = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
            if (!$defaultSite) {
                $this->addFlash('warning', 'Vous devez déclarer un site par défaut');

                return $this->redirect($this->get('cms.admin.cms_site')->generateUrl('list'));
            }

            $id = $defaultSite->getId();
        }

        if ($id) {
            $datagrid->setValue('site', null, $id);

            $rp = $em->getRepository('WebEtDesignCmsBundle:CmsMenuItem');
            $qb = $rp->createQueryBuilder('mi');

            $qb
                ->select(['mi', 'm'])
                ->leftJoin('mi.menu', 'm')
                ->getQuery()->getResult();


            $qb = $rp->createQueryBuilder('mi');

            $qb
                ->select(['mi', 'c'])
                ->leftJoin('mi.children', 'c')
                ->getQuery()->getResult();


            $qb = $rp->createQueryBuilder('mi');

            $qb
                ->select(['mi', 'p', 'r'])
                ->leftJoin('mi.page', 'p')
                ->leftJoin('p.route', 'r')
                ->getQuery()->getResult();
        }

        $formView = $datagrid->getForm()->createView();

        return $this->renderWithExtraParams('@WebEtDesignCms/admin/menu/tree.html.twig', [
            'action'     => 'tree',
            'form'       => $formView,
            'datagrid'   => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            //            'export_formats' => $this->has('sonata.admin.admin_exporter') ?
            //                $this->get('sonata.admin.admin_exporter')->getAvailableFormats($this->admin) :
            //                $this->admin->getExportFormats(),
        ], null);
    }

    public function listAction($id = null)
    {
        $request = $this->getRequest();

        if ($id === null) {
            $defaultSite = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
            if (!$defaultSite) {
                $this->addFlash('warning', 'Vous devez déclarer un site par défaut');

                return $this->redirect($this->get('cms.admin.cms_site')->generateUrl('list'));
            }

            $id = $defaultSite->getId();
            $request->attributes->set('id', $id);
        }

        return $this->redirect($this->admin->generateUrl('tree', ['id' => $id]));

        $request = $this->getRequest();

        $this->admin->checkAccess('list');

        $preResponse = $this->preList($request);
        if (null !== $preResponse) {
            return $preResponse;
        }

        if ($listMode = $request->get('_list_mode')) {
            $this->admin->setListMode($listMode);
        }

        $datagrid = $this->admin->getDatagrid();
        if ($id) {
            $datagrid->setValue('site', null, $id);
        }
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFilterTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplate('list');
        // $template = $this->templateRegistry->getTemplate('list');

        $root = $this->getDoctrine()->getRepository('CmsMenuItem')->getByCode('root');

        $menuRepo = $this->getDoctrine()->getRepository('CmsMenuItem');

        $rootNodes = $menuRepo->findRoot();
        $sites     = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->findSitesMenu();

        return $this->renderWithExtraParams($template, [
            'action'         => 'list',
            'form'           => $formView,
            'root'           => $root,
            'datagrid'       => $datagrid,
            'rootNodes'      => $rootNodes,
            'sites'          => $sites,
            'csrf_token'     => $this->getCsrfToken('sonata.batch'),
            'export_formats' => $this->has('sonata.admin.admin_exporter') ?
                $this->get('sonata.admin.admin_exporter')->getAvailableFormats($this->admin) :
                $this->admin->getExportFormats(),
        ], null);
    }

    /**
     * @inheritDoc
     */
    public function createAction($id = null)
    {
        if ($id === null) {
            $site = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
        } else {
            $site = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->find($id);
        }

        $request = $this->getRequest();
        // the key used to lookup the template
        $templateKey = 'edit';

        $this->admin->checkAccess('create');

        $class = new \ReflectionClass($this->admin->hasActiveSubClass() ? $this->admin->getActiveSubClass() : $this->admin->getClass());

        if ($class->isAbstract()) {
            return $this->renderWithExtraParams(
                '@SonataAdmin/CRUD/select_subclass.html.twig',
                [
                    'base_template' => $this->getBaseTemplate(),
                    'admin' => $this->admin,
                    'action' => 'create',
                ],
                null
            );
        }

        $newObject = $this->admin->getNewInstance();
        $newObject->setSite($site);

        $preResponse = $this->preCreate($request, $newObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($newObject);

        $form = $this->admin->getForm();

        if (!\is_array($fields = $form->all()) || 0 === \count($fields)) {
            throw new \RuntimeException(
                'No editable field defined. Did you forget to implement the "configureFormFields" method?'
            );
        }

        $form->setData($newObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode() || $this->isPreviewApproved())) {
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);
                $this->admin->checkAccess('create', $submittedObject);

                try {
                    $newObject = $this->admin->create($submittedObject);

                    if ($this->isXmlHttpRequest()) {
                        return $this->renderJson([
                            'result' => 'ok',
                            'objectId' => $this->admin->getNormalizedIdentifier($newObject),
                            'objectName' => $this->escapeHtml($this->admin->toString($newObject)),
                        ], 200, []);
                    }

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->trans(
                            'flash_create_success',
                            ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                            'SonataAdminBundle'
                        )
                    );

                    // redirect to edit mode
                    return $this->redirectTo($newObject);
                } catch (ModelManagerException $e) {
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if (!$this->isXmlHttpRequest()) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->trans(
                            'flash_create_error',
                            ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                            'SonataAdminBundle'
                        )
                    );
                }
            } elseif ($this->isPreviewRequested()) {
                // pick the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $formView = $form->createView();
        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplate($templateKey);
        // $template = $this->templateRegistry->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action' => 'create',
            'form' => $formView,
            'object' => $newObject,
            'objectId' => null,
        ], null);
    }


    public function customRedirectToList($inEdit)
    {
        $parameters = [];

        if ($filter = $this->admin->getFilterParameters()) {
            $parameters['filter'] = $filter;
        }

        $parameters['in_edit'] = $inEdit;

        return $this->redirect($this->admin->generateUrl('list', $parameters));
    }

    /**
     * @inheritDoc
     */
    protected function redirectTo($object)
    {
        $request = $this->getRequest();

        $url = false;

        if (null !== $request->get('btn_update_and_list')) {
            return $this->customRedirectToList($object->getId());
        }
        if (null !== $request->get('btn_create_and_list')) {
            return $this->customRedirectToList($object->getId());
        }

        if (null !== $request->get('btn_create_and_create')) {
            $params = [];
            if ($this->admin->hasActiveSubClass()) {
                $params['subclass'] = $request->get('subclass');
            }
            $url = $this->admin->generateUrl('create', $params);
        }

        if ('DELETE' === $this->getRestMethod()) {
            return $this->redirectToList();
        }

        if (!$url) {
            foreach (['edit', 'show'] as $route) {
                if ($this->admin->hasRoute($route) && $this->admin->hasAccess($route, $object)) {
                    $url = $this->admin->generateObjectUrl($route, $object);

                    break;
                }
            }
        }

        if (!$url) {
            return $this->redirectToList();
        }

        return new RedirectResponse($url);
    }

    protected function moveItems($submittedObject)
    {
        $cmsReop = $this->getDoctrine()->getRepository('CmsMenuItem');

        switch ($submittedObject->getMoveMode()) {
            case 'persistAsFirstChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsReop->persistAsFirstChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsLastChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsReop->persistAsLastChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsNextSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsReop->persistAsNextSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsPrevSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsReop->persistAsPrevSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsPrevSibling($submittedObject);
                }
                break;
        }

        $this->getDoctrine()->getManager()->flush();
    }

    protected function setFormTheme(FormView $formView, array $theme = null): void
    {
        $twig = $this->get('twig');

        // BC for Symfony < 3.4 where runtime should be TwigRenderer
        if (!method_exists(DebugCommand::class, 'getLoaderPaths')) {
            $twig->getRuntime(TwigRenderer::class)->setTheme($formView, $theme);

            return;
        }

        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $theme);
    }


}

<?php

namespace WebEtDesign\CmsBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Menu\Renderer\TwigRenderer;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Form\MoveForm;

class CmsPageAdminController extends CRUDController
{
    protected function preList(Request $request)
    {
        $request->request->set('site', $request->attributes->get('id'));
    }

    public function moveAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $object = $em->getRepository(CmsPage::class)->find($id);

        $form = $this->createForm(MoveForm::class, $object, [
            'data_class' => CmsPage::class,
            'object'     => $object,
            'action'     => $this->admin->generateObjectUrl('move', $object)
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CmsPage $object */
            $object = $form->getData();

            $this->moveItems($object);

            return $this->redirect($this->admin->generateUrl('tree', [
                'id' => $object->getRoot()->getSite()->getId(),
            ]));
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'label'        => $object->getTitle(),
                'modalContent' => $this->renderView('@WebEtDesignCms/admin/nestedTreeMoveAction/moveForm.html.twig', [
                    'admin'  => $this->admin,
                    'form'   => $form->createView(),
                    'object' => $object
                ])
            ]);
        }

        return $this->renderWithExtraParams('@WebEtDesignCms/admin/nestedTreeMoveAction/move.html.twig', [
            'form'   => $form->createView(),
            'object' => $object
        ]);
    }

    public function treeAction($id = null)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine();
        if ($id === null) {
            $defaultSite = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
            if (!$defaultSite) {
                $this->addFlash('warning', 'Vous devez déclarer un site par défaut');

                return $this->redirect($this->get('cms.admin.cms_site')->generateUrl('list'));
            }

            $id = $defaultSite->getId();
        }

        $datagrid = $this->admin->getDatagrid();

        if ($id) {
            $datagrid->setValue('site', null, $id);

            $rp = $em->getRepository('WebEtDesignCmsBundle:CmsPage');
            $qb = $rp->createQueryBuilder('p');

            $qb
                ->select(['p', 'r'])
                ->leftJoin('p.route', 'r')
                ->andWhere(
                    $qb->expr()->eq('p.site', $id)
                )
                ->getQuery()->getResult();

            $qb = $rp->createQueryBuilder('p');

            $qb
                ->select(['PARTIAL p.{id}', 'd'])
                ->leftJoin('p.declinations', 'd')
                ->andWhere(
                    $qb->expr()->eq('p.site', $id)
                )
                ->getQuery()->getResult();

            $qb = $rp->createQueryBuilder('p');

            $qb
                ->select(['PARTIAL p.{id}', 'c'])
                ->leftJoin('p.children', 'c')
                ->andWhere(
                    $qb->expr()->eq('p.site', $id)
                )
                ->getQuery()->getResult();
        }

        $formView = $datagrid->getForm()->createView();

        return $this->renderWithExtraParams('@WebEtDesignCms/admin/page/tree.html.twig', [
            'action'     => 'tree',
            'form'       => $formView,
            'datagrid'   => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            //            'export_formats' => $this->has('sonata.admin.admin_exporter') ?
            //                $this->get('sonata.admin.admin_exporter')->getAvailableFormats($this->admin) :
            //                $this->admin->getExportFormats(),
        ], null);
    }

    /**
     * @inheritDoc
     */
    public function listAction($id = null)
    {
        $request = $this->getRequest();

        if ($this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault() == null) {
            $this->addFlash('warning', 'Vous devez déclarer un site par défaut');

            return $this->redirect($this->get('cms.admin.cms_site')->generateUrl('list'));
        }

        if (!$request->get('filter')) {
            return new RedirectResponse($this->admin->generateUrl('tree'));
        }

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
        $twig = $this->get('twig');
        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $this->admin->getFilterTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplate('list');

        // $template = $this->templateRegistry->getTemplate('list');

        return $this->renderWithExtraParams($template, [
            'action'         => 'list',
            'form'           => $formView,
            'datagrid'       => $datagrid,
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
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine();
        if ($id === null) {
            $site = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
        } else {
            $site = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->find($id);
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
                    'admin'         => $this->admin,
                    'action'        => 'create',
                ],
                null
            );
        }

        /** @var CmsPage $newObject */
        $newObject = $this->admin->getNewInstance();
        $newObject->setSite($site);

        if ($request->query->has('siteId')) {
            $site = $this->getDoctrine()->getRepository($this->getParameter('wd_cms.admin.config.entity.site'))->find($request->query->get('siteId'));
            /** @var CmsPage $refPage */
            $refPage = $this->getDoctrine()->getRepository($this->getParameter('wd_cms.admin.config.entity.page'))->find($request->query->get('refId'));

            $newObject->setSite($site);
            $newObject->addCrossSitePage($refPage);
            $newObject->setTemplate($refPage->getTemplate());
            $newObject->setRoles($refPage->getRoles());

            foreach ($refPage->getCrossSitePages() as $crossSitePage) {
                $newObject->addCrossSitePage($crossSitePage);
            }

            $refPage->addCrossSitePage($newObject);
            $em = $this->getDoctrine()->getManager();
            $em->persist($refPage);
        }

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

                    $this->moveItems($submittedObject);

                    if ($this->isXmlHttpRequest()) {
                        return $this->renderJson([
                            'result'     => 'ok',
                            'objectId'   => $this->admin->getNormalizedIdentifier($newObject),
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
        $twig = $this->get('twig');
        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $this->admin->getFormTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplate($templateKey);

        // $template = $this->templateRegistry->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action'   => 'create',
            'form'     => $formView,
            'object'   => $newObject,
            'objectId' => null,
        ], null);
    }

    /**
     * @inheritDoc
     */
    public function editAction($id = null)
    {
        $request = $this->getRequest();
        // the key used to lookup the template
        $templateKey = 'edit';

        $id = $request->get($this->admin->getIdParameter());
        /** @var CmsPage $existingObject */
        $existingObject = $this->admin->getObject($id);

        if (!$existingObject) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->admin->checkAccess('edit', $existingObject);

        $preResponse = $this->preEdit($request, $existingObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($existingObject);
        $objectId = $this->admin->getNormalizedIdentifier($existingObject);

        $form = $this->admin->getForm();

        if (!\is_array($fields = $form->all()) || 0 === \count($fields)) {
            throw new \RuntimeException(
                'No editable field defined. Did you forget to implement the "configureFormFields" method?'
            );
        }

        $form->setData($existingObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode() || $this->isPreviewApproved())) {
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);

                try {
                    $existingObject = $this->admin->update($submittedObject);

                    $this->moveItems($submittedObject);

                    if ($this->isXmlHttpRequest()) {
                        return $this->renderJson([
                            'result'     => 'ok',
                            'objectId'   => $objectId,
                            'objectName' => $this->escapeHtml($this->admin->toString($existingObject)),
                        ], 200, []);
                    }

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->trans(
                            'flash_edit_success',
                            ['%name%' => $this->escapeHtml($this->admin->toString($existingObject))],
                            'SonataAdminBundle'
                        )
                    );

                    // redirect to edit mode
                    return $this->redirectTo($existingObject);
                } catch (ModelManagerException $e) {
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                } catch (LockException $e) {
                    $this->addFlash('sonata_flash_error', $this->trans('flash_lock_error', [
                        '%name%'       => $this->escapeHtml($this->admin->toString($existingObject)),
                        '%link_start%' => '<a href="' . $this->admin->generateObjectUrl('edit', $existingObject) . '">',
                        '%link_end%'   => '</a>',
                    ], 'SonataAdminBundle'));
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if (!$this->isXmlHttpRequest()) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->trans(
                            'flash_edit_error',
                            ['%name%' => $this->escapeHtml($this->admin->toString($existingObject))],
                            'SonataAdminBundle'
                        )
                    );
                }
            } elseif ($this->isPreviewRequested()) {
                // enable the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $formView = $form->createView();
        // set the theme for the current Admin Form
        //        $this->setFormTheme($formView, $this->admin->getFormTheme());
        $twig      = $this->get('twig');
        $formTheme = array_merge($this->admin->getFormTheme(), [
            '@WebEtDesignCms/form/cms_multilingual_type.html.twig'
        ]);
        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $formTheme);

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplate($templateKey);

        // $template = $this->templateRegistry->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action'   => 'edit',
            'form'     => $formView,
            'object'   => $existingObject,
            'objectId' => $objectId,
        ], null);
    }

    /**
     * @inheritDoc
     */
    protected function preDelete(Request $request, $object)
    {
        if ($object->isRoot()) {
            $this->addFlash('error', "Vous ne pouvez supprimer la page d'accueil");

            return $this->redirect($this->admin->generateUrl('tree', ['id' => $object->getSite()->getId()]));
        }

        return null;
    }

    protected function moveItems($submittedObject)
    {
        $CmsRepo = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsPage');

        switch ($submittedObject->getMoveMode()) {
            case 'persistAsFirstChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsFirstChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $CmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsLastChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsLastChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $CmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsNextSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsNextSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $CmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsPrevSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsPrevSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $CmsRepo->persistAsPrevSibling($submittedObject);
                }
                break;
        }

        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * @inheritDoc
     */
    protected function redirectTo($object)
    {
        $request = $this->getRequest();

        $url = false;

        if (null !== $request->get('btn_update_and_list')) {
            return $this->redirectToTree();
        }
        if (null !== $request->get('btn_create_and_list')) {
            return $this->redirectToTree();
        }

        if (null !== $request->get('btn_create_and_create')) {
            $params = [];
            if ($this->admin->hasActiveSubClass()) {
                $params['subclass'] = $request->get('subclass');
            }
            $url = $this->admin->generateUrl('create', $params);
        }

        if ('DELETE' === $this->getRestMethod()) {
            return $this->redirectToTree();
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
            return $this->redirectToTree();
        } else {
            if (sizeof($request->query->all()) > 0) {
                $url .= '?' . http_build_query($request->query->all());
            }
        }

        return new RedirectResponse($url);
    }

    public function redirectToTree()
    {
        return $this->redirect($this->admin->generateUrl('tree'));
    }

}

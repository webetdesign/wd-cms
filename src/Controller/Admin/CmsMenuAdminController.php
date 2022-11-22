<?php

namespace WebEtDesign\CmsBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use LogicException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use WebEtDesign\CmsBundle\Admin\BreadcrumbsBuilder\MenuBreadcrumbsBuilder;
use WebEtDesign\CmsBundle\Entity\CmsMenu;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Entity\CmsMenuLinkTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsMenuTypeEnum;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\MoveForm;
use function count;
use function is_array;

class CmsMenuAdminController extends CRUDController
{
    private RequestStack $requestStack;

    /**
     * CmsMenuAdminController constructor.
     * @param RequestStack $requestStack
     */
    public function __construct(
        RequestStack $requestStack,
        protected EntityManagerInterface $em,
        protected Pool $pool
    ) {
        $this->requestStack = $requestStack;
    }

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

            return $this->redirect($this->admin->generateUrl('tree', [
                'id'   => $object->getRoot()->getSite()->getId(),
                '_tab' => 'tab_' . $object->getMenu() ? $object->getMenu()->getCode() : uniqid()
            ]));
        }

        if ($request->isXmlHttpRequest()) {
            return $this->renderWithExtraParams('@WebEtDesignCms/admin/nestedTreeMoveAction/moveForm.html.twig',
                [
                    'form'   => $form->createView(),
                    'object' => $object
                ]);
        }

        return $this->renderWithExtraParams('@WebEtDesignCms/admin/nestedTreeMoveAction/move.html.twig',
            [
                'form'   => $form->createView(),
                'object' => $object
            ]);
    }

    public function treeAction($id): RedirectResponse|Response
    {
        $em       = $this->getDoctrine();
        $datagrid = $this->admin->getDatagrid();
        $request  = $this->requestStack->getCurrentRequest();
        $session  = $request->getSession();

        if ($id === null) {
            if ($session->get('admin_current_site_id')) {
                $id = $session->get('admin_current_site_id');
            } else {
                $defaultSite = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
                if (!$defaultSite) {
                    $this->addFlash('warning', 'Vous devez déclarer un site par défaut');

                    return $this->redirect($this->get('cms.admin.cms_site')->generateUrl('list'));
                }

                $id = $defaultSite->getId();
            }
            $request->attributes->set('id', $id);
        }

        if ($id) {
            $session->set('admin_current_site_id', $id);
            $datagrid->setValue('site', null, $id);

            $rp = $em->getRepository(CmsMenuItem::class);
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
                ->select(['mi', 'p', 'r', 'c'])
                ->leftJoin('mi.page', 'p')
                ->leftJoin('p.route', 'r')
                ->leftJoin('p.contents', 'c')
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

    public function listAction(Request $request): Response
    {
        $defaultSite = $this->em->getRepository(CmsSite::class)->getDefault();
        if ($defaultSite === null) {
            $this->addFlash('warning', 'Vous devez déclarer un site par défaut');
            return $this->redirect($this->pool->getAdminByAdminCode('cms.admin.cms_site')->generateUrl('list'));
        }

        try {
            $parent = $this->admin->getParent();
        } catch (LogicException $e) {
            $parent = null;
        }

        $session = $request->getSession();

        if (!$parent) {
            $site = null;
            if ($session->get('admin_current_site_id')) {
                $id = $session->get('admin_current_site_id');
                $site = $this->em->find(CmsSite::class, $id);
            }

            if ($site === null) {
                $site = $defaultSite;
            }
        } else {
            $site = $this->admin->getParent()->getSubject();
        }

        $siteAdmin = $this->pool->getAdminByClass(CmsSite::class);
        $url = $siteAdmin->generateUrl('cms.admin.cms_menu.tree', ['id' => $site->getId()]);

        return $this->redirect($url);
    }

    /**
     * @param null $id
     * @return Response
     * @throws ReflectionException
     * @author Benjamin Robert
     */
    public function createAction($id = null): Response
    {

        if ($id === null) {
            $site = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
        } else {
            $site = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->find($id);
        }

        $request = $this->requestStack->getCurrentRequest();
        // the key used to lookup the template
        $templateKey = 'edit';

        $this->admin->checkAccess('create');

        $class = new ReflectionClass($this->admin->hasActiveSubClass() ? $this->admin->getActiveSubClass() : $this->admin->getClass());

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

        $newObject = $this->admin->getNewInstance();
        $newObject->setSite($site);

        $preResponse = $this->preCreate($request, $newObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($newObject);

        $form = $this->admin->getForm();

        if (!is_array($fields = $form->all()) || 0 === count($fields)) {
            throw new RuntimeException(
                'No editable field defined. Did you forget to implement the "configureFormFields" method?'
            );
        }

        $form->setData($newObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode($this->requestStack->getCurrentRequest()) || $this->isPreviewApproved($this->requestStack->getCurrentRequest()))) {
                /** @var CmsMenu $submittedObject */
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);
                $this->admin->checkAccess('create', $submittedObject);

                try {
                    $newObject = $this->admin->create($submittedObject);

                    if ($this->isXmlHttpRequest($this->requestStack->getCurrentRequest())) {
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
                    return $this->redirectTo($this->requestStack->getCurrentRequest(), $newObject);
                } catch (ModelManagerException $e) {
                    try {
                        $this->handleModelManagerException($e);
                    } catch (Exception $e) {
                    }

                    $isFormValid = false;
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if (!$this->isXmlHttpRequest($this->requestStack->getCurrentRequest())) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->trans(
                            'flash_create_error',
                            ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                            'SonataAdminBundle'
                        )
                    );
                }
            } elseif ($this->isPreviewRequested($this->requestStack->getCurrentRequest())) {
                // pick the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $formView = $form->createView();
        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        $template = $this->admin->getTemplateRegistry()->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action'   => 'create',
            'form'     => $formView,
            'object'   => $newObject,
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
    protected function redirectTo(Request $request, $object): RedirectResponse
    {
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

        if ('DELETE' === $this->requestStack->getCurrentRequest()->getMethod()) {
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
                    $cmsReop->persistAsFirstChildOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsLastChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsReop->persistAsLastChildOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsNextSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsReop->persistAsNextSiblingOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsPrevSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsReop->persistAsPrevSiblingOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $cmsReop->persistAsPrevSibling($submittedObject);
                }
                break;
        }

        $this->getDoctrine()->getManager()->flush();
    }

}

<?php

namespace WebEtDesign\CmsBundle\Controller\Admin;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Exception\LockException;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Sonata\AdminBundle\Form\Type\Operator\ContainsOperatorType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use WebEtDesign\CmsBundle\Admin\CmsPageDeclinationAdmin;
use WebEtDesign\CmsBundle\Entity\CmsContent;
use WebEtDesign\CmsBundle\Entity\CmsPage;
use WebEtDesign\CmsBundle\Entity\CmsPageDeclination;
use WebEtDesign\CmsBundle\Entity\CmsSite;
use WebEtDesign\CmsBundle\Form\MoveForm;
use function count;
use function is_array;

class CmsPageAdminController extends CRUDController
{

    private RequestStack            $requestStack;
    private CmsPageDeclinationAdmin $declinationAdmin;
    private Pool                    $pool;
    private EntityManagerInterface  $em;

    /**
     * @param RequestStack $requestStack
     * @param CmsPageDeclinationAdmin $declinationAdmin
     * @param Pool $pool
     */
    public function __construct(
        RequestStack $requestStack,
        CmsPageDeclinationAdmin $declinationAdmin,
        Pool $pool,
        EntityManagerInterface $entityManager,
    ) {
        $this->requestStack           = $requestStack;
        $this->declinationAdmin       = $declinationAdmin;
        $this->pool                   = $pool;
        $this->em                     = $entityManager;
    }

    protected function preList(Request $request): ?Response
    {
        $request->request->set('site', $request->attributes->get('id'));
        return null;
    }

    public function moveAction(Request $request, $childId)
    {
        $em = $this->getDoctrine()->getManager();

        $object = $em->getRepository(CmsPage::class)->find($childId);

        $object->setMoveTarget($object->getRoot());

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
                'modalContent' => $this->renderView('@WebEtDesignCms/admin/nestedTreeMoveAction/moveForm.html.twig',
                    [
                        'admin'  => $this->admin,
                        'form'   => $form->createView(),
                        'object' => $object
                    ])
            ]);
        }

        return $this->renderWithExtraParams('@WebEtDesignCms/admin/nestedTreeMoveAction/move.html.twig',
            [
                'form'   => $form->createView(),
                'object' => $object
            ]);
    }

    public function treeAction($id = null)
    {

        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        $datagrid = $this->admin->getDatagrid();

        if ($id) {
            $session->set('admin_current_site_id', $id);

            $rp = $this->em->getRepository(CmsPage::class);
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


        return $this->renderWithExtraParams($this->admin->getTemplateRegistry()->getTemplate('tree'),
            [
                'action'           => 'tree',
                'declinationAdmin' => $this->declinationAdmin,
                'form'             => $formView,
                'datagrid'         => $datagrid,
                'csrf_token'       => $this->getCsrfToken('sonata.batch'),
                //            //            'export_formats' => $this->has('sonata.admin.admin_exporter') ?
                //            //                $this->get('sonata.admin.admin_exporter')->getAvailableFormats($this->admin) :
                //            //                $this->admin->getExportFormats(),
            ], null);
    }

    /**
     * @inheritDoc
     */
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
        $url = $siteAdmin->generateUrl('cms.admin.cms_page.tree', ['id' => $site->getId()]);

        return $this->redirect($url);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws ReflectionException
     * @author Benjamin Robert
     */
    public function createAction(Request $request): Response
    {
        $id = $request->get($this->admin->getIdParameter(), null);

        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine();
        if ($id === null) {
            /** @var CmsSite $site */
            $site = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
        } else {
            /** @var CmsSite $site */
            $site = $em->getRepository('WebEtDesignCmsBundle:CmsSite')->find($id);
        }
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

        /** @var CmsPage $newObject */
        $newObject = $this->admin->getNewInstance();
        $newObject->setSite($site);

        $newObject->setMoveTarget($site->getRootPage());

        if ($request->query->has('refId')) {
            /** @var CmsPage $refPage */
            $refPage = $this->getDoctrine()->getRepository(CmsPage::class)
                ->find($request->query->get('refId'));

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
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);
                $this->admin->checkAccess('create', $submittedObject);

                try {
                    $newObject = $this->admin->create($submittedObject);

                    $this->moveItems($submittedObject);

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
                    $this->handleModelManagerException($e);

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
        $twig = $this->get('twig');
        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $this->admin->getFormTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplateRegistry()->getTemplate($templateKey);

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
    public function editAction(Request $request): Response
    {
        // the key used to lookup the template
        $templateKey = 'edit';

        $id = $request->get($this->admin->getIdParameter());
        /** @var CmsPage $existingObject */
        $existingObject = $this->admin->getObject($id);

        if (!$existingObject) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s',
                $id));
        }

        $this->admin->checkAccess('edit', $existingObject);

        $preResponse = $this->preEdit($request, $existingObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($existingObject);
        $objectId = $this->admin->getNormalizedIdentifier($existingObject);

        $form = $this->admin->getForm();

        if (!is_array($fields = $form->all()) || 0 === count($fields)) {
            throw new RuntimeException(
                'No editable field defined. Did you forget to implement the "configureFormFields" method?'
            );
        }

        $form->setData($existingObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode($this->requestStack->getCurrentRequest()) || $this->isPreviewApproved($this->requestStack->getCurrentRequest()))) {
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);

                try {
                    $existingObject = $this->admin->update($submittedObject);

                    $this->moveItems($submittedObject);

                    if ($this->isXmlHttpRequest($this->requestStack->getCurrentRequest())) {
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
                    return $this->redirectTo($this->requestStack->getCurrentRequest(),
                        $existingObject);
                } catch (ModelManagerException $e) {
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                } catch (LockException $e) {
                    $this->addFlash('sonata_flash_error', $this->trans('flash_lock_error', [
                        '%name%'       => $this->escapeHtml($this->admin->toString($existingObject)),
                        '%link_start%' => '<a href="' . $this->admin->generateObjectUrl('edit',
                                $existingObject) . '">',
                        '%link_end%'   => '</a>',
                    ], 'SonataAdminBundle'));
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if (!$this->isXmlHttpRequest($this->requestStack->getCurrentRequest())) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->trans(
                            'flash_edit_error',
                            ['%name%' => $this->escapeHtml($this->admin->toString($existingObject))],
                            'SonataAdminBundle'
                        )
                    );
                }
            } elseif ($this->isPreviewRequested($this->requestStack->getCurrentRequest())) {
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
        $template = $this->admin->getTemplateRegistry()->getTemplate($templateKey);

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
    protected function preDelete(Request $request, $object): ?Response
    {
        if ($object->isRoot()) {
            $this->addFlash('error', "Vous ne pouvez supprimer la page d'accueil");

            return $this->redirect($this->admin->generateUrl('tree',
                ['id' => $object->getSite()->getId()]));
        }

        return null;
    }

    protected function moveItems($submittedObject)
    {
        $CmsRepo = $this->getDoctrine()->getRepository(CmsPage::class);

        switch ($submittedObject->getMoveMode()) {
            case 'persistAsFirstChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsFirstChildOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $CmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsLastChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsLastChildOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $CmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsNextSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsNextSiblingOf($submittedObject,
                        $submittedObject->getMoveTarget());
                } else {
                    $CmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsPrevSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $CmsRepo->persistAsPrevSiblingOf($submittedObject,
                        $submittedObject->getMoveTarget());
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
    protected function redirectTo(Request $request, object $object): RedirectResponse
    {
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
            $params['id'] = $object->getSite()->getId();
            $url          = $this->admin->generateUrl('create', $params);
        }

        if ('DELETE' === $this->requestStack->getCurrentRequest()->getMethod()) {
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

    public function duplicateAction()
    {
        $request = $this->requestStack->getCurrentRequest();

        $id = $request->get($this->admin->getIdParameter());
        /** @var CmsPage $existingCmsPage */
        $existingCmsPage = $this->admin->getObject($id);

        if (!$existingCmsPage) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s',
                $id));
        }

        $form = $this->createFormBuilder()
            ->add('site', EntityType::class, [
                'class'       => CmsSite::class,
                'required'    => true,
                'placeholder' => 'Choisir le site',
                'label'       => 'Site vers lequel copier la page',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CmsSite $site */
            $site = $form->getData()['site'];

            $newCmsPage = clone $existingCmsPage;

            $newCmsPage->setId(null);
            $newCmsPage->setSlug(null);
            $newCmsPage->setRoute(null);
            $newCmsPage->setRoot(null);
            $newCmsPage->setContents(new ArrayCollection());
            $newCmsPage->setMenuItems(new ArrayCollection());
            $newCmsPage->setDeclinations(new ArrayCollection());
            $newCmsPage->setSite($site);

            if ($existingCmsPage->getSite()->getId() !== $newCmsPage->getSite()->getId()) {
                foreach ($existingCmsPage->getCrossSitePages() as $crossSitePage) {
                    $newCmsPage->addCrossSitePage($crossSitePage);
                }

                $newCmsPage->addCrossSitePage($existingCmsPage);
                $existingCmsPage->addCrossSitePage($newCmsPage);
            }

            foreach ($existingCmsPage->getDeclinations() as $declination) {
                $newCmsPage = $this->copyDeclination($declination, $newCmsPage);
            }

            $newCmsPage->setRoot($site->getRootPage());
            $newCmsPage->setParent($newCmsPage->getParent() ? $this->getParentPage($existingCmsPage,
                $newCmsPage) : $site->getRootPage());
            $newCmsPage = $this->copyContent($existingCmsPage, $newCmsPage);

            $this->getDoctrine()->getManager()->persist($newCmsPage);
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash(
                'sonata_flash_success',
                'Page ' . $existingCmsPage . ' - ' . $existingCmsPage->getSite() . ' copiée'
            );

            // redirect to edit mode
            return $this->redirectTo($this->requestStack->getCurrentRequest(), $newCmsPage);
        }

        return $this->renderWithExtraParams('@WebEtDesignCms/admin/page/duplicate.html.twig', [
            'form'   => $form->createView(),
            'object' => $existingCmsPage
        ], null);
    }

    private function copyDeclination(CmsPageDeclination $declination, CmsPage $page)
    {
        $newDeclination = clone $declination;
        $contents       = $newDeclination->getContents();
        $newDeclination->setContents(new ArrayCollection());

        foreach ($contents as $content) {
            /** @var CmsContent $nc */
            $nc = clone $content;
            $nc->setId(null);
            $nc->setDeclination($declination);
            $this->getDoctrine()->getManager()->persist($nc);
            $newDeclination->addContent($nc);
        }

        $page->addDeclination($newDeclination);

        return $page;
    }

    private function copyContent(CmsPage $origin, CmsPage $page)
    {
        foreach ($origin->getContents() as $cmsContent) {
            /** @var CmsContent $newContent */
            $newContent = clone $cmsContent;
            $newContent->setId(null);
            $newContent->setPage($page);

            $this->getDoctrine()->getManager()->persist($newContent);
            $page->addContent($newContent);
        }

        return $page;
    }

    /**
     * @param CmsPage $src
     * @param CmsPage $dest
     * @return CmsPage
     */
    private function getParentPage(CmsPage $src, CmsPage $dest)
    {
        if ($src->getSite()->getId() === $dest->getSite()->getId()) {
            return $src->getParent();
        }

        $srcParent = $src->getParent();

        if (!$srcParent) {
            return $src->getSite()->getRootPage();
        }

        $cross = $srcParent->getCrossSitePage($dest->getSite());

        return $cross ? $cross : $src->getSite()->getRootPage();
    }

}

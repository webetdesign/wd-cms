<?php

namespace WebEtDesign\CmsBundle\Controller\Admin;

use Exception;
use ReflectionClass;
use ReflectionException;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WebEtDesign\CmsBundle\Repository\CmsSiteRepository;

final class CmsSharedBlockAdminController extends CRUDController
{

    public function listAction(Request $request): Response
    {
        $session = $request->getSession();
        $id = $request->get($this->admin->getIdParameter(), null);

        if ($id === null) {
            if($session->get('admin_current_site_id')) {
                $id = $session->get('admin_current_site_id');
            } else {
                $defaultSite = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsSite')->getDefault();
                if (!$defaultSite) {
                    $this->addFlash('warning', 'Vous devez déclarer un site par défaut');

                    return $this->redirect($this->get('cms.admin.cms_site')->generateUrl('list'));
                }

                $id = $defaultSite->getId();
            }
            $request->attributes->set('id', $id);
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
            $session->set('admin_current_site_id', $id);
            $datagrid->setValue('site', null, $id);
        }
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFilterTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplateRegistry()->getTemplate('list');

        // $template = $this->templateRegistry->getTemplate('list');

        return $this->renderWithExtraParams($template, [
            'action' => 'list',
            'form' => $formView,
            'datagrid' => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
            'export_formats' => $exportFormats ?? $this->admin->getExportFormats(),
        ]);
    }

    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function createAction(Request $request): Response
    {
        $this->admin->checkAccess('create');

        // the key used to lookup the template
        $templateKey = 'edit';

        $class = new ReflectionClass($this->admin->hasActiveSubClass() ? $this->admin->getActiveSubClass() : $this->admin->getClass());

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

        $preResponse = $this->preCreate($request, $newObject);
        if (null !== $preResponse) {
            return $preResponse;
        }

        $this->admin->setSubject($newObject);

        $form = $this->admin->getForm();

        $form->setData($newObject);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode($request) || $this->isPreviewApproved($request))) {
                /** @phpstan-var T $submittedObject */
                $submittedObject = $form->getData();
                $this->admin->setSubject($submittedObject);
                $this->admin->checkAccess('create', $submittedObject);

                try {
                    $newObject = $this->admin->create($submittedObject);

                    if ($this->isXmlHttpRequest($request)) {
                        return $this->handleXmlHttpRequestSuccessResponse($request, $newObject);
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
                    return $this->redirectTo($request, $newObject);
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
                if ($this->isXmlHttpRequest($request) && null !== ($response = $this->handleXmlHttpRequestErrorResponse($request, $form))) {
                    return $response;
                }

                $this->addFlash(
                    'sonata_flash_error',
                    $this->trans(
                        'flash_create_error',
                        ['%name%' => $this->escapeHtml($this->admin->toString($newObject))],
                        'SonataAdminBundle'
                    )
                );
            } elseif ($this->isPreviewRequested($request)) {
                // pick the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $formView = $form->createView();
        // set the theme for the current Admin Form
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        // NEXT_MAJOR: Remove this line and use commented line below it instead
        $template = $this->admin->getTemplateRegistry()->getTemplate($templateKey);
        // $template = $this->templateRegistry->getTemplate($templateKey);

        return $this->renderWithExtraParams($template, [
            'action' => 'create',
            'form' => $formView,
            'object' => $newObject,
            'objectId' => null,
        ], null);
    }
}

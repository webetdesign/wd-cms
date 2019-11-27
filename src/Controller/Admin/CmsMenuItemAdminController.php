<?php

declare(strict_types=1);

namespace WebEtDesign\CmsBundle\Controller\Admin;

use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use WebEtDesign\CmsBundle\Entity\CmsMenuItem;
use WebEtDesign\CmsBundle\Form\MoveForm;

final class CmsMenuItemAdminController extends CRUDController
{

    public function moveAction(Request $request, $itemId)
    {
        $em = $this->getDoctrine()->getManager();

        $object = $em->getRepository(CmsMenuItem::class)->find($itemId);

        $form = $this->createForm(MoveForm::class, $object, [
            'data_class' => CmsMenuItem::class,
            'object'     => $object,
            'action'     => $this->admin->generateUrl('move', ['id' => $object->getMenu()->getId(), 'itemId' => $object->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CmsMenuItem $object */
            $object = $form->getData();

            $this->moveItems($object);

            return $this->redirect($this->admin->getParent()->generateUrl('tree', [
                'id'   => $object->getMenu()->getSite()->getId(),
                '_tab' => 'tab_' . $object->getMenu()->getCode()
            ]));
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'label'        => $object->getName(),
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

    /**
     * @inheritDoc
     */
    public function createAction()
    {
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

        /** @var CmsMenuItem $newObject */
        $newObject = $this->admin->getNewInstance();

        if ($request->query->has('target')) {
            $target = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsMenuItem')->find($request->query->get('target'));
            $newObject->setMoveTarget($target);
            $newObject->setMoveMode('persistAsLastChildOf');
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
                            'result'   => 'ok',
                            'objectId' => $this->admin->getNormalizedIdentifier($newObject),
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
                    dump($e);
                    die();
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
    public function listAction()
    {
        return $this->redirect($this->admin->getParent()->generateUrl('list'));
    }

    protected function moveItems($submittedObject)
    {
        $cmsRepo = $this->getDoctrine()->getRepository('WebEtDesignCmsBundle:CmsMenuItem');

        switch ($submittedObject->getMoveMode()) {
            case 'persistAsFirstChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsFirstChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsLastChildOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsLastChildOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsNextSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsNextSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsFirstChild($submittedObject);
                }
                break;
            case 'persistAsPrevSiblingOf':
                if ($submittedObject->getMoveTarget()) {
                    $cmsRepo->persistAsPrevSiblingOf($submittedObject, $submittedObject->getMoveTarget());
                } else {
                    $cmsRepo->persistAsPrevSibling($submittedObject);
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
            return $this->redirect($this->admin->getParent()->generateUrl('list'));
        }
        if (null !== $request->get('btn_create_and_list')) {
            return $this->redirect($this->admin->getParent()->generateUrl('list'));
        }

        if (null !== $request->get('btn_create_and_create')) {
            $params = [];
            if ($this->admin->hasActiveSubClass()) {
                $params['subclass'] = $request->get('subclass');
            }
            $url = $this->admin->generateUrl('create', $params);
        }

        if ('DELETE' === $this->getRestMethod()) {
            return $this->redirect($this->admin->getParent()->generateUrl('list'));
        }

        if (!$url) {
            foreach (['edit', 'show'] as $route) {
                if ($this->admin->hasRoute($route) && $this->admin->hasAccess($route, $object)) {
                    $url = $this->admin->generateObjectUrl(
                        $route,
                        $object,
                        $this->getSelectedTab($request)
                    );

                    break;
                }
            }
        }

        if (!$url) {
            return $this->redirect($this->admin->getParent()->generateUrl('list'));
        }

        return new RedirectResponse($url);
    }

    private function getSelectedTab(Request $request)
    {
        return array_filter(['_tab' => $request->request->get('_tab')]);
    }


}

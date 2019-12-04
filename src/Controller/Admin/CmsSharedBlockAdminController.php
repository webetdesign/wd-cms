<?php

namespace WebEtDesign\CmsBundle\Controller\Admin;

use Knp\Menu\Renderer\TwigRenderer;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;

final class CmsSharedBlockAdminController extends CRUDController
{

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

        $this->admin->checkAccess('list');

        $request     = $this->getRequest();
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

    protected function setFormTheme(FormView $formView, array $theme = null): void
    {
        $twig = $this->get('twig');

        $twig->getRuntime(FormRenderer::class)->setTheme($formView, $theme);
    }

}

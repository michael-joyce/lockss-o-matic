<?php

namespace LOCKSSOMatic\PLNImporterBundle\Controller;

use LOCKSSOMatic\PLNImporterBundle\Services\PLNPluginImportService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

#use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{

    public function indexAction()
    {
        $response = $this->render('LOCKSSOMaticPLNImporterBundle:Default:index.html.twig',
            array());
        $response->headers->set('Content-Type', 'text/html');
        return $response;
    }

    private function createPluginUploadForm()
    {
        return $this->createFormBuilder()
                ->add('jar_file', 'file',
                    array(
                    'label'    => 'Upload .jar file',
                    'required' => true,
                ))
                ->add('save', 'submit',
                    array(
                    'label'        => 'Import',
                    'button_class' => 'btn btn-primary'
                ))
                ->getForm();
    }

    public function importPluginAction(Request $request)
    {
        $form = $this->createPluginUploadForm();
        $form->handleRequest($request);
        if ($form->isValid()) {
            /** @var UploadedFile */
            $file = $form['jar_file']->getData();
            /** @var PLNPluginImportService */
            $importer = $this->container->get('pln_plugin_importer');
            $entity = $importer->importJarFile($file, false);
            $this->getDoctrine()->getEntityManager()->flush();
            return $this->redirect($this->generateUrl('plugins_show', array(
                'id' => $entity->getId())
            ));
        }
        return $this->render('LOCKSSOMaticPLNImporterBundle:Default:plugin.html.twig',
                array(
                'upload_form' => $form->createView()
        ));
    }

}

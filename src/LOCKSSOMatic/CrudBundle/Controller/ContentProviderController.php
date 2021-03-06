<?php

namespace LOCKSSOMatic\CrudBundle\Controller;

use Ramsey\Uuid\Uuid;
use LOCKSSOMatic\CrudBundle\Entity\ContentProvider;
use LOCKSSOMatic\CrudBundle\Entity\Plugin;
use LOCKSSOMatic\CrudBundle\Form\ContentProviderType;
use LOCKSSOMatic\SwordBundle\Exceptions\BadRequestException;
use LOCKSSOMatic\SwordBundle\Exceptions\HostMismatchException;
use LOCKSSOMatic\SwordBundle\Exceptions\MaxUploadSizeExceededException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ContentProvider controller.
 *
 * @Route("/contentprovider")
 */
class ContentProviderController extends Controller
{
    /**
     * Lists all ContentProvider entities across all PLNs.
     * Does pagination.
     *
     * @Route("/", name="contentprovider")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $dql = 'SELECT e FROM LOCKSSOMaticCrudBundle:ContentProvider e';
        $query = $em->createQuery($dql);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Creates a new ContentProvider entity.
     *
     * @Route("/", name="contentprovider_create")
     * @Method("POST")
     * @Template("LOCKSSOMaticCrudBundle:ContentProvider:new.html.twig")
     *
     * @param Request $request
     *
     * @return RedirectResponse|array
     */
    public function createAction(Request $request) {
        $entity = new ContentProvider();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if ($entity->getUuid() === null || $entity->getUuid() === '') {
                $entity->setUuid(Uuid::uuid4());
            }
            $em->persist($entity);
            $em->flush();
            $this->addFlash('success', 'The content provider was saved.');
            return $this->redirect($this->generateUrl(
                'contentprovider_show',
                array('id' => $entity->getId())
            ));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a form to create a ContentProvider entity.
     *
     * @param ContentProvider $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(ContentProvider $entity) {
        $form = $this->createForm(
            new ContentProviderType(),
            $entity,
            array(
            'action' => $this->generateUrl('contentprovider_create'),
            'method' => 'POST',
            )
        );

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new ContentProvider entity.
     *
     * @Route("/new", name="contentprovider_new")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function newAction() {
        $entity = new ContentProvider();
        $form = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Finds and displays a ContentProvider entity.
     *
     * @Route("/{id}", name="contentprovider_show")
     * @Method("GET")
     * @Template()
     *
     * @param int $id
     *
     * @return array
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:ContentProvider')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContentProvider entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing ContentProvider entity.
     *
     * @Route("/{id}/edit", name="contentprovider_edit")
     * @Method("GET")
     * @Template()
     *
     * @param int $id
     *
     * @return array
     */
    public function editAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:ContentProvider')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContentProvider entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Creates a form to edit a ContentProvider entity.
     *
     * @param ContentProvider $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(ContentProvider $entity) {
        $form = $this->createForm(
            new ContentProviderType(),
            $entity,
            array(
            'action' => $this->generateUrl(
                'contentprovider_update',
                array('id' => $entity->getId())
            ),
            'method' => 'PUT',
            )
        );

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing ContentProvider entity.
     *
     * @Route("/{id}", name="contentprovider_update")
     * @Method("PUT")
     * @Template("LOCKSSOMaticCrudBundle:ContentProvider:edit.html.twig")
     *
     * @param Request $request
     * @param int $id
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:ContentProvider')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContentProvider entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The content provider was saved.');

            return $this->redirect($this->generateUrl(
                'contentprovider_show',
                array('id' => $id)
            ));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a ContentProvider entity. Does not do any
     * confirmation, just deletes.
     *
     * @Route("/{id}/delete", name="contentprovider_delete")
     *
     * @param int $id
     *
     * @return RedirectRequest
     */
    public function deleteAction($id) {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:ContentProvider')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find ContentProvider entity.');
        }

        $em->remove($entity);
        $em->flush();

        return $this->redirect($this->generateUrl('contentprovider'));
    }

    /**
     * Creates a form to delete a ContentProvider entity by id.
     *
     * @todo I think this is unused.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id) {
        return $this->createFormBuilder()->setAction($this->generateUrl(
            'contentprovider_delete',
            array('id' => $id)
        ))->setMethod('DELETE')->add('submit', 'submit', array('label' => 'Delete'))->getForm();
    }

    /**
     * Create a sample CSV document for later import. Users
     * can edit the CSV file in a spreadsheet editor and
     * add the necessary rows. The $id parameter is the
     * database id for the content provider to create the deposit. Streams
     * the CSV file.
     *
     * @todo test this.
     *
     * @Route("/{id}/csv-sample", name="contentprovider_csv_sample")
     * @Method({"GET"})
     *
     * @param int $id
     *
     * @return Response
     */
    public function csvSampleAction($id) {
        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository('LOCKSSOMaticCrudBundle:ContentProvider')->find($id);
        $params = array_merge(
            array('URL', 'journalTitle', 'Size', 'Checksum Type', 'Checksum Value'),
            $provider->getPlugin()->getDefinitionalProperties()
        );
        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $params);
        rewind($fh);

        return new Response(
            stream_get_contents($fh),
            Response::HTTP_OK,
            array(
            'Content-Type' => 'text/csv',
            )
        );
    }

    /**
     * Build a CSV import form.
     *
     * @param int $id
     * @return Form
     */
    private function createImportForm($id) {
        $formBuilder = $this->createFormBuilder();
        $formBuilder->add(
            'uuid',
            'text',
            array(
            'label' => 'Deposit UUID',
            'required' => false,
            'attr' => array(
                'help' => 'Leave UUID blank to have one generated.',
            ),
            )
        );
        $formBuilder->add('title', 'text');
        $formBuilder->add('summary', 'textarea');
        $formBuilder->add('file', 'file', array('label' => 'CSV File'));
        $formBuilder->add('submit', 'submit', array('label' => 'Import'));
        $formBuilder->setAction($this->generateUrl(
            'contentprovider_csv_import',
            array('id' => $id)
        ));
        $formBuilder->setMethod('POST');

        return $formBuilder->getForm();
    }

    /**
     * Check a row to make sure it's correct and ready for import.
     *
     * @param array $record
     * @param Plugin $plugin
     * @throws BadRequestException
     */
    private function precheckContent($record, Plugin $plugin) {
        foreach ($plugin->getDefinitionalProperties() as $property) {
            if (!array_key_exists($property, $record)) {
                throw new BadRequestException("{$property} must have a value.");
            }
        }
    }

    /**
     * Precheck the deposit CSV data before doing an import.
     *
     * @param array $csv
     * @param ContentProvider $provider
     * @throws HostMismatchException
     * @throws MaxUploadSizeExceededException
     */
    private function precheckDeposit($csv, ContentProvider $provider) {
        $plugin = $provider->getPlugin();
        $permissionHost = $provider->getPermissionHost();
        foreach ($csv as $record) {
            $this->precheckContent($record, $plugin);
            $host = parse_url($record['url'], PHP_URL_HOST);
            if ($host !== $permissionHost) {
                $msg = "Content host:{$host} Permission host: {$permissionHost}";
                throw new HostMismatchException($msg);
            }
            if ($record['size'] && $record['size'] > $provider->getMaxFileSize()) {
                throw new MaxUploadSizeExceededException("Content size {$record['size']} exceeds provider's maximum: {$provider->getMaxFileSize()}");
            }
        }
    }

    /**
     * Get the CSV data from an upload form.
     *
     * @param Form $form
     * @return array
     */
    private function getCsvData(Form $form) {
        $data = $form->getData();
        $dataFile = $data['file'];
        $fh = $dataFile->openFile();
        $headers = array_map(function ($h) { return strtolower($h);

        }, $fh->fgetcsv());
        $headerIdx = array_flip($headers);
        $records = array();

        // @codingStandardsIgnoreLine
        while (($row = $fh->fgetcsv()) && (count($row) >= 2)) {
            $record = array();
            foreach ($headers as $header) {
                if(! isset($row[$headerIdx[$header]])) {
                    continue;
                }
                $record[$header] = $row[$headerIdx[$header]];
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Import a CSV file.
     *
     * @Route("/{id}/csv", name="contentprovider_csv_import")
     * @Method({"GET", "POST"})
     * @Template()
     *
     * @param Request $request
     * @param int $id
     *
     * @return array|RedirectResponse
     */
    public function csvAction(Request $request, $id) {
        $em = $this->getDoctrine()->getManager();
        $provider = $em->getRepository('LOCKSSOMaticCrudBundle:ContentProvider')->find($id);
        $requiredParams = $provider->getPlugin()->getDefinitionalProperties();

        $form = $this->createImportForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $csv = $this->getCsvData($form);
            $this->precheckDeposit($csv, $provider);

            /* @var DepositBuilder $builder */
            $depositBuilder = $this->container->get('crud.builder.deposit');
            $contentBuilder = $this->container->get('crud.builder.content');
            $auBuilder = $this->container->get('crud.builder.au');
            $idGenerator = $this->container->get('crud.au.idgenerator');

            $deposit = $depositBuilder->fromForm($form, $provider, $em);
            foreach ($csv as $record) {
                $content = $contentBuilder->fromArray($record);
                $content->setDeposit($deposit);
                $auid = $idGenerator->fromContent($content, false);
                $au = $em->getRepository('LOCKSSOMaticCrudBundle:Au')->findOneBy(array(
                    'auid' => $auid,
                ));
                if ($au === null) {
                    $au = $auBuilder->fromContent($content);
                }
                $content->setAu($au);
            }

            $em->flush();

            return $this->redirect($this->generateUrl('deposit_show', array(
                'id' => $deposit->getId(),
                'plnId' => $deposit->getPln()->getId(),
            )));
        }

        return array(
            'entity' => $provider,
            'required' => $requiredParams,
            'form' => $form->createView(),
        );
    }
}

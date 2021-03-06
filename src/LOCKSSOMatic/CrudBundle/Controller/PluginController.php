<?php

namespace LOCKSSOMatic\CrudBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin controller.
 *
 * @Route("/plugin")
 */
class PluginController extends Controller
{
    /**
     * Lists all Plugin entities. Does pagination.
     *
     * @Route("/", name="plugin")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $dql = 'SELECT e FROM LOCKSSOMaticCrudBundle:Plugin e';
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
     * Finds and displays a Plugin entity.
     *
     * @Route("/{id}", name="plugin_show")
     * @Method("GET")
     * @Template()
     *
     * @param int $id
     *
     * @return array
     */
    public function showAction($id) {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('LOCKSSOMaticCrudBundle:Plugin')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Plugin entity.');
        }

        return array(
            'entity' => $entity,
        );
    }
}

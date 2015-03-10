<?php

namespace LOCKSSOMatic\DefaultBundle\Twig;

use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig_Extension;
use Twig_SimpleFunction;

class PlnsExtension extends Twig_Extension {
    
    /**
     * @var Doctrine
     */
    private $doctrine;

    public function __construct(Doctrine $doctrine, Session $session)
    {
        $this->doctrine = $doctrine;
        $this->session = $session;
    }

    public function getFunctions()
    {
        return array(
            'plnsList' => new Twig_SimpleFunction('plnsList', array($this, 'plnsList')),
            'currentPln' => new Twig_SimpleFunction('currentPln', array($this, 'currentPln')),
        );
    }
    
    public function plnsList() {
        $em = $this->doctrine->getManager();
        return $em->getRepository('LOCKSSOMaticCRUDBundle:Plns')->findAll();
    }
    
    public function currentPln() {
        $plnId = $this->session->get('plnId');
        if( ! $plnId) {
            return null;
        }
        $em = $this->doctrine->getManager();
        return $em->getRepository('LOCKSSOMaticCRUDBundle:Plns')->find($plnId);
    }
    
     public function getName()
    {
        return 'lom_plnsextension';
    }

}
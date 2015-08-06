<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Service;

use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
/**
 * Description of AbstractApiService
 *
 * @author dzonz
 */
abstract class AbstractApiService 
implements ServiceLocatorAwareInterface {
    
    /**
     *
     * @var ServiceLocatorInterface 
     */
    protected $serviceLocator;
    
    public function __construct() {
        
    }
    
    /**
     * Set serviceManager instance
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return void
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Retrieve serviceManager instance
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
    
    /**
     * 
     * @return ServiceLocatorInterface
     */
    public function getRealServiceLocator() {
        return $this->serviceLocator->getServiceLocator();
    }
    
    public function init() {
        
    }
    
}

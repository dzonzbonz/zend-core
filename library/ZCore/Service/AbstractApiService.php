<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Service;

use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceLocatorInterface;
/**
 * Description of AbstractApiService
 *
 * @author dzonz
 */
abstract class AbstractApiService {
    
    /**
     *
     * @var ServiceLocatorInterface 
     */
    protected $serviceLocator;
        
    /**
     *
     * @var Adapter
     */
    protected $adapter;
    
    public function __construct(ServiceLocatorInterface $serviceLocator) {
        $this->serviceLocator = $serviceLocator;
    }
    
    /**
     * 
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator() {
        return $this->serviceLocator;
    }
    
    /**
     * 
     * @return ServiceLocatorInterface
     */
    public function getRealServiceLocator() {
        return $this->serviceLocator->getServiceLocator();
    }
    
    /**
     * 
     * @return Adapter
     */
    public function getAdapter() {
        return $this->adapter;
    }
    
    public function init() {
        
        $this->initAdapter();
        
    }
    
    protected function initAdapter() {
        $config = $this->getServiceLocator()->get('Config');
        
        $this->adapter = new Adapter($config['db_master']);
    }
}

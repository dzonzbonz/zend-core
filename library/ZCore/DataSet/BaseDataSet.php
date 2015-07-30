<?php

namespace ZCore\DataSet;

use ZCore\Resource\BaseResource;
use Zend\Paginator\Paginator;
use ZCore\Resource\PaginatorResource;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class BaseDataSet
extends BaseResource {
    
    protected function getCollectionResourcePrototype($resultSet = null) {
        return null;
    }

    protected function getItemResourcePrototype($resultSet = null) {
        return null;
    }
    
    public function exchangeData($resultSet) {
        $options = $this->getOption();
        $router = $this->getOption('router', array());
        
        $entityResource = $resultSet instanceof Paginator
                ? $this->getCollectionResourcePrototype($resultSet)
                : $this->getItemResourcePrototype($resultSet);
        
        $entityResource->setOption($options);
        
        $this->addResource($entityResource, 'data');
        
    // paginator
        if ($resultSet instanceof Paginator) {
            $paginatorResource = new PaginatorResource($resultSet);
            $paginatorResource->setOption('router', $router);

            $this->addResource($paginatorResource, 'pages');
        }
        
        return $this;
    }
    
}
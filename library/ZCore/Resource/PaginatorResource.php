<?php
namespace ZCore\Resource;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Zend\Paginator\Paginator;

/**
 * Description of ItemResource
 *
 * @author dzonz
 */
class PaginatorResource 
extends BaseResource {
    
    protected $_parameterBase = 'paging[{key}]';
    
    public function __construct(Paginator $data = null, $depracated = null) {
        parent::__construct($data, null);
    }
    
    public function setData(Paginator $data = null, $depracated = null) {
        return parent::setData($data, null);
    }
    
    /**
     * 
     * @return Paginator
     */
    public function getData() {
        return parent::getData();
    }
    
    public function toArray() {        
        return array_merge($this->transformData($this->_data), parent::toArray());
    }
    
    protected function transformData(Paginator $paginator) {
//        var_dump($paginator); die();
        $pages = (array)$paginator->getPages();
        $links = $this->transformLink($pages, $paginator->getItemCountPerPage());
        $pages['links'] = $links;
        return $pages;
    }
    
    protected function transformLink(array $pageCollection = array(), $perPage = -1) {
        $links = array();
        $route = $this->assembleRoute();
        
        foreach ($pageCollection as $pageKey => $pageNum) {
            
            if (is_array($pageNum)) {
                $links[$pageKey] = $this->transformLink($pageNum, $perPage);
            } else {
                if (in_array($pageKey, array(
                    'pageCount', 
                    'itemCountPerPage', 
                    'currentItemCount',
                    'totalItemCount',
                ))) {
                    continue;
                }
                
                $queryParams = array();
            
                $pageParameter = str_replace('{key}', 'page', $this->_parameterBase);
                $queryParams[$pageParameter] = $pageNum;

                $perPageParameter = str_replace('{key}', 'limit', $this->_parameterBase);
                $queryParams[$perPageParameter] = $perPage;
            
                $links[$pageKey] = $route . '?' . http_build_query($queryParams);
            }
        }
        
        return $links;
    }
}

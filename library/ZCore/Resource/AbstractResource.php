<?php
namespace ZCore\Resource;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AbstractResource
 *
 * @author dzonz
 */
abstract class AbstractResource {
    
    protected $_resources = array();
    
    protected $_options = array();
    
    protected $_data = null;
    
    protected $_key = null;
    
    public function __construct($data = null, $key = null) {
        $this->setData($data, $key);
    }
    
    public function setData($data, $key = '') {
        $this->_data = $data;
        $this->_key = $key;
        
        return $this;
    }
    
    public function getData() {
        return $this->_data;
    }
    
    public function setOption($option, $value = null) {
        if (isset($value)) {
            $this->_options[$option] = $value;
        } else {
            $this->_options = $option;
        }
        
        return $this;
    }
    
    public function getOption($option = null, $default = null) {
        return $this->hasOption($option)
                ? (
                    is_null($option)
                    ? $this->_options
                    : $this->_options[$option]
                )
                : $default;
    }
    
    public function hasOption($option = null) {
        return !is_null($option) && !is_array($option)
                ? isset($this->_options[$option])
                : is_array($this->_options) && count($this->_options) > 0;
    }
    
    public function toArray() {
        $result = array();
        
        foreach ($this->_resources as $key => $resource) {
            if (is_callable(array($resource, 'toArray'))) {
                $result[$key] = $resource->toArray();
            } else if (is_array($resource)) {
                $result[$key] = $resource;
            }
        }
        
        return $result;
    }
    
    public function addResource($resource, $key) {
        if (empty($key)) {
            throw new \InvalidArgumentException('Resource key must not be empty');
        }
        
        $this->_resources[$key] = $resource;
        
        return $this;
    }
    
    protected function transformData($data) {
        if (is_array($data)) {
            return $data;
        } else if (is_object($data) && is_callable(array($data, 'toArray'))) {
            return $data->toArray();
        }
        
        return null;
    }
}

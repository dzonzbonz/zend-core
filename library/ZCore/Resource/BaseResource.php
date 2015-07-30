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
class BaseResource {
    
    protected $_resources = array();
    
    protected $_data = null;
    
    protected $_key = null;
    
    /**
     *
     * @var \ZCore\Configuration\BaseConfiguration
     */
    protected $_configuration;
    
    protected $_linked = array();
    
    /**
     *
     * @var \Zend\Mvc\Router\RouteInterface
     */
    protected $router;
    
    /**
     *
     * @var \Zend\Mvc\Router\RouteInterface
     */
    private static $staticRouter;
    
    /**
     *
     * @var \ZCore\Configuration\BaseConfiguration
     */
    private static $staticConfiguration;
    
    public function __construct($data = null, $key = null) {
        $this->setData($data, $key);
        
        $this->_configuration = new \ZCore\Configuration\BaseConfiguration();
    }
    
    public function clearData() {
        $this->_data = array();
        $this->_linked = array();
        
        return $this;
    }
    
    public function setData($data, $key = '') {
        $this->_data = $data;
        $this->_key = $key;
        
        return $this;
    }
    
    public function getData() {
        return $this->_data;
    }
    
    final public function setOption($option, $value = null) {
        $this->_configuration->setOption($option, $value);
        
        return $this;
    }
    
    final public function getOption($option = null, $default = null) {
        $options = $this->_configuration->getOption($option, $default);
        
        $defaultOptions = self::$staticConfiguration->getOption($option, $default);
        
        if (is_array($options) && is_array($defaultOptions)) {
            return \ZCore\Utils::arrayMerge($defaultOptions, $options);
        }
        else if (!is_array($options)) {
            return $defaultOptions;
        }
        else {
            return $options;
        }
    }
    
    final public function hasOption($option = null) {
        return $this->_configuration->hasOption($option);
    }
    
    public function mergeLinkedItems(array $external) {
        $this->_linked = array_merge($this->_linked, $external);
        return $this;
    }
    
    public function setLinkedItem($type, $key, $item) {
        if (!$this->hasLinkedItem($type, $key)) {
            $this->_linked[$type][$key] = $item;
        }
        
        return $this;
    }
    
    public function getLinkedItem($type, $key, $default = null) {
        if ($this->hasLinkedItem($type, $key)) {
            return $this->_linked[$type][$key];
        }
        return $default;
    }
    
    public function hasLinkedItem($type, $key) {
        return isset($this->_linked[$type]) && isset($this->_linked[$type][$key]);
    }
    
    public function getLinkedItems() {
        return is_array($this->_linked) ? $this->_linked : array();
    }
    
    final public function getRouter() {
        if ($this->router) {
            return $this->router;
        } else {
            return self::$staticRouter;
        }
    }

    public function setRouter(\Zend\Mvc\Router\RouteInterface $router) {
        $this->router = $router;
        return $this;
    }
    
    final public static function setGlobalRouter(\Zend\Mvc\Router\RouteInterface $router) {
        self::$staticRouter = $router;
    }
    
    final public static function setGlobalOptions(array $options = array()) {
        if (!self::$staticConfiguration) {
            self::$staticConfiguration = new \ZCore\Configuration\BaseConfiguration();
        }
        $oldOptions = self::$staticConfiguration->getOption();
        self::$staticConfiguration->setOption(\ZCore\Utils::arrayMerge($oldOptions, $options));
    }
    
    protected function assembleRoute(array $params = array(), array $options = array()) {
        $router = $this->getOption('router', array());
        
        $routeName = isset($router['name']) ? $router['name'] : '';
        $defaultParams = isset($router['params']) ? $router['params'] : array();
        $defaultOptions = isset($router['options']) ? $router['options'] : array();
        
        $defaultOptions = array_merge(array(
            'name' => $routeName
        ), $defaultOptions);
        
        return $this->getRouter()->assemble(array_merge($defaultParams, $params), array_merge($defaultOptions, $options));
    }
    
    public function toArray() {
        
        $this->_linked = array();
        
        $resource = $this->transformResource($this->_data);
        
        if (!is_array($resource)) {
            $resource = array();
        }
        
        $result = array();
        
        if ($this->_key && !empty($this->_key)) {
            $result[$this->_key] = $resource;
        } else {
            $result = $resource;
        }
        
        foreach ($this->_resources as $key => $resource) {
            if ($resource instanceof BaseResource) {
                $result[$key] = $resource->toArray();
                $this->mergeLinkedItems($resource->getLinkedItems());
            }
            else if (is_callable(array($resource, 'toArray'))) {
                $result[$key] = $resource->toArray();
            } 
            else if (is_array($resource)) {
                $result[$key] = $resource;
            } else {
                $result[$key] = array();
            }
        }
        
        return $result;
    }
    
    public function getLinkedResource() {
        
        $linkedItems = $this->getLinkedItems();
        $linkedConfig = $this->getOption('linked', array());
        $linkedResource = null;
        
        if (count($linkedItems) > 0) {
            $linkedResource = new BaseResource();
            foreach ($linkedItems as $type => $collection) {
                if (isset($linkedConfig[$type])) {
                    $linkedSchema = new $linkedConfig[$type]['resource']();
                    $linkedSchema->setData($collection);
                    $linkedSchema->setOption($linkedConfig[$type]['options']);
                    
                    $linkedResource->addResource($linkedSchema, $linkedConfig[$type]['name']);
                }
            }
        }
        
        return $linkedResource;
    }
    
    public function addResource($resource, $key) {
        if (empty($key)) {
            throw new \InvalidArgumentException('Resource key must not be empty');
        }
        
        $this->_resources[$key] = $resource;
        
        return $this;
    }
    
    protected function getResource($key) {
        if (empty($key)) {
            throw new \InvalidArgumentException('Resource key must not be empty');
        }
        
        if (!isset($this->_resources[$key])) {
            throw new \OutOfBoundsException('Resource key is not set');
        }
        
        return $this->_resources[$key];
    }
    
    protected function transformResource($data) {
        if (is_array($data)) {
            return $data;
        } else if ($data instanceof BaseResource) {
            $resourceData = $data->toArray();
            $this->mergeLinkedItems($data->getLinkedItems());
            return $resourceData;
        } else if (is_array($data) || is_object($data)) {
            $resourceData = $this->transformData($data);
            return $resourceData;
        } else {
            return array();
        }
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

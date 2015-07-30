<?php

namespace ZCore\ResultSet;

use Zend\Db\ResultSet\ResultSet;
use ZCore\Configuration\ConfigurationInterface;
use ZCore\Configuration\BaseConfiguration;

/**
 * Description of AbstractResultSet
 *
 * @author dzonz
 */
abstract class BaseResultSet 
extends ResultSet 
implements ConfigurationInterface {
    
    /**
     *
     * @var BaseConfiguration
     */
    protected $configuration = null;
    
    public function __construct($returnType = self::TYPE_ARRAYOBJECT, $arrayObjectPrototype = null, $configuration = array()) {
        parent::__construct($returnType, $arrayObjectPrototype);
        
        $this->configuration = new BaseConfiguration($configuration);
    }
    
    protected function currentRow() {
        if ($this->buffer === null) {
            $this->buffer = -2; // implicitly disable buffering from here on
        } elseif (is_array($this->buffer) && isset($this->buffer[$this->position])) {
            return $this->buffer[$this->position];
        }
        $data = $this->dataSource->current();
        if (is_array($this->buffer)) {
            $this->buffer[$this->position] = $data;
        }
        return $data;
    }
 
    protected function exchangeObject($current, $data = array()) {
        
    }
 
    protected function exchangeArray(array $data = array()) {
        return $data;
    }
    
    public function current() {
        $data = $this->currentRow();

        if ($this->returnType === self::TYPE_ARRAYOBJECT && is_array($data)) {
            /** @var $ao ArrayObject */
            $ao = clone $this->arrayObjectPrototype;
            if ($ao instanceof ArrayObject || method_exists($ao, 'exchangeArray')) {
                $ao->exchangeArray($data);
            }
            
            $this->exchangeObject($ao, $data);
            
            return $ao;
        }

        $exchangedArray = $this->exchangeArray($data);
        
        if (!is_array($exchangedArray)) {
            $exchangedArray = array();
        }
        
        return $exchangedArray;
    }
    
    public function getOption($option = null, $default = null) {
        return $this->configuration->getOption($option, $default);
    }

    public function hasOption($option = null) {
        return $this->configuration->hasOption($option);
    }

    public function setOption($option, $value = null) {
        $this->configuration->setOption($option, $value);
        return $this;
    }

}

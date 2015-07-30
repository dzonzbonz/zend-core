<?php

namespace ZCore\Configuration;

use ZCore\Configuration\ConfigurationInterface;

/**
 * Description of AbstractConfig
 *
 * @author dzonz
 */
class BaseConfiguration
implements ConfigurationInterface {
    
    protected $_options = array();
    
    public function __construct($options = array()) {
        $this->setOption($options);
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
    
}

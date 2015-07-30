<?php

namespace ZCore\Extractor;

abstract class AbstractExtractor
implements ExtractorInterface {
    
    /**
     *
     * @var array
     */
    private $options;
    
    public function __construct(array $options = array()) {
        $this->setOption($options);
    }

    public function getOption($option = null, $default = null) {
        return $this->hasOption($option)
            ? (
                !is_null($option) 
                ? $this->options[$option]
                : $this->options
            
            )
            : $default;
    }

    public function hasOption($option = null) {
        return is_array($this->options) 
            ?  (
                !is_null($option)
                ? isset($this->options[$option])
                : false
            )
            : false;
    }
    
    public function setOption($option = null, $value = null) {
        if (is_null($option)) {
            $this->options = $option;
        } else {
            $this->options[$option] = $value;
        }
        
        return $this;
    }
    
    public function unsetOption($option = null) {
        if (is_null($option)) {
            $this->options = null;
        } else {
            unset($this->options[$option]);
        }
        
        return $this;
    }

}


<?php

namespace ZCore\Model;

use ZCore\Extractor\ExtractorInterface;
use Zend\Stdlib\Hydrator\Filter\FilterInterface;
use ZCore\Extractor\ObjectPropertyExtractor;

abstract class AbstractModel 
implements ModelInterface {

    protected static $__publicProperties = array();
    
    protected static $__hiddenProperties = array();
    
    public function __construct(array $data = array(), $opts = array()) {
        $this->exchangeArray($data, $opts);
    }
    
    public static function newFromArray(array $data = array(), $opts = array()) {
        $instance = new self();
        $instance->exchangeArray($data, $opts);
        
        return $instance;
    }
    
    protected function parseProperties() {
        $class = get_called_class();
        
        if (!isset(self::$__publicProperties[$class]) || !isset(self::$__hiddenProperties[$class])) {
            $reflectionObject = new \ReflectionClass($this);
            $properties = $reflectionObject->getProperties(\ReflectionProperty::IS_PROTECTED);
            
            self::$__publicProperties[$class] = array();
            self::$__hiddenProperties[$class] = array();
            
            foreach ($properties as $property) {
                if (substr($property->getName(), 0, 1) != '_') {
                    self::$__publicProperties[$class][] = $property->getName();
                } else if (substr($property->getName(), 0, 2) != '__') {
                    self::$__hiddenProperties[$class][] = $property->getName();
                }
            }
        }
    }
    
    public function getPublicProperties() {
        $this->parseProperties();
        
        $class = get_called_class();
        
        return self::$__publicProperties[$class];
    }
    
    public function getHiddenProperties() {
        $this->parseProperties();
        
        $class = get_called_class();
        
        return self::$__hiddenProperties[$class];
    }
    
    protected function getExchangePrefix($opts = array()) {
        return isset($opts['prefix']) ? $opts['prefix'] : '';
    }
    
    protected function getExchangeVisibility($opts = array()) {
        return isset($opts['visibility']) ? $opts['visibility'] : 'all'; // all, set, unset
    }
    
    protected function getExchangeValue($opts = array()) {
        return isset($opts['value']) ? $opts['value'] : 'any'; // empty, has, any
    }
    
    /**
     * {@inheritDoc}
     */
    public function exchangeArray(array $data = array(), array $opts = array()) {
        if (is_array($data) && count($data) > 0) {
            
            $prefix = $this->getExchangePrefix($opts);
            $exchangeProperties = $this->getPublicProperties();
            $include = isset($opts['include']) ? $opts['include'] : false;
            $exclude = isset($opts['exclude']) ? $opts['exclude'] : array();
        
            // set only values which exists in array with possibility to treat null values
            if (count($data) > count($exchangeProperties)) {
                foreach ($exchangeProperties as $property) {
                    if (isset($data[$prefix . $property])) {
                        if (in_array($property, $exclude)) {
                            continue;
                        } 
                        else if ($include && !in_array($property, $include)) {
                            continue;
                        }
                        
                        $this->$property = $data[$prefix . $property];
                    }
                }
            } else {
                foreach ($data as $key => $value) {
                    if (!empty($prefix)) {
                        if (strpos($key, $prefix) === 0) {
                            $key = substr($key, strlen($prefix));
                        } else {
                            continue;
                        }
                    }
                    
                    if (in_array($key, $exclude)) {
                        continue;
                    } 
                    else if ($include && !in_array($key, $include)) {
                        continue;
                    }
                    
                    if (in_array($key, $exchangeProperties)) {
                        $this->$key = $value;
                    }
                }
            }
        }
        
        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getArrayCopy(array $opts = array()) {
        $prefix = $this->getExchangePrefix($opts);
        $value = $this->getExchangeValue($opts);
        $exchangeProperties = $this->getPublicProperties();
        $include = isset($opts['include']) ? $opts['include'] : false;
        $exclude = isset($opts['exclude']) ? $opts['exclude'] : array();
        
        $array = array();
        
        foreach ($exchangeProperties as $property) {
            if (in_array($property, $exclude)) {
                continue;
            } 
            else if ($include && !in_array($property, $include)) {
                continue;
            } 
            
            if ('any' == $value) {
                $array[$prefix . $property] = $this->$property;
            } else if ('has' == $value) {
                if (isset($this->$property) && !is_null($this->$property)) {
                    $array[$prefix . $property] = $this->$property;
                } else {
                    unset($array[$prefix . $property]);
                }
            } else {
                $array[$prefix . $property] = null;
            }
        }
        
        return $array;
    }

    public function toArray(array $opts = null) {
        $res = array();
        
        $include = isset($opts['include']) ? $opts['include'] : true;
        $exclude = isset($opts['exclude']) ? $opts['exclude'] : array();
        $prefix = isset($opts['prefix']) ? strval($opts['prefix']) : '';
        $scope = isset($opts['scope']) ? strval($opts['scope']) : 'no-objects';

        // provide correct values for LOV type options
        if (!in_array($scope, array('no-objects', 'all-single-depth', 'all-recursive'))) {
            $scope = 'no-objects';
        }
        
        $publicProperties = $this->getPublicProperties();
        
        foreach ($publicProperties as $prop) {
            // now check include and exclude options
            if (in_array($prop, $exclude)) {
                continue;
            } 
            else if (is_array($include) && !in_array($prop, $include)) {
                continue;
            }

            // if property is object then check mode
            if (is_object($this->$prop)) {
                if ($scope == 'no-objects') {
                    continue;
                }

                // try to export property, if it is instance of AbstractEntity then call its toArray
                // (take care to adapt scope)
                // otherwise
                // try to export object into string or give up
                if ($this->$prop instanceof AbstractEntity) {
                    if ($scope == 'all-recursive') {
                        $res[$prefix . $prop] = $this->$prop->toArray(array('scope' => 'all-recursive'));
                    } else {
                        $res[$prefix . $prop] = $this->$prop->toArray(array('scope' => 'no-objects'));
                    }
                } elseif (method_exists($this->$prop, '__toString')) {
                    $res[$prefix . $prop] = strval($this->$prop);
                } else {
                    continue;
                }
            } else {
                $res[$prefix . $prop] = $this->$prop;
            }
        }

        return $res;
    }

}

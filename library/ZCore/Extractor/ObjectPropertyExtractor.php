<?php

namespace ZCore\Extractor;

class ObjectPropertyExtractor
extends AbstractExtractor {

    private static $singleton;
    
    public static function singleton() {
        if (!self::$singleton) {
            self::$singleton = new self();
        }
        
        return self::$singleton;
    }
    
    public static function staticExtract($object, $opts = array()) {
        $extracted = array();
        
        $include = isset($opts['include']) ? $opts['include'] : true;
        $exclude = isset($opts['exclude']) ? $opts['exclude'] : array();
        $prefix = isset($opts['prefix']) ? strval($opts['prefix']) : '';
        $scope = isset($opts['scope']) ? strval($opts['scope']) : 'no-objects';
        $visibility = isset($opts['visibility']) ? strval($opts['visibility']) : 'protected';
        
        $reflectionObject = new \ReflectionClass($object);
        $properties = $reflectionObject->getProperties(\ReflectionProperty::IS_PROTECTED);
        
        foreach ($properties as $property) {
            if (in_array($property->getName(), $exclude)) {
                continue;
            } elseif (is_array($include) && !in_array($property->getName(), $include)) {
                continue;
            }
            
            $getMethod  = str_replace(array('.', '-', '_'), ' ', $property->getName());
            $getMethod  = ucwords($getMethod);
            $getMethod  = str_replace(' ', '', $getMethod);
            $getMethod  = 'get' . $getMethod;

            if (is_callable(array($object, $getMethod))) {
                $extracted[$prefix . $property->getName()] = $this->$getMethod();
            }
        }
        
        return $extracted;
    }
    /**
     * {@inheritDoc}
     */
    public function extract($object) {
        $extracted = array();
        
        $prefix = $this->getPrefix();
        $exclude = $this->getExclude();
        $include = $this->getInclude();
        
        $prefix  = $prefix && !empty($prefix) ? $prefix : '';
        $exclude  = !is_array($exclude) ? array() : $exclude;
        $include  = !is_array($include) ? false : $include;
        
//        $scope = isset($opts['scope']) ? strval($opts['scope']) : 'no-objects';
//        $visibility = isset($opts['visibility']) ? strval($opts['visibility']) : 'protected';
        
        $opts = array(
            'include' => $include,
            'exclude' => $exclude,
            'prefix'  => $prefix,
            'visibility' => $visibility,
            'scope' => $scope
        );
        
        return self::staticExtract($object, $opts);
        
    }

}


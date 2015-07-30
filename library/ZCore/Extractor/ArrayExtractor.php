<?php

namespace ZCore\Extractor;

class ArrayExtractor
extends AbstractExtractor {

    private static $singleton;
    
    public static function singleton() {
        if (!self::$singleton) {
            self::$singleton = new self();
        }
        
        return self::$singleton;
    }
    
    public static function staticExtract($data, $opts = array()) {
        $extracted = array();
        
        $properties = isset($opts['properties']) ? (array) $opts['properties'] : true;
        $include = isset($opts['include']) ? (array) $opts['include'] : true;
        $exclude = isset($opts['exclude']) ? (array) $opts['exclude'] : array();
        $prefix = isset($opts['prefix']) ? strval($opts['prefix']) : '';
        
    // set only values which exists in array with possibility to treat null values
        foreach ($data as $key => $val) {
        // in prefix mode we are considering only keys which begin with prefix
            if ($prefix) {
                if (strpos($key, $prefix) === 0) {
                    // key starts with prefix, remove it to get new key
                    $key = substr($key, strlen($prefix));
                } else {
                    continue;
                }
            }

            if (in_array($key, $exclude)) {
                continue;
            } 
            else if (is_array($include) && !in_array($key, $include)) {
                continue;
            } 
            else if (is_array($properties) && !in_array($key, $properties)) {
                continue;
            }
            
            $extracted[$key] = $val;
        }
        
        return $extracted;
    }
    
    /**
     * {@inheritDoc}
     */
    public function extract($data, $opts = array()) {
        return self::staticExtract($data, $opts);
    }

}


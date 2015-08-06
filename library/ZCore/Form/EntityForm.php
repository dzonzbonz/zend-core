<?php

namespace ZCore\Form;

use ZCore\Form\AbstractForm;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class EntityForm 
extends AbstractForm {

    protected $entityKey = array(
        'id'
    );
    
    public function isValid(array $opts = array()) {
        $method = isset($opts['filter']) ? $opts['filter'] : self::INPUT_FILTER_CREATE;
        
    // If there are some keys set we assume that the default validation is overriden
        $include = isset($opts['include']) ? $opts['include'] : array();
        $exclude = isset($opts['exclude']) ? $opts['exclude'] : array();
        
        if (self::INPUT_FILTER_CREATE == $method) {
        // create
            if (count($exclude) == 0) {
                foreach ($this->entityKey as $keyField) {
                    if (!in_array($keyField, $exclude)) {
                        $exclude[] = $keyField;
                    }
                }
            }
        }
        else if (self::INPUT_FILTER_DELETE == $method) {
            if ($include !== false && count($include) == 0) {
                foreach ($this->entityKey as $keyField) {
                    if (!in_array($keyField, $include)) {
                        $include[] = $keyField;
                    }
                }
            }
        }
        else if (self::INPUT_FILTER_PATCH == $method) {
        // we are patching and must have keys
            if ($include !== false && count($include) == 0) {
                $entityFields = array_keys($this->getData());
                foreach ($entityFields as $entityField) {
                    if (!in_array($entityField, $include)) {
                        $include[] = $entityField;
                    }
                }
            }
        }
        
        $newOpts = array_merge($opts, array(
            'include' => count($include) > 0 ? $include : false,
            'exclude' => $exclude
        ));

        return parent::isValid($newOpts);
    }
}
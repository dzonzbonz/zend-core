<?php
namespace ZCore\Resource;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ItemResource
 *
 * @author dzonz
 */
class ItemResource 
extends BaseResource {
    
//    public function toArray() {
//        $itemArray = array();
//        
//        if ($this->_data instanceof AbstractResource) {
//            $itemArray = $this->_data->toArray();
//        } else if (is_array($this->_data) || is_object($this->_data)) {
//            $itemArray = $this->transformData($this->_data);
//            if (is_null($itemArray)) {
//                $itemArray = array();
//            }
//        } else {
//            $itemArray = $this->_data;
//        }
//        
//        $ret = array();
//        if ($this->_key && !empty($this->_key)) {
//            $ret[$this->_key] = $itemArray;
//        } else {
//            $ret = $itemArray;
//        }
//        
//        return array_merge($ret, parent::toArray());
//    }
    
}

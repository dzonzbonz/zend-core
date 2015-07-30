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
class ErrorResource 
extends ItemResource {
    
    public function setData($data) {
        parent::setData($data, 'errors');
    }
    
}

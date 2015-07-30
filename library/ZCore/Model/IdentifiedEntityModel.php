<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Model;

use ZCore\Model\AbstractModel;
use ZCore\Model\IdentifiedModelInterface;

/**
 * Description of EntityModel
 *
 * @author dzonz
 */
class IdentifiedEntityModel 
extends AbstractModel
implements IdentifiedModelInterface {
    
    protected $id;
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    
}

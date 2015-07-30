<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Model;

use ZCore\Model\AbstractModel;
use ZCore\Model\EntityModelInterface;

/**
 * Description of EntityModel
 *
 * @author dzonz
 */
class EntityModel 
extends AbstractModel
implements EntityModelInterface {
    
    protected $id;
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    
}

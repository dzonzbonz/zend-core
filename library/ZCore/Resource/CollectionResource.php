<?php
namespace ZCore\Resource;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CollectionResource
 *
 * @author dzonz
 */
class CollectionResource 
extends BaseResource {
    
    protected function transformResource($data) {
        $collectionArray = array();
        
        if (!is_null($data)) {
            foreach ($data as $collectionItem) {
                $collectionArray[] = parent::transformResource($collectionItem);
            }
        }
        
        return $collectionArray;
    }
    
}

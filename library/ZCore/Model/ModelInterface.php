<?php

namespace ZCore\Model;

interface ModelInterface {
    
    /**
     * Exchange data into object
     * 
     * @param array $data
     * @param array $opts
     */
    public function exchangeArray(array $data = array(), array $opts = array());

    /**
     * Returns the array of defined fields
     * 
     * @return array
     */
    public function getArrayCopy(array $opts = array());
    
    /**
     * Returns the array of defined fields
     * For compatibility it reffers to getArrayCopy
     * 
     * @return array
     */
    public function toArray(array $opts = array());
}


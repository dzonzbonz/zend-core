<?php

namespace ZCore\Extractor;

interface ExtractorInterface {
    
    public function getOption();

    public function setOption($option = null, $value = null);
    
    public function hasOption($option = null);
    
    public function unsetOption($option = null);
    
    /**
     * Extract Data from array
     * 
     * @param mixed $data
     * @return array
     */
    public function extract($data);
    
}
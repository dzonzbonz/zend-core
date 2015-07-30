<?php

namespace ZCore\Configuration;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author dzonz
 */
interface ConfigurationInterface {
    
    public function setOption($option, $value = null);
    
    public function getOption($option = null, $default = null);
    
    public function hasOption($option = null);
    
}

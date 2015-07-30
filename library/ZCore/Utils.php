<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore;

class Utils {
    
    public static function arrayMerge($default, $newData) {
    $ret = $default;
    
    foreach ($newData as $key => $value) {
        if (is_array($value)) {
            $ret[$key] = self::arrayMerge($default[$key], $value);
        } else {
            $ret[$key] = $value;
        }
    }
    
    return $ret;
}
    
}
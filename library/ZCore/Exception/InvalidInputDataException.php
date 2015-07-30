<?php

namespace ZCore\Exception;

use Zend\InputFilter\InputFilter;
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class InvalidInputDataException
extends \Exception {
    
    /**
     *
     * @var InputFilter
     */
    protected $inputFilter;
    
    public function __construct(InputFilter $form, $message, $code, $previous) {
        $this->inputFilter = $form;
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * 
     * @return InputFilter
     */
    public function getInputFilter() {
        return $this->inputFilter;
    }

}
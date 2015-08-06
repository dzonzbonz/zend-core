<?php

namespace ZCore\Form;

use Zend\InputFilter\InputFilter;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

abstract class AbstractForm 
extends InputFilter {
    const INPUT_FILTER_CREATE = 'create';
    const INPUT_FILTER_UPDATE = 'update';
    const INPUT_FILTER_PATCH = 'patch';
    const INPUT_FILTER_DELETE = 'delete';
    
    public function __construct(array $data = array(), array $form = array()) {
        $this->addInputFilters($form);
        
        $this->setData($data);
    }
    
    public function getData() {
        return $this->data;
    }
    
    protected function addInputFilters(array $form = array()) {
        foreach ($form as $inputKey => $inputFilter) {
            $this->add(
                array_merge(array('name' => $inputKey), $inputFilter), 
                $inputKey
            );
        }
        
        return $this;
    }
    
    public function isValid(array $opts = array()) {
        $valid = false;
        
        $exclude = isset($opts['exclude']) ? $opts['exclude'] : array();
        $include = isset($opts['include']) ? $opts['include'] : false;
        
        if (!$include && count($exclude) > 0) {
            $inputs = array_keys($this->getInputs());
            $include = array_diff($inputs, $exclude);
        }
        
        if ($include) {
            $valid = $this->validateInputs($include);
        } else {
            $valid = parent::isValid();
        }
        
        return $valid;
    }
    
    
    /**
     * 
     * @param string $name
     * @return \Zend\Validator\ValidatorChain
     */
    protected function getValidatorChainByName($name) {
        $inputValidator = $this->get($name);
        return $inputValidator->getValidatorChain();
    }
    
    
    /**
     * 
     * @param string $name
     * @param int $validator
     * @return \Zend\Validator\AbstractValidator
     */
    protected function getValidatorInstanceByName($name, $validator = 0) {
        try {
            $validatorChain = $this->getValidatorChainByName($name);

            $validators = $validatorChain->getValidators();

            $inputFieldValidator = $validators[$validator]['instance'];
            
            return $inputFieldValidator;
        } catch (\Zend\InputFilter\Exception\InvalidArgumentException $ia) {
            return null;
        }
    }
}
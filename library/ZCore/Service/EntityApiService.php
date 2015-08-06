<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Service;

use ZCore\Service\AbstractApiService;
use ZCore\Model\AbstractModel;

/**
 * Description of AbstractApiService
 *
 * @author dzonz
 */
abstract class EntityApiService 
extends AbstractApiService {
    
    const INPUT_FILTER_METHOD_SAVE = 'save';
    const INPUT_FILTER_METHOD_CREATE = 'create';
    const INPUT_FILTER_METHOD_UPDATE = 'update';
    const INPUT_FILTER_METHOD_PATCH = 'patch';
    const INPUT_FILTER_METHOD_DELETE = 'delete';
    
    const SAVE_LIST_METHOD_CONTINUE = 'continue';
    const SAVE_LIST_METHOD_BREAK = 'break';
    
    const SAVE_LIST_VALIDATE_ALL = 'all';
    const SAVE_LIST_VALIDATE_SINGLE = 'single';
    
    /**
     *
     * @var \Zend\Db\Adapter\Adapter
     */
    protected $adapter = null;
    
    /**
     *
     * @var \ZCore\Mapper\AbstractMapper
     */
    protected $entityMapper;
    
    public function __construct(\Zend\Db\Adapter\Adapter $adapter) {
        parent::__construct();
        
        $this->adapter = $adapter;
    }
    
    /**
     * @returns \ZCore\Form\AbstractForm
     */
    abstract protected function getEntityInputFilter(array $data = array(), $method = self::INPUT_FILTER_METHOD_CREATE);
    
    /**
     * @return AbstractModel
     */
    abstract protected function getEntityPrototype(array $data = array(), array $opts = array());
    
    /**
     * 
     * @param \ZCore\Model\IdentifiedEntityModel $entity
     * @param string $method
     * @return array
     */
    protected function getEntityArray(AbstractModel $entity, $method = self::INPUT_FILTER_METHOD_CREATE) {
        return $entity->getArrayCopy(array(
            'value' => self::INPUT_FILTER_METHOD_PATCH == $method ? 'has' : 'any'
        ));
    }
    
    /**
     * 
     * @return Adapter
     */
    public function getAdapter() {
        return $this->adapter;
    }
    
    /**
     * @return \ZCore\Mapper\AbstractMapper
     */
    abstract protected function instantiateMapper();
    
    /**
     * 
     * @return \ZCore\Mapper\AbstractMapper
     */
    protected function getMapper() {
        if (!$this->entityMapper) {
            $this->entityMapper = $this->instantiateMapper();
        }
        
        return $this->entityMapper;
    }
    
    protected function deleteEntity(AbstractModel $entity) {
        $method = self::INPUT_FILTER_METHOD_DELETE;
        $entityData = $this->getEntityArray($entity, $method);
        $filter = $this->getEntityInputFilter($entityData, $method);
        
        $this->validateEntityData($filter, $entityData, array('filter' => $method)); 
        return $this->getMapper()->delete($entity);
    }
    
    /**
     * {@inheritDoc}
     */
    protected function saveEntity(AbstractModel $entity, $method = self::INPUT_FILTER_METHOD_CREATE) {
        $entityData = $this->getEntityArray($entity, $method);
        
        return $this->saveEntityArray($entityData, $method);
    }
    
    protected function validateEntityData(\ZCore\Form\EntityForm $form, array $data = array(), array $opts = array()) {
        $form->setData($data);
        if (!$form->isValid($opts)) {
            throw new \ZCore\Exception\InvalidInputDataException($form, 'Invalid input data');
        }
        
        return true;
    }
    
    /**
     * 
     * @param AbstractModel $model
     * @return type
     */
    protected function saveEntityProxy(AbstractModel $model) {
        return $this->getMapper()->save($model);
    }
    
    /**
     * 
     * @param array $data
     * @param string $method
     * @return \ZCore\Model\AbstractModel
     * @throws \ZCore\Exception\InvalidInputDataException
     */
    protected function saveEntityArray(array $data = array(), $method = self::INPUT_FILTER_METHOD_CREATE) {
        if (!is_array($data)) {
            $data = array();
        }
        
        $entityInputFilter = $this->getEntityInputFilter($data, $method);
        
        $this->validateEntityData($entityInputFilter, $data, array('filter' => $method));

        $entity = $this->getEntityPrototype($entityInputFilter->getValues());

        $this->saveEntityProxy($entity);

        return $entity;
    }
    
    /**
     * 
     * @param array $list
     */
    protected function saveEntityDataList(array $list = array(), array $commonData = array(), $method = self::INPUT_FILTER_METHOD_CREATE) {
        $report = array(
            "__list__" => array(),
            "errors" => array()
        );
        
    // validate first
        $inputFilter = $this->getEntityInputFilter(array());
        
        $error = 0;
        foreach ($list as $num => $entityData) {
            $saveData = array_merge($entityData, $commonData);
            
            try {
            // validate single item
                $this->validateEntityData($inputFilter, $saveData, array('filter' => $method, 'save' => 'list'));

                $entity = $this->getEntityPrototype($inputFilter->getValues());

                $this->saveEntityProxy($entity);
                
                $report["__list__"][$num] = array_merge($entity->getArrayCopy(array('value' => 'has')), array(
                    '_status_' => 'OK'
                ));
            } catch (\ZCore\Exception\InvalidInputDataException $ex) {
                $error++;
                $report["__list__"][$num] = array_merge($saveData, array(
                    '_status_' => 'error_' . $error
                ));
                $report["errors"]['error_' . $error] = $ex->getInputFilter()->getMessages();
            } catch (\Exception $e) {
                $error++;
                $report["__list__"][$num] = array_merge($saveData, array(
                    '_status_' => 'error_' . $error
                ));
                $report["errors"]['error_' . $error] = $e->getMessage();
            }
        }
        
        return $report;
    }
}

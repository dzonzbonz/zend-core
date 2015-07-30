<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Mapper;

use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Db\Adapter\AdapterInterface;
use ZCore\Model\ModelInterface;

use ZCore\Model\IdentifiedEntityModel;

abstract class IdentifiedEntityMapper 
extends AbstractMapper {

    /**
     * @var ModelInterface
     */
    protected $modelPrototype;
    
    /**
     *
     * @var boolean
     */
    protected $tablePrimaryKeyAutoIncrement = true;
    
    /**
     *
     * @var string | array
     */
    protected $tablePrimaryKey = 'id';
    
    public function __construct(
        AdapterInterface $dbAdapter
    ) {
        parent::__construct($dbAdapter);
    }
    
    /**
     * 
     * @param IdentifiedEntityModel $entity
     * @return int
     */
    public function save($entity) {
        return $this->saveEntity($entity);
    }

    /**
     * 
     * @param IdentifiedEntityModel $entity
     * @return int
     */
    public function delete($entity) {
        return $this->deleteEntity($entity);
    }
    
    protected function findEntity($key, ResultSetInterface $resultSet = null) {
        $select = $this->selectBy($key);
        $select->columns($this->getSelectColumns());
        $select->limit(1);
        
        return $this->executeSelectStatement($select, $resultSet);
    }
    
    protected function saveEntity(IdentifiedEntityModel $entity) {
        if ($entity->getId()) {
            $this->updateEntity($entity);
        } else {
            $this->insertEntity($entity);
        }
        
        return $entity->getId();
    }
    
    protected function deleteEntity(IdentifiedEntityModel $entity) {
        $delete = $this->deleteBy($entity->getId());
        
        return $this->executeDelete($delete)->getAffectedRows();
    }
    
    protected function updateEntity(IdentifiedEntityModel $entity) {
        $update = $this->updateBy($entity->getArrayCopy(array('value' => 'has')));
        
        return $this->executeUpdate($update)->getAffectedRows();
    }
    
    protected function insertEntity(IdentifiedEntityModel $entity) {
        $insert = $this->insertBy($entity->getArrayCopy(array('value' => 'has')));
        
        $id = $this->executeInsert($insert)->getGeneratedValue();
        $entity->setId($id);
        
        return $id;
    }
    
    /**
     * {@inheritDoc}
     */
    protected function deleteByKey($id, $key = 'id') {
        $action = $this->sql->delete($this->tableName);
        $action->where(array($key . ' = ?' => $id));

        try {
            $stmt = $this->sql->prepareStatementForSqlObject($action);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            throw new \ZCore\Exception\EntryDeleteException("Delete error", null, $e);
        }
        
        return (bool) $result->getAffectedRows();
    }

}

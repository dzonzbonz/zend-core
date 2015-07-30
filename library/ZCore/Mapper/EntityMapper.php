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

use ZCore\Model\EntityModel;

abstract class EntityMapper 
extends AbstractMapper {

    /**
     * @var ModelInterface
     */
    protected $modelPrototype;
    
    public function __construct(
        AdapterInterface $dbAdapter
    ) {
        parent::__construct($dbAdapter);
    }
    
    
    
    /**
     * {@inheritDoc}
     */
    protected function findByKey($key, ResultSetInterface $resultSet = null) {
        $select = $this->selectBy($key);
        
        $result = $this->executeSelectStatement($select, $resultSet);
        
        try {
            $stmt = $this->sql->prepareStatementForSqlObject($select);
            $result = $stmt->execute();
        } catch (Exception $e) {
            throw new \ZCore\Exception\DatabaseException('Query Error', null, $e);
        }
        
        if ($result instanceof ResultInterface && $result->isQueryResult() && $result->getAffectedRows()) {
            return $result->current();
        }

        throw new \ZCore\Exception\EntryNotFoundException("{$this->tableFriendlyName} with given ID:{$key} not found.");
    }
    
    protected function findEntity($key, ResultSetInterface $resultSet = null) {
        $select = $this->selectBy($key);
        $select->columns($this->getSelectColumns());
        $select->limit(1);
        
        return $this->executeSelectStatement($select, $resultSet);
    }
    
    protected function saveEntity(EntityModel $entity) {
        if ($entity->getId()) {
            $this->updateEntity($entity);
        } else {
            $this->insertEntity($entity);
        }
        
        return $entity->getId();
    }
    
    protected function deleteEntity(EntityModel $entity) {
        $delete = $this->deleteBy($entity->getId());
        
        return $this->executeDelete($delete)->getAffectedRows();
    }
    
    protected function updateEntity(EntityModel $entity) {
        $update = $this->updateBy($entity->getArrayCopy(array('value' => 'has')));
        
        return $this->executeUpdate($update)->getAffectedRows();
    }
    
    protected function insertEntity(EntityModel $entity) {
        $insert = $this->deleteBy($entity->getArrayCopy(array('value' => 'has')));
        
        $id = $this->executeInsert($insert)->getGeneratedValue();
        $entity->setId($id);
        
        return $id;
    }
    
    protected function saveData(array $data = array(), $key = 'id') {
        $key = strval($key);
        
        $id = $key && isset($data[$key]) ? intval($data[$key]) : null;
        
        if ($key !== null) {
            unset($data[$key]); // Neither Insert nor Update needs the ID in the array
        }
        
        if ($id) {
            // ID present, it's an Update
            $action = $this->sql->update($this->tableName);
            $action->set($data);
            $action->where(array($key . ' = ?' => intval($id)));
        } else {
            // ID NOT present, it's an Insert
            $action = $this->sql->insert($this->tableName);
            $action->values($data);
        }

        $stmt = $this->sql->prepareStatementForSqlObject($action);
        
        $result = $stmt->execute();

        if ($result instanceof ResultInterface) {
            if ($key !== null && $newId = $result->getGeneratedValue()) {
            // When a value has been generated, set it on the object
                $id = $newId;
            }

            return $id;
        }

        throw new \ZCore\Exception\DatabaseException("Database error");
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

<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZCore\Mapper;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;

use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\ResultSetInterface;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect;

use ZCore\Model\AbstractModel;

abstract class AbstractMapper {
    
    /**
     * @var Adapter
     */
    protected $dbAdapter;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     *
     * @var Sql
     */
    protected $sql;
    
    /**
     *
     * @var string
     */
    protected $tableName = '';
    
    /**
     *
     * @var string
     */
    protected $tableFriendlyName = 'Entity';
    
    /**
     *
     * @var array
     */
    protected $tableFields = array();
    
    /**
     *
     * @var string | array
     */
    protected $tablePrimaryKey = 'id';
    
    /**
     *
     * @var boolean
     */
    protected $tablePrimaryKeyAutoIncrement = false;
    
    /**
     *
     * @var string
     */
    protected $tableAlias = 'e';
    
    /**
     * @param AdapterInterface      $dbAdapter
     * @param HydratorInterface     $hydrator
     * @param ModelInterface        $modelPrototype
     */
    public function __construct(
        AdapterInterface $dbAdapter
    ) {
        $this->dbAdapter = $dbAdapter;
        
        $this->sql = new Sql($this->dbAdapter);
    }
    
    public function getTableName() {
        return $this->tableName;
    }
    
    public function getTablePrimaryKey() {
        return $this->tablePrimaryKey;
    }
    
    public function isTableKeyAutoincrement() {
        return $this->tablePrimaryKeyAutoIncrement;
    }
    
    public function extractPrimaryKey(array $data = array()) {
        $entityKeys = array();
        
        $keys = $this->getTablePrimaryKey();
        
        if (is_array($keys)) {
            foreach ($keys as $keyField) {
                if (isset($data[$keyField])) {
                    $entityKeys[$keyField] = $data[$keyField];
                }
            }
        } else if ($keys) {
            if (isset($data[$keys])) {
                $entityKeys[$keys] = $data[$keys];
            }
        }
        
        return $entityKeys;
    }
    
    public function extractRowColumns(array $data = array(), $opts = array()) {
        return \ZCore\Extractor\ArrayExtractor::staticExtract($data, array_merge($opts, array(
            'properties' => $this->tableFields
        )));
    }
    
    public function getSelectColumns($alias = '', $prefix = '', $include = null, $exclude = array()) {
        $columns = array();
        
        foreach ($this->tableFields as $field) {
            if ($include && !in_array($field, $include)) {
                continue;
            } else if (in_array($field, $exclude)) {
                continue;
            }
            
            $columns[$prefix . $field] = ( empty($alias) ? '' : $alias . '.') . $field;
        }
        
        return $columns;
    }
    
    /**
     * 
     * @param Select $select
     * @param string $alias
     * @param mixed $on
     * @param array $selectConfig
     * @param string $type
     */
    public function joinWith(Select $select, $alias, $on, $selectConfig = array(), $type = Select::JOIN_LEFT) {
        $prefix = isset($selectConfig['prefix']) ? $selectConfig['prefix'] : '__' . $alias . '_';
        $include = isset($selectConfig['include']) ? $selectConfig['include'] : false;
        $exclude = isset($selectConfig['exclude']) ? $selectConfig['exclude'] : array();
        
        $columns = $this->getSelectColumns('', $prefix, $include, $exclude);
        
        $select->join(
            array($alias => $this->tableName), 
            $on,
            count($columns) > 0 ? $columns : array(),
            $type
        );
    }
    
    public function find($key, ResultSetInterface $resultSet = null, $criteria = array(), $order = array(), $include = array()) {
    // merge keys
        if (is_array($key)) {
            $criteria = array_merge($criteria, $key);
        } else if ($key) {
            $criteria = array_merge($criteria, array($this->tablePrimaryKey => $key));
        } else {
            throw new \InvalidArgumentException('You must provide a key to find by');
        }
        
        $select = $this->selectBy($criteria, $order, $include);
        $select->limit(1);
        
        return $this->executeSelectStatement($select, $resultSet);
    }
    
    /**
     * 
     * @param array $criteria
     * @param array $order
     * @param array $paging
     * @return \Api\Company\Mapper\ResultInterface|null
     */
    public function findAll($criteria = array(), ResultSetInterface $resultSetPrototype = null, $order = array(), $include = array()) {
        $select = $this->selectBy($criteria, $order, $include);
        
        return $this->executePaginatedSelectStatement($select, $resultSetPrototype);
    }
    
    /**
     * 
     * @param AbstractModel $entity
     */
    public function delete($entity) {
        $delete = $this->deleteBy($entity->getArrayCopy(array('value' => 'has')));
        return $this->executeDelete($delete);
    }
    
    /**
     * 
     * @param AbstractModel $entity
     */
    public function save($entity) {        
        return $this->saveBy($entity->getArrayCopy(array('value' => 'has')));
    }
    
    /**
     * Override end return false | null  to override default where assigment
     * You can implement joins and other criterias ti generate result
     * 
     * @param Select $select
     * @param array $criteria
     * @param array $include
     * @return array | boolean
     */
    protected function selectByCondition(Select $select, $criteria = null, $order = array(), $include = array()) {
        $condition = array();
        if (is_array($criteria)) {
            foreach ($criteria as $key => $value) {
                if (in_array($key, $this->tableFields)) {
                    $condition[$this->tableAlias . '.' . $key . ' = ?'] = $value;
                }
            }
        } else if (isset($criteria) && !is_null($criteria)) {
            $condition[$this->tableAlias . '.' . $this->tablePrimaryKey . ' = ?'] = $criteria;
        }
        
        return $condition;
    }
    
    /**
     * Override end return false | null  to override default where assigment
     * You can implement joins and other criterias ti generate result
     * 
     * @param Select $select
     * @param array $criteria
     * @param array $order
     * @param array $include
     * @return array
     */
    protected function orderByCondition(Select $select, $criteria = null, $order = array(), $include = array()) {        
        return $order;
    }
    
    /**
     * Override end return false | null  to override default where assigment
     * You can implement joins and other criterias ti generate result
     * 
     * @param Select $select
     * @param array $criteria
     * @param array $order
     * @param array $include
     * @return array
     */
    protected function columnsByCondition(Select $select, $criteria = null, $order = array(), $include = array()) {        
        return $this->getSelectColumns();
    }
    
    /**
     * 
     * @param type $criteria
     * @param array $order
     * @return Select
     */
    protected function selectBy($criteria = null, $order = array(), array $include = array()) {
        $select = $this->sql->select(array(
            $this->tableAlias => $this->tableName
        ));
        
        $selectBy = $this->selectByCondition($select, $criteria, $order, $include);
        
        $orderBy = $this->orderByCondition($select, $criteria, $order, $include);
        
        $columnsBy = $this->columnsByCondition($select, $criteria, $order, $include);
        
        if ($columnsBy) {
            $select->columns($columnsBy);
        }
        
        if ($selectBy) {
            $select->where($selectBy);
        }
        
        if ($orderBy) {
            $select->order($orderBy);
        }
        
        return $select;
    }
    
    /**
     * 
     * @param array $criteria
     * @param string $value
     * @return Delete
     */
    protected function deleteBy($criteria, $value = null) {
        $delete = $this->sql->delete($this->tableName);
        
        $condition = array();
        if (is_array($criteria)) {
            foreach ($criteria as $key => $value) {
                $condition[$key . ' = ?'] = $value;
            }
        } else {
            if (!is_null($value)) {
                $condition[$criteria . ' = ?'] = $value;
            } else {
                $condition[$this->tablePrimaryKey . ' = ?'] = $criteria;
            }
        }
        
        $delete->where($condition);
        return $delete;
    }
    
    /**
     * 
     * @param array $data
     * @param array $criteria
     * @param string $value
     * @return Update
     */
    protected function updateBy(array $data) {
        $update = $this->sql->update($this->tableName);
        
        $condition = array();
        if (is_array($this->tablePrimaryKey)) {
        // check if all keys are set
            foreach ($this->tablePrimaryKey as $keyField) {
                if (!isset($data[$keyField])) {
                    throw new \InvalidArgumentException("Unable to update! Primary key [{$keyField}] is not set.");
                }
            }
        // prepare data and criteria
            foreach ($this->tablePrimaryKey as $keyField) {
                $condition[$keyField . ' = ?'] = $data[$keyField];
                unset($data[$keyField]);
            }
        } else {
            if ($this->tablePrimaryKey) {
                if (!isset($data[$this->tablePrimaryKey])) {
                    throw new \InvalidArgumentException("Unable to update! Primary key [{$this->tablePrimaryKey}] is not set.");
                }
                
                $condition[$this->tablePrimaryKey . ' = ?'] = $data[$this->tablePrimaryKey];
                unset($data[$this->tablePrimaryKey]);
            } else {
                throw new \InvalidArgumentException("Unable to update! Primary key is not set.");
            }
        }
        
    // extract only data that is from this table
        $updateData = $this->extractRowColumns($data);
        
        $update->set($updateData);
        $update->where($condition);
        
        return $update;
    }
    
    /**
     * 
     * @param array $data
     * @param array | string $key
     * @param string $value
     * @return Insert
     */
    protected function insertBy(array $data) {
        $insert = $this->sql->insert($this->tableName);
        
        if (is_array($this->tablePrimaryKey)) {
        // we need to insert all the values
            foreach ($this->tablePrimaryKey as $keyField) {
                if (!isset($data[$keyField])) {
                    throw new \InvalidArgumentException("Unable to insert! Primary key [{$keyField}] is not set.");
                }
            }
        } else if ($this->tablePrimaryKey) {
            if ($this->tablePrimaryKeyAutoIncrement) {
            // primary key is incremental so we dont need it
//                unset($data[$this->tablePrimaryKey]);
                if (isset($data[$this->tablePrimaryKey])) {
                    throw new \InvalidArgumentException("Unable to insert! Primary key [{$this->tablePrimaryKey}] is allready set.");
                }
            } else {
            // we need all data
                if (!isset($data[$this->tablePrimaryKey])) {
                    throw new \InvalidArgumentException("Unable to insert! Primary key [{$this->tablePrimaryKey}] is not set.");
                }
            }
        }
    // extract only data that is from this table
        $insertData = $this->extractRowColumns($data);
        
    // could filter data
        $insert->values($insertData);
        
        return $insert;
    }
    
    /**
     * 
     * @param Select $select
     * @param ResultSetInterface $resultSet
     * @return ResultInterface
     * @throws \ZCore\Exception\DatabaseException
     * @throws \ZCore\Exception\EntryNotFoundException
     */
    protected function executeSelect(Select $select, ResultSetInterface $resultSet = null) {
        try {
            $statement = $this->sql->prepareStatementForSqlObject($select);
            $result    = $statement->execute();

            if ($result instanceof ResultInterface && $result->isQueryResult() && $result->getAffectedRows()) {
                if ($resultSet) {
                    $resultSet->initialize($result);
                }
                
                if ($result->count() > 0) {
                    return $result;
                }
            }
        } catch (Exception $e) {
            throw new \ZCore\Exception\DatabaseException('Query Error', null, $e);
        }
        
        throw new \ZCore\Exception\EntryNotFoundException("Query for {$this->tableFriendlyName} returned an empty result.");
    }
    
    /**
     * 
     * @param Select $select
     * @param ResultSetInterface $resultSetPrototype
     * @return Paginator
     */
    protected function prepareSelectPaginator(Select $select, ResultSetInterface $resultSetPrototype = null) {
        $paginatorAdapter = new DbSelect(
            $select,
            $this->dbAdapter,
            $resultSetPrototype
        );

        $paginator = new Paginator($paginatorAdapter);
        return $paginator;
    }
    
    /**
     * 
     * @param Insert $insert
     * @return ResultInterface
     * @throws \ZCore\Exception\EntrySaveException
     */
    protected function executeInsert(Insert $insert) {
        try {
            $stmt = $this->sql->prepareStatementForSqlObject($insert);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            throw new \ZCore\Exception\EntrySaveException("Insert error", null, $e);
        }
        
        return $result;
    }
    
    /**
     * 
     * @param Update $update
     * @return ResultInterface
     * @throws \ZCore\Exception\EntryUpdateException
     */
    protected function executeUpdate(Update $update) {
        try {
            $stmt = $this->sql->prepareStatementForSqlObject($update);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            throw new \ZCore\Exception\EntryUpdateException("Update error", null, $e);
        }
        
        return $result;
    }
    
    /**
     * 
     * @param Delete $delete
     * @return ResultInterface
     * @throws \ZCore\Exception\EntryDeleteException
     */
    protected function executeDelete(Delete $delete) {
        try {
            $stmt = $this->sql->prepareStatementForSqlObject($delete);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            throw new \ZCore\Exception\EntryDeleteException("Delete error", null, $e);
        }
        
        return $result;
    }
    
    /**
     * 
     * @param array $data
     * @param array $options
     * @return array
     * @throws \InvalidArgumentException
     * @throws \ZCore\Exception\EntrySaveException
     */
    public function saveBy(array $data, array $options = array()) {
        
        if ($this->tablePrimaryKeyAutoIncrement && (is_array($this->tablePrimaryKey || !$this->tablePrimaryKey))) {
            throw new \InvalidArgumentException('Settings not done correctly');
        }
        
        $insertAction = false;
        $updateAction = false;
        $updateActionFallback = !$this->tablePrimaryKeyAutoIncrement || is_array($this->tablePrimaryKey);
        
        if (!empty($this->tablePrimaryKey) && !is_array($this->tablePrimaryKey)) {
        // PRIMARY Key is single key
            if (isset($data[$this->tablePrimaryKey]) && $this->tablePrimaryKeyAutoIncrement) {
                $updateAction = true;
            } else {
                $insertAction = true;
            }
        } else if (is_array($this->tablePrimaryKey)) {
        // PRIMARY Key is multi key so it could exist or not
            $insertAction = true;
        } else {
            $insertAction = true;
        }
        
        if ($insertAction) {
            try {
                $insert = $this->insertBy($data);
                $result = $this->executeInsert($insert);

                if ($this->tablePrimaryKey && $this->tablePrimaryKeyAutoIncrement && !is_array($this->tablePrimaryKey)) {
                    $newId = $result->getGeneratedValue();
                    $data[$this->tablePrimaryKey] = $newId;
                }
                
                $updateActionFallback = false;
            } catch (\ZCore\Exception\EntrySaveException $insertException) {
            // wait for fallback update
                if (!$updateActionFallback) {
                    throw $insertException;
                }
            }
            
            return $data;
        }
        
        if ($updateAction || ($insertAction && $updateActionFallback)) {
            $update = $this->updateBy($data);
            $result = $this->executeUpdate($update);
        }
        
        return $data;
    }
    
    /**
     * 
     * @param Select $select
     * @param ResultSetInterface $resultSet
     * @return ResultInterface
     * @throws \ZCore\Exception\DatabaseException
     * @throws \ZCore\Exception\EntryNotFoundException
     */
    protected function executeSelectStatement(Select $select, ResultSetInterface $resultSet = null) {
        try {
            $statement = $this->sql->prepareStatementForSqlObject($select);
            $result    = $statement->execute();

            if ($result instanceof ResultInterface && $result->isQueryResult() && $result->getAffectedRows()) {
                if ($resultSet) {
                    $resultSet->initialize($result);
                }
                
                if ($result->count() > 0) {
                    return $result;
                }
            }
        } catch (Exception $e) {
            throw new \ZCore\Exception\DatabaseException('Query Error', null, $e);
        }
        
        throw new \ZCore\Exception\EntryNotFoundException("{$this->tableFriendlyName} with given ID not found.");
    }
    
    protected function executePaginatedSelectStatement(\Zend\Db\Sql\Select $select, ResultSetInterface $resultSetPrototype = null) {
        $paginatorAdapter = new DbSelect(
            $select,
            $this->dbAdapter,
            $resultSetPrototype
        );

        $paginator = new Paginator($paginatorAdapter);
        return $paginator;
    }
}

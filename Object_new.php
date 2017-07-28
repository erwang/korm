<?php

namespace KORM;

use KORM\Connection;

class Object {

    public static $_updateTableStructure = false;
    public static $_tableNameWithNamespace = false;
    private $_className;
    protected static $_tableName;
    private static $_cache = [];
    protected $id = null;

    private function __construct($id = null,$class=null) {
        $this->_className = $class;
        if (null==$id) {
            $this->_constructNewRow($class);
        } else {
            $this->id = $id;
            $query = 'select * from `' . self::_getTableName() . '` where `id`=?';
            $pdo = Connection::prepare($query);
            $pdo->execute([$id]);
            $attrs = $pdo->fetchAll(\PDO::FETCH_ASSOC);
            if (isset($attrs[0])) {
                foreach ($attrs[0] as $key => $value) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * 
     * @param type $id
     * @param type $nocache
     * @return \KORM\Object
     */
    static public function get($id = null, $nocache = false) {
        $class = get_called_class();
        $class::$_tableName = isset($class::$_tableName) ? $class::$_tableName : $class;
        if (null==$id or $nocache) {
            $object = new $class($id,$class);
            return $object;
        }
        //test if object is in cache
        if(!isset(self::$_cache[$class][$id])) {
            $object = new $class($id,$class);
            self::_cache($object);
        } else {
            $object = self::$_cache[$class][$id];
        }
        return $object;
    }
    private static function _cache($object){
        //create an array for this class
        if (!isset(self::$_cache[$object->_className])) {
            self::$_cache[$object->_className] = [];
        }
        self::$_cache[$object->_className][$object->id]=$object;
    }
    /**
     * 
     * @param String $name
     * @param array $arguments
     * @return Object
     */
    public function __call($name, $arguments) {
        if (substr($name, 0, 3) == 'set') {
            $key = strtolower(substr($name, 3, 1)) . substr($name, 4);
            $this->$key = $arguments[0];
        }
        return $this;
    }

    public function isInDatabase() {
        return is_numeric($this->id) and $this->id != 0;
    }

    public function populate($values) {
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }
    }

    public function store() {
        $vars = get_object_vars($this);

        /**
         * table creation or update
         */
        if (self::$_updateTableStructure) {
            if (!self::_tableExists()) {
                self::_createTable();
            }
//create fields
            $this->_createColumns();
        }
        /**
         * convert attributes to database data
         */
        foreach ($vars as $key => $value) {
            if (substr($key, 0, 1) == '_' or $key == 'id') {
                unset($vars[$key]);
            }
            if (is_object($value)) {
                $vars[$key . '_id'] = $value->id;
                unset($vars[$key]);
            }
            if (is_array($value)) {
                unset($vars[$key]);
            }
        }
        /**
         * create and execute query
         */
        if (!$this->isInDatabase()) {

            $query = 'insert into `' . self::_getTableName() . '`(`' . implode('`, `', array_keys($vars)) . '`) values (?' . str_repeat(', ?', sizeof($vars) - 1) . ')';
            $statement = Connection::prepare($query);
            $result = $statement->execute(array_values($vars));
            if (!$result) {
                throw new \Exception($statement->errorInfo()[2]);
            }
            $this->id = Connection::lastInsertId();
        } else {
            $query = 'update `' . self::_getTable() . '` set `' . implode('` = ?, `', array_keys($vars)) . '` = ? '
                    . 'where `id` = ?';

            $statement = Connection::prepare($query);
            $vars['id'] = $this->$id;
            $result = $statement->execute(array_values($vars));
            if (!$result) {
                throw new \Exception($statement->errorInfo()[2]);
            }
        }
        self::_cache($this);
        return $this;
    }

    public static function count($params = []) {
        $query = 'select count(*) from `' . self::_getTableName() . '` ';
        $where = [];
        $p = [];
        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                $where[] = '`' . $key . '`=?';
                $p[] = $value;
            }
            $query .= 'where ' . implode(' and ', $where);
        }
        $statement = Connection::prepare($query);
        $statement->execute($p);
        $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
        return $result[0];
    }
    private function _constructNewRow(String $class) {
        $vars = $class::_getColumns($class::_getTableName());
        var_dump($vars);
        foreach ($vars as $value) {
            $key = $value['Field'];
            $this->$key = $value['Default'];
        }
    }
    

    /**
     * drop table
     * @param boolean $foreignKeyCheck
     * @return PDOStatement
     */
    public static function drop($foreignKeyCheck = true) {
        if (!$foreignKeyCheck) {
            Connection::exec('SET FOREIGN_KEY_CHECKS = 0;');
        }
        $return = Connection::exec('drop table `' . self::_getTableName() . '`;');
        if (!$foreignKeyCheck) {
            Connection::exec('SET FOREIGN_KEY_CHECKS = 1;');
        }
        return $return;
    }

    /**
     * truncate table
     * @param boolean $foreignKeyCheck
     * @return PDOStatement
     */
    public static function truncate($foreignKeyCheck = true) {
        if (!$foreignKeyCheck) {
            Connection::exec('SET FOREIGN_KEY_CHECKS = 0;');
        }
        $return = Connection::exec('truncate `' . self::_getTableName() . '`;');
        if (!$foreignKeyCheck) {
            Connection::exec('SET FOREIGN_KEY_CHECKS = 1;');
        }
        return $return;
    }

//------------------------
// Table structure
//------------------------
    protected static function _getTableName() {
        $class = get_called_class();
        if (isset($class::$_table)) {
            return $class::$_table;
        } else {
            $array = explode('\\', $class);
            return strtolower($array[count($array) - 1]);
        }
    }

    /**
     * execute a query
     * @param string $query
     * @param array $params
     * @return integer
     */
    public static function exec($query, $params) {
        $statement = Connection::prepare($query);
        $statement->execute($params);

        return $statement->rowCount();
    }

    /**
     * create table if not exists
     * @return type
     */
    private static function _createTable() {
        return self::exec('create table ' . self::_getTableName() . '(`id`  INT NOT NULL AUTO_INCREMENT PRIMARY KEY)', []);
    }

    /**
     * return true if table exists
     * @return boolean
     */
    public static function _tableExists() {
        $query = 'show tables like "' . self::_getTableName() . '"';
        return sizeof(Connection::fetchAll($query)) > 0;
    }

    /**
     * get columns informations
     * @param string $table
     * @return array
     */
    public static function _getColumns($table = null) {
        if (is_null($table)) {
            $table = self::_getTableName();
        }
        $query = 'show columns from `' . $table . '`';
        if (self::_tableExists()) {
            return Connection::query($query)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    }

    /**
     * return column type
     * @param string $value
     * @return string
     */
    private static function _getColumnType($value) {
        $type = 'longtext';
        switch (gettype($value)) {
            case 'boolean':
                $type = 'tinyint(1)';
                break;
            case 'integer':
                $type = 'int(11)';
                break;
            case 'double':
                $type = 'float';
                break;
            case 'string':
                if (\DateTime::createFromFormat('Y-m-d', $value)) {
                    $type = 'date';
                } elseif (\DateTime::createFromFormat('H:i:s', $value) or \DateTime::createFromFormat('H:i', $value)) {
                    $type = 'time';
                } elseif (\DateTime::createFromFormat('Y-m-d H:i:s', $value) or \DateTime::createFromFormat('Y-m-d H:i', $value)) {
                    $type = 'datetime';
                } elseif (is_numeric($value)) {
                    if (intval($value) == $value) {
                        return self::_getColumnType(intval($value));
                    } else {
                        return self::_getColumnType(floatval($value));
                    }
                } elseif (strlen($value) > 250) {
                    $type = 'longtext';
                } else {
                    $type = 'varchar(250)';
                }
        }
        return $type;
    }

    /**
     * create columns from attributes value
     */
    private function _createColumns() {
        $columns = self::_getColumns();
        $vars = get_object_vars($this);

        foreach ($vars as $key => $value) {
            if (substr($key, 0, 1) != '_') {
                $trouve = false;
                $name = $key;
                $type = self::_getColumnType($value);
                $index = '';
                $null = '';
                $foreignKey = false;
                if (is_object($value)) {
                    $name .= '_id';
                    $referenceClass = get_class($value);
                    $value = $value->id;
                    $type = 'int(11)';
                    $null = 'NULL';
                    $index = ', ADD INDEX(`' . $name . '`)';
                    $foreignKey = true;
                    $referenceTable = $referenceClass::_getTableName();
                }
                if (is_array($value)) {
                    $trouve = true;
                    $value = 'NULL';
                }
                foreach ($columns as $column) {
                    if ($column['Field'] == $name) {
                        $trouve = true;
                        $c = $column;
                    }
                }
//create field
                if (!$trouve) {
//ALTER TABLE `board` ADD `label` VARCHAR(200) NOT NULL AFTER `id`; 
                    self::exec('ALTER TABLE `' . self::_getTableName() . '` ADD `' . $name . '` ' . $type . ' ' . ' ' . $null . ' ' . $index, []);
                    if ($foreignKey) {
                        self::exec('ALTER TABLE `' . self::_getTableName() . '` ADD FOREIGN KEY (`' . $name . '`) REFERENCES `' . $referenceTable . '`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;', []);
                    }
                } elseif (isset($c)) {
                    if ($type != $c['Type'] and $value !== 'NULL' and ! is_null($value)) {
                        $dataTypes = ['tinyint(1)', 'int(11)', 'float', 'date', 'time', 'datetime', 'varchar(250)', 'longtext'];
//update field
                        if (array_search($c['Type'], $dataTypes) < array_search($type, $dataTypes)) {
                            self::exec('ALTER TABLE `' . self::_getTableName() . '` CHANGE `' . $name . '` `' . $name . '` ' . $type, []);
                        }
                    }
                }
                unset($c);
            }
        }
    }

}

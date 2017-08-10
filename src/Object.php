<?php

/**
 * Object
 */

namespace KORM;

use KORM\Connection;

/**
 * class to convert table row to object
 */
class Object {

    /**
     * attribute to update database structure
     * @var boolean
     */
    public static $updateTableStructure = true;

    /**
     * name of the primary key column
     * @var string
     */
    protected static $_primaryKeyColumn = 'id';
    private static $_cache = [];
    protected $_className;
    protected static $_connection;

    /**
     * create an object
     * @param type $class
     */
    public function __construct($id = null) {
        $class=$this->_className = get_called_class();
        if (!$class::tableExists()) {
            self::_createTable();
        }
        if (is_null($id) and ( !isset($this->id) or is_null($this->id))) {
            $this->_constructNewRow($class);
        } else {
            $query = 'select * from `' . $class::_getTable() . '` where ' . self::$_primaryKeyColumn . '=?';
            $pdo = self::getConnection()->prepare($query);
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
     * use to create a new Object with no id in database
     * @param \KORM\String $class
     */
    private function _constructNewRow(String $class) {
        $vars = $class::getColumns($class::_getTable());
        foreach ($vars as $value) {
            $key = $value['Field'];
            $this->$key = $value['Default'];
        }
    }

    /**
     * return table name
     * @return string table name
     */
    public static function _getTable() {
        $class = get_called_class();
        if (isset($class::$_table)) {
            return $class::$_table;
        } else {
            $array = explode('\\', $class);
            return strtolower($array[count($array) - 1]);
        }
    }

    /**
     * get columns informations
     * @param string $table
     * @return array
     */
    public static function getColumns($table = null) {
        if (is_null($table)) {
            $table = self::_getTable();
        }
        $query = 'show columns from `' . $table . '`';
        if (self::tableExists()) {
            return self::getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            return [];
        }
    }

    /**
     * test column existence
     * @param string $column column name
     * @param string $table table name
     * @return boolean
     */
    public static function hasColumn($column, $table = null) {
        if (is_null($table)) {
            $table = self::_getTable();
        }
        if (self::tableExists()) {
            $query = 'show columns from `' . $table . '` where Field=?';
            $statement = self::getConnection()->prepare($query);
            $statement->execute([$column]);
            return count($statement->fetchAll()) > 0;
        } else {
            return false;
        }
    }

    /**
     * find objects with a select query
     * <code>
     * $booksInFrench=Book::where('language=:language',['language'=>'French']);
     * </code>
     * @param string $where
     * @param array $params
     * @param array $order
     * @return array
     */
    public static function where($where, $params = [], $order = null, $limit = null) {
        $query = 'select * from `' . self::_getTable() . '` where ' . $where . self::order($order) . self::limit($limit);
        return self::query($query, $params);
    }

    /**
     * find first object with a select query
     * <code>
     * $firstBookInFrench=Book::whereFirst('language=:language order by `date`',['language'=>'French']);
     * </code>
     * @param string $where
     * @param array $params
     * @return object
     */
    public static function whereFirst($where, $params = [], $order = null) {
        $array = self::where($where, $params, $order, '0,1');
        return isset($array[0]) ? $array[0] : null;
    }

    /**
     * find objects 
     * 
     * <code>
     * $booksInFrench=Book::find(['language'=>'French']);
     * </code>
     * @param type $params
     * @return type
     */
    public static function find($params, $order = null, $limit = null) {
        $where = [];
        $p = [];
        foreach ($params as $key => $value) {
            $where[] = '`' . $key . '`=?';
            $p[] = $value;
        }
        return self::where(implode(' and ', $where), $p, $order, $limit);
    }

    /**
     * find first object
     * 
     * <code>
     * $booksInFrench=Book::findFirst(['language'=>'French']);
     * </code>
     * @param type $params
     * @return self
     */
    public static function findOne($params, $order = null) {
        $array = self::find($params, $order, '0,1');
        return isset($array[0]) ? $array[0] : null;
    }

    /**
     * get all rows from class
     * @return array
     */
    public static function getAll($order = null) {
        $query = 'select * from `' . self::_getTable() . '`' . self::order($order);

        return self::query($query, []);
    }

    private static function order($order = null) {
        if (null != $order or sizeof($order) > 0) {
            $query = ' order by `' . implode('`,`', $order) . '`';
        } else {
            $query = '';
        }
        return $query;
    }

    public static function limit($limit) {
        if (null != $limit) {
            $query = ' limit ' . $limit;
        } else {
            $query = '';
        }
        return $query;
    }

    public static function count($params = []) {
        $query = 'select count(*) from `' . self::_getTable() . '` ';
        $where = [];
        $p = [];
        if (count($params) > 0) {
            foreach ($params as $key => $value) {
                $where[] = '`' . $key . '`=?';
                $p[] = $value;
            }
            $query .= 'where ' . implode(' and ', $where);
        }
        $statement = self::getConnection()->prepare($query);
        $statement->execute($p);
        $result = $statement->fetchAll(\PDO::FETCH_COLUMN);
        return $result[0];
    }

    /**
     * get objects from a query
     * @param string $query
     * @param array $params
     * @return array<Object>
     */
    public static function query($query, $params) {
        $statement = self::getConnection()->prepare($query);
        $statement->execute($params);

        return $statement->fetchAll(\PDO::FETCH_CLASS, get_called_class());
    }

    /**
     * return namespace
     * @return string
     */
    protected function _getNamespace() {
        $thisClass = get_class($this);
        return implode('\\', array_slice(explode('\\', $thisClass), 0, -1));
    }
    /**
     * determine $this class
     * @return String
     */
    protected function _getClass() {
        return isset($this->_className) ? $this->_className : get_class($this);
    }

    /**
     * return class name 
     * @param string $class
     * @param boolean $namespace
     * @return string
     */
    protected function _getClassBetween($class, $namespace = false) {
        $thisClass = $this->_getClass();
        $nsp = $this->_getNamespace();
        $array = [join('', array_slice(explode('\\', $class), -1)), join('', array_slice(explode('\\', $thisClass), -1))];
        sort($array);

        if ($namespace) {
            return $nsp . '\\' . implode('_', $array);
        } else {
            return implode('_', $array);
        }
    }

    /**
     * return object with relation
     * @param string $class
     * @return array
     */
    public function hasMany($class) {
        if (!class_exists($class)) {
            throw new \Exception('Class does not exists : ' . $class);
        }
        if ($class::hasColumn(self::_getTable() . '_id')) {
            return $class::find([self::_getTable() . '_id' => $this->id]);
//}elseif($class::hasColumn(self::_getTable() . '_id')){
        } elseif (class_exists($this->_getClassBetween($class, true))) {
            $classBetween = $this->_getClassBetween($class, true);
            $items = $classBetween::find([self::_getTable() . '_id' => $this->id]);
            $array = [];
            foreach ($items as $item) {
                $field = $class::_getTable() . '_id';
                $array[] = new $class($item->$field);
            }
            return $array;
        } else {
            return false;
        }
    }

    /**
     * execute a query
     * @param string $query
     * @param array $params
     * @return integer
     */
    public static function exec($query, $params) {
        $statement = self::getConnection()->prepare($query);
        $statement->execute($params);

        return $statement->rowCount();
    }

    /**
     * drop table
     * @param boolean $foreignKeyCheck
     * @return PDOStatement
     */
    public static function drop($foreignKeyCheck = true) {
        if (!$foreignKeyCheck) {
            self::getConnection()->exec('SET FOREIGN_KEY_CHECKS = 0;');
        }
        $return = self::getConnection()->exec('drop table `' . self::_getTable() . '`;');
        if (!$foreignKeyCheck) {
            self::getConnection()->exec('SET FOREIGN_KEY_CHECKS = 1;');
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
            self::getConnection()->exec('SET FOREIGN_KEY_CHECKS = 0;');
        }
        $return = self::getConnection()->exec('truncate `' . self::_getTable() . '`;');
        if (!$foreignKeyCheck) {
            self::getConnection()->exec('SET FOREIGN_KEY_CHECKS = 1;');
        }
        return $return;
    }

    /**
     * delete Object
     * @return integer
     */
    public function delete() {
        $return = $this->exec('delete from ' . self::_getTable() . ' where `' . self::$_primaryKeyColumn . '` = ?', [$this->id]);
        unset($this);
        return $return;
    }

    /**
     * destruct object
     */
    public function __destruct() {
        unset($this);
    }

    /**
     * return true if an object with $id exists
     * @param integer $id
     * @return boolean
     */
    public static function keyExists($id) {
        return count(self::find([self::$_primaryKeyColumn, $id])) > 0;
    }

    /**
     * return true if table exists
     * @return boolean
     */
    public static function tableExists() {
        $query = 'show tables like "' . self::_getTable() . '"';
        return count(self::getConnection()->fetchAll($query)) > 0;
    }

    public function isInDatabase() {
        $id = self::$_primaryKeyColumn;
        return isset($this->$id) and ! is_null($this->$id);
    }

    public function isEqualTo(Object $object){
        $vars = get_object_vars($this);
        foreach($vars as$key=>$value){
            if(!isset($object->$key) or $this->$key!==$object->$key){
                return false;
            }
        }
        return true;
    }
    /**
     * store an object in the database
     * @return \KORM\Object
     */
    public function store() {
        $id = self::$_primaryKeyColumn;

        $vars = get_object_vars($this);

        /**
         * table creation or update
         */
        if (self::$updateTableStructure) {
            if (!self::tableExists()) {
                self::_createTable();
            }
//create fields
            $this->_createColumns();
//-----
        }
        /**
         * convert attributes to database data
         */
        foreach ($vars as $key => $value) {
            if (substr($key, 0, 1) == '_' or $key == self::$_primaryKeyColumn) {
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
            if(!isset(self::$_cache['queries'][self::_getTable()]['insert'])){
                self::$_cache['queries'][self::_getTable()]['insert'] = 'insert into `' . self::_getTable() . '`(`' . implode('`, `', array_keys($vars)) . '`) values (?' . str_repeat(', ?', sizeof($vars) - 1) . ')';
            }

            $statement = self::getConnection()->prepare(self::$_cache['queries'][self::_getTable()]['insert']);
            $statement->execute(array_values($vars));
            $this->$id = self::getConnection()->lastInsertId();
        } else {
            if(!isset(self::$_cache['queries'][self::_getTable()]['update'])){
                self::$_cache['queries'][self::_getTable()]['update'] = $query = 'update `' . self::_getTable() . '` set `' . implode('` = ?, `', array_keys($vars)) . '` = ? '
                                        . 'where `' . self::$_primaryKeyColumn . '` = ?';
            }

            $statement = self::getConnection()->prepare(self::$_cache['queries'][self::_getTable()]['update']);
            $vars[self::$_primaryKeyColumn] = $this->$id;
            $result = $statement->execute(array_values($vars));
            if (!$result) {
                throw new \Exception($statement->errorInfo()[2]);
            }
        }


        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                $classBetween = $this->_getClassBetween(ucfirst($key), true);
                $items = $classBetween::find([$this->_getTable() . '_id' => $this->id]);
                foreach ($items as $item) {
                    $item->delete();
                }
                foreach ($value as $v) {
                    $item = new $classBetween();
                    $field1 = $this->_getTable();
                    $item->$field1 = $this;

                    $field2 = $key;
                    $item->$field2 = $v;
                    $item->store();
                }
                unset($vars[$key]);
            }
        }
        return $this;
    }

    /**
     * populate the object with array 
     * <code>
     * $data=['firstname'=>'Jules', 'lastname'=>'Verne'];
     * $author->populate($data);
     * </code>
     * @param array $params
     * @return \KORM\Object
     */
    public function populate(array $params) {
        $columns = self::getColumns();
        foreach ($columns as $column) {
            if (isset($params[$column['Field']])) {
                $c = $column['Field'];
                $this->$c = $params[$column['Field']];
            }
        }
        return $this;
    }

    /**
     * get an object from another class
     * @param string $name
     * @return KORM\Object
     */
    public function __get($name) {
        if ($name == self::$_primaryKeyColumn) {
            return null;
        }
        $class = ucfirst($name);
        $field = $name . '_id';
        if (isset($this->$field)) {
            $class = $this->getNamespace($this) . '\\' . ucfirst($name);
            return new $class($this->$field);
        }
        return $this->hasMany($this->_getNamespace() . '\\' . ucfirst($name));
    }

    protected function getNamespace($object = null) {
        if ($object !== null) {
            $tmp = (($object != "self") && (get_called_class() != get_class($object))) ? get_class($object) : get_called_class();
            $tmparr = explode("\\", $tmp);
            $class = array_pop($tmparr);
            return join("\\", $tmparr);
        } else {
            return __NAMESPACE__;
        }
    }

    /**
     * Update table structure
     */
    private static function _createTable() {
        return self::exec('create table ' . self::_getTable() . '(' . self::$_primaryKeyColumn . ' INT NOT NULL AUTO_INCREMENT PRIMARY KEY)', []);
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
        $columns = self::getColumns();
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
                    $referenceTable = $referenceClass::_getTable();
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
                    self::exec('ALTER TABLE `' . self::_getTable() . '` ADD `' . $name . '` ' . $type . ' ' . ' ' . $null . ' ' . $index, []);
                    if ($foreignKey) {
                        self::exec('ALTER TABLE `' . self::_getTable() . '` ADD FOREIGN KEY (`' . $name . '`) REFERENCES `' . $referenceTable . '`(`' . $referenceClass::$_primaryKeyColumn . '`) ON DELETE RESTRICT ON UPDATE RESTRICT;', []);
                    }
                } elseif (isset($c)) {
                    if ($type != $c['Type'] and $value !== 'NULL' and ! is_null($value)) {
                        $dataTypes = ['tinyint(1)', 'int(11)', 'float', 'date', 'time', 'datetime', 'varchar(250)', 'longtext'];
                        //update field
                        if (array_search($c['Type'], $dataTypes) < array_search($type, $dataTypes)) {
                            self::exec('ALTER TABLE `' . self::_getTable() . '` CHANGE `' . $name . '` `' . $name . '` ' . $type, []);
                        }
                    }
                }
                unset($c);
            }
        }
    }

    /**
     * return json value
     * @return string
     */
    public function __toString() {
        return json_encode($this);
    }

    public function beginTransaction() {
        return self::getConnection()->beginTransaction();
    }

    public function commit() {
        return self::getConnection()->commit();
    }

    public function rollback() {
        return self::getConnection()->rollBack();
    }

    /**
     * set Connection object
     * @param \KORM\Connection $connection
     */
    public static function setConnection(Connection $connection){
        self::$_connection=$connection;
    }

    /**
     * get Connection object
     * @return \KORM\Connexion
     */
    public function getConnection(){
        return self::$_connection;
    }
}

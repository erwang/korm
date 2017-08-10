<?php
/**
 * Connexion to the database
 */

namespace KORM;

/**
 * Connexion to the database
 */
class Connection {
    /**
     * store the PDO object
     * @var PDO
     */
    private $_pdo=null;
    /**
     * store the instance for singleton pattern
     * @var Connexion
     */
    private static $_instances=[];
    
    /**
     * Constructor
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param array $options
     */
    private function __construct($dsn,$username,$password,$options){
        try{
            $this->_pdo=new \PDO($dsn,$username,$password,$options);            
            
        }
        catch (Exception $e){
            die('Error : '.$e->getMessage());
        }
    }
    
    /**
     * Create an instance with setup
     * @param string $name
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param string $options
     */
    public static function setup($name,$dsn,$username=null,$password=null,$options=null){
        return self::$_instances[$name] = new self($dsn,$username,$password,$options);

    }
    
    /**
     * get the Connexion instance
     * @param string $name
     * @return KORM\Connexion
     * @throws \Exception
     */
    public static function get($name){
        if(is_null(self::$_instances)){
            throw new \Exception('You must call Connexion::setup() before');
        }
        return self::$_instances;
    }
    /**
     * Prepare a SQL query
     * @param string $query
     * @return PDOStatement
     */
    public function prepare($query){
        return $this->_pdo->prepare($query);
    }
    
    /**
     * execute a SQL Query
     * @param string $query
     * @return PDOStatement
     */
    public function query($query){
        return $this->_pdo->query($query);
    }
    /**
     * execute an exec query
     * @param type $query
     * @return type
     */
    public function exec($query){
        try {
            return $this->_pdo->exec($query);
        } catch (Exception $exc) {
            exit($exc->getTraceAsString());
        }
    }
    /**
     * return the last inserted id
     * @return int
     */
    public function lastInsertId(){
        return $this->_pdo->lastInsertId();
    }
    /**
     * get all rows froma query 
     * @param type $query
     * @param type $class
     * @return type
     */
    public function fetchAll($query,$class='stdClass'){
        return  $this->query($query,  \PDO::FETCH_CLASS,'stdClass')->fetchAll(\PDO::FETCH_CLASS,$class);
    }
    public function beginTransaction() {
        return $this->_pdo->beginTransaction();
    }
    public function commit() {
        return $this->_pdo->commit();
    }
    public function rollBack() {
        return $this->_pdo->rollBack();
    }
}
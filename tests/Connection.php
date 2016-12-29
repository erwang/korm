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
    private static $_instance;
    
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
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @param string $options
     */
    public static function setup($dsn,$username=null,$password=null,$options=null){
        self::$_instance = new self($dsn,$username,$password,$options);
    }
    
    /**
     * get the Connexion instance
     * @return KORM\Connexion
     * @throws \Exception
     */
    public static function get(){
        if(is_null(self::$_instance)){
            throw new \Exception('You must call Connexion::setup before');
        }
        return self::$_instance;
    }
    /**
     * Prepare a SQL query
     * @param string $query
     * @return PDOStatement
     */
    public static function prepare($query){
        return self::get()->_pdo->prepare($query);
    }
    
    /**
     * execute a SQL Query
     * @param string $query
     * @return PDOStatement
     */
    public static function query($query){
        return self::get()->_pdo->query($query);          
    }
    /**
     * execute an exec query
     * @param type $query
     * @return type
     */
    public static function exec($query){
        try {
            return self::get()->_pdo->exec($query);            
        } catch (Exception $exc) {
            exit($exc->getTraceAsString());
        }
    }
    /**
     * return the last inserted id
     * @return int
     */
    public static function lastInsertId(){
        return self::get()->_pdo->lastInsertId();        
    }
    /**
     * get all rows froma query 
     * @param type $query
     * @param type $class
     * @return type
     */
    public static function fetchAll($query,$class='stdClass'){
        return  self::get()->query($query,  \PDO::FETCH_CLASS,'stdClass')->fetchAll(\PDO::FETCH_CLASS,$class);        
    }
    
}
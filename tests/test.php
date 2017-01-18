<?php

include('./Connection.php');

use KORM\Connection;

Connection::setup('mysql:host=localhost;dbname=perso_korm', 'root', '123456', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

class Object {

    protected $id;

    public function __construct($id = null) {
        $this->id = $id;
        if ($id != null) {
            $query = 'select * from ' . strtolower(get_class($this)) . ' where id=' . $id;
            $result = Connection::query($query);
            $data = $result->fetchAll(\PDO::FETCH_ASSOC);
            if (isset($data[0])) {
                foreach ($data[0] as $key => $value) {
                    if ($key != 'id') {
                        $setter = 'set' . ucfirst($key);
                        $this->$setter($value);
                    }
                }
            }
        }
    }
    
    public function getId(){
        return $this->id;
    }

    public static function find($params) {
        $query = 'select * from ' . strtolower(get_called_class()) . ' where ';
        foreach ($params as $nomChamp => $valeur) {
            $query .= $nomChamp . '="' . $valeur . '" and ';
        }
        $query = substr($query, 0, -4);
        var_dump($query);
        $result = Connection::query($query);
        return $result->fetchAll(\PDO::FETCH_CLASS, get_called_class());
    }

    public static function findFirst($params) {
        $result = self::find($params);
        if (isset($result[0])) {
            return $result[0];
        } else {
            return null;
        }
    }

    public function store() {
        if (is_null($this->id)) {
            $this->insert();
        } else {
            $this->update();
        }
    }

    protected function update() {
        $vars = get_object_vars($this);
        $query = 'update ' . strtolower(get_class($this)) . ' set ';
        foreach ($vars as $nomAttribut => $valeur) {
            if ($nomAttribut != 'id') {
                $query .= $nomAttribut . '="' . $valeur . '",';
            }
        }
        $query = substr($query, 0, -1);
        $query .= ' where id=' . $this->id;
        var_dump($query);
        Connection::exec($query);
    }

    protected function insert() {
        $vars = get_object_vars($this);
        $query = 'insert into ' . strtolower(get_class($this)) . '(';
        foreach ($vars as $nomAttribut => $valeur) {
            if ($nomAttribut != 'id') {
                $query .= $nomAttribut . ',';
            }
        }
        $query = substr($query, 0, -1);

        $query .= ') values(';
        foreach ($vars as $nomAttribut => $valeur) {
            if ($nomAttribut != 'id') {
                $query .= '"' . $valeur . '",';
            }
        }
        $query = substr($query, 0, -1) . ')';
        var_dump($query);
        Connection::exec($query);
    }

}

class Book extends Object {

    protected $title;
    protected $published='2016';
    protected $isbn;
    protected $pages=0;
    protected $author_id;
    protected $publisher_id;

    function getTitle() {
        return $this->title;
    }

    function getPublished() {
        return $this->published;
    }

    function getIsbn() {
        return $this->isbn;
    }

    function getPages() {
        return $this->pages;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setPublished($published) {
        $this->published = $published;
    }

    function setIsbn($isbn) {
        $this->isbn = $isbn;
    }

    function setPages($pages) {
        $this->pages = $pages;
    }

    function setAuthor(Author $author) {
        $this->author_id = $author->getId();
    }

    function getAuthor() {
        return new Author($this->author_id);
    }

    function setPublisher(Publisher $publisher){
        $this->publisher_id=$publisher->getId();
    }    
    function getPublisher(){
        return new Publisher($this->publisher_id);
    }
}

class Author extends Object {

    protected $lastname;
    protected $firstname;
    protected $birthDate = '2016-12-23';
    protected $deathDate = '2016-12-23';

    public function getLastname() {
        return $this->lastname;
    }

    public function setLastname($lastname) {
        if (is_string($lastname)) {
            $lastname = strtoupper($lastname);
            return $this->lastname = $lastname;
        }
    }

    function getFirstname() {
        return $this->firstname;
    }

    function getBirthDate() {
        return $this->birthDate;
    }

    function getDeathDate() {
        return $this->deathDate;
    }

    function setFirstname($firstname) {
        $this->firstname = $firstname;
    }

    function setBirthDate($birthDate) {
        $this->birthDate = $birthDate;
    }

    function setDeathDate($deathDate) {
        $this->deathDate = $deathDate;
    }

    public function getBooks() {
        return Book::find(['author_id' => $this->id]);
    }

}

class Publisher extends Object{
    protected $name;
    function getName() {
        return $this->name;
    }

    function setName($name) {
        $this->name = $name;
    }
}
error_reporting(E_ALL);

$hemingway = new Author(1);

$publisher = new Publisher(1);

$book=new Book();
$book->setTitle('Test');
$book->setAuthor($hemingway);
$book->setPublisher($publisher);

$book->store();
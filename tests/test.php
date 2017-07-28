<?php

include('../src/Connection.php');
include('../src/Object.php');

include('./Author.php');
include('./Book.php');
include('./Publisher.php');



use KORM\Connection, KORM\Tests\Author, KORM\Tests\Publisher, KORM\Tests\Book;

Connection::setup('mysql:host=localhost;dbname=perso_korm', 'phpmyadmin', '123456', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

error_reporting(E_ALL);

Book::drop();
Author::drop();

KORM\Object::$_updateTableStructure=true;

$author = Author::get();
$author->setLastName('Hugo');
$author->setFirstName('Victor');
$author->store();


$book=Book::get();
$book->setTitle('Les Misérables');
$book->setAuthor($author);
$book->store();

var_dump($book);
var_dump($author);

$author2 = Author::get(1);
var_dump($author2);
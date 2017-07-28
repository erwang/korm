<?php

namespace KORM\Tests;

require_once (__DIR__ . '/../vendor/autoload.php');
include(__DIR__ . '/Author.php');
include(__DIR__ . '/Book.php');
include(__DIR__ . '/Publisher.php');
include(__DIR__ . '/Tag.php');
include(__DIR__ . '/Book_Tag.php');

use \PHPUnit\Framework\TestCase;
use KORM\Object;

class ObjectTest extends TestCase {

    public function setUp() {
        \KORM\Connection::setup('mysql:host=localhost;dbname=perso_korm', 'phpmyadmin', '123456', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
        Author::truncate(false);
        Author::drop(false);
        Book::drop(false);
        Publisher::drop(false);
        Tag::drop(false);
        Book_Tag::drop(false);

        $author = new Author();
        $author->firstname = 'Ernest';
        $author->lastname = 'Hemingway';
        $author->birthDate = '1899-07-21';
        $author->deathDate = '1961-07-2';
        $author->store();

        $book = new Book();
        $book->title = 'The Old Man and the Sea';
        $book->published = 1952;
        $book->isbn = '0-684-80122-1';
        $book->pages = 127;
        $book->store();

        $publisher = new Publisher();
        $publisher->name = 'Charles Scribner\'s Sons';
        $publisher->store();

        $book->author = $author;
        $book->publisher = $publisher;

        $tag1 = new Tag();
        $tag1->label = 'English';
        $tag1->store();
        $tag2 = new Tag();
        $tag2->label = 'Literary Fiction';
        $tag2->store();
        $tag3 = new Tag();
        $tag3->label = 'None';
        $tag3->store();


        $book->tag = [$tag1, $tag2];
        $book->store();
    }

    public function testAuthor() {
        $author = new Author(1);
        $this->assertEquals('Ernest', $author->firstname);
        $this->assertEquals('Hemingway', $author->lastname);
        

        $author->delete();
        $this->assertEquals(false, Author::keyExists(1));
    }

    public function testFindOne() {
        $ernest = Author::findOne(['lastname' => 'Hemingway']);
        $this->assertInstanceOf('KORM\Tests\Author', $ernest);
    }

    public function testManyToMany() {
        $tag = Tag::findOne(['label' => 'English']);
        $this->assertCount(1, $tag->book);
        $this->assertEquals(1, Tag::count(['label' => 'English']));
    }

    public function testGetAll() {
        $this->assertCount(3, Tag::getAll());
    }

    public function testPopulate() {
        $data['firstname'] = 'Victor';
        $data['lastname'] = 'Hugo';
        $hugo = new Author();
        $hugo->populate($data);
        $this->assertEquals('Victor', $hugo->firstname);        
    }
    public function testIsEqualTp(){
        $author1 = new Author(1);
        $author2 = new Author(1);
        $this->assertEquals(true, $author1->isEqualTo($author2));
        
        $author3 = new Author();
        $this->assertEquals(false, $author1->isEqualTo($author3));
    }
}

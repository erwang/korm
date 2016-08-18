# orm_perso
ORM in PHP


## Setup

The Connection class setup must be call first.
``` php
\KORM\Connection::setup('pdo_dsn', 'username', 'password');
```

A connection to a mysql database :
``` php
\KORM\Connection::setup('mysql:host=localhost;dbname=database', 'username', 'password');
```
with options :
``` php
\KORM\Connection::setup('mysql:host=localhost;dbname=database', 'username', 'password', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
```

##Create a class
Each table in database requires a class with the same name :
``` php
class Table extends \KORM\Object{
}
```
_name class is converted to lower case_
For example, to store books :
``` php
class Book extends \KORM\Object{
}
```
The `Book` objects will be store in `book` table

##Get a row
``` php
$book = new Book($id);
```
_will load in `$book` all the data in table `book` with id=1

##Create a row
``` php
$book = new Book();
```

##Store an object
``` php
$book = new Book($id);
$book->title='Les Misérables';
$book->store();
```

##Delete an object
``` php
$book = new Book($id);
$book->delete();
```

##Relations

###One to many
``` php
//create a book
$lesMiserables = new Book();
$lesMiserables->title='Les Misérables';
$lesMiserables->store();

//create an author
$hugo=new Author();
$hugo->name='Victor Hugo';
$hugo->store();

//create a relation
$lesMiserables->author=$hugo;
$lesMiserables->store();

//get the book
$book = new Book($lesMiserables->id);
$author = $book->author; //return the object Author from table author
```
### many to many
``` php
//create tags
$tag1=new Tag();
$tag1->text='french';
$tag1->store();

$tag2=new Tag();
$tag2->text='Roman';
$tag2->store();
$lesMiserables->tag=\[$tag1,$tag2\];
```
##Count
//get the number of books
Book::count();

//get the number of books from an author
Book::count(['author_id'=>$author->id]);



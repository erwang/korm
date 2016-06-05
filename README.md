# orm_perso
ORM in PHP


## Setup

The Connexion class setup must be call first.
``` php
\KORM\Connexion::setup('pdo_dsn', 'username', 'password');
```

A connexion to a mysql database :
``` php
\KORM\Connexion::setup('mysql:host=localhost;dbname=database', 'username', 'password');
```
with options :
``` php
\KORM\Connexion::setup('mysql:host=localhost;dbname=database', 'username', 'password', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
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
$book = Book::get($id);
```
_will load in `$book` all the data in table `book` with id=1

##Create a row
``` php
$book = Book::newItem();
```

##Store an object
``` php
$book = Book::get($id);
$book->title='Les Misérables';
$book->store();
```

##Delete an object
``` php
$book = Book::get($id);
$book->delete();
```

##Relations
``` php
$book = Book::get($id);
$author = $book->author; //return the object Author from table author
```


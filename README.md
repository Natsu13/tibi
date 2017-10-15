# tibi
When you want text file because there is no database but you have alerady your project on dibi then just include tibi and continue!

you must include 
tibi.php and config.php

then you just use like dibi
dibi::query("SELECT * FROM table");

it returns TibiResult it's iterable and countable.
You can you se $result->sql to show the constructed sql
->fetch() for first result
or foreach

For connect you need add this parameter 

$options = array(
			'folder'   => './data/',
			'root'	   => null
		);
dibi::connect($options);

You don't need the root

_Whats work:_
ALTER TABLE table DROP column
ALTER TABLE table ADD column datetype(size) [auto_increment] [primary]
ALTER TABLE table MODIFY COLUMN column datetype(size) [auto_increment] [primary]
ALTER TABLE table RENAME COLUMN column TO newcolumn
DESCRIBE table
CREATE TABLE table
SHOW TABLES
SELECT * FROM table [WHERE left = right]
INSERT INTO table (there you must send in second argument the array of data, you can call this command like a text (*1*))
UPDATE table SET (*1*) [WHERE left = right]
DELETE FROM table [WHERE left = right]

_Whats not work:_
LIKE in WHERE
SELECT column, column2, ...
JOIN

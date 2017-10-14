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

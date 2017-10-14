<style>
table tr td {
	padding:5px;
}
table tr th {
	padding:5px;
	background: silver;
}
input { padding:4px; }
form {margin:0px;}
</style>
<?php
include "tibi.php";
include "../../library/config.php";
$config = new Config(null);

$options = array(
			'folder'   => './data/',
			'root'	   => null
		);
dibi::connect($options);

if(isset($_POST["addt"])){
	$result = dibi::query("CREATE TABLE ".$_POST["addtable"]);
	header("refresh:0");
}

echo "<table>";
	echo "<tr><td valign=top width=203>";
		echo "<b>Tables</b>";
		$result = dibi::query("SHOW TABLES");		
		foreach($result as $n => $row){
			echo "<div><a href='?showtable=".$row["name"]."&type=schema' style='display:block;padding:3px;".($_GET["showtable"]==$row["name"]?"background:orange;":"")."'>".$row["name"]."</a></div>";
		}
		echo "<hr><form action=# method=post><input type=text name=addtable><input type=submit name=addt value='+'></form>";
		echo "<hr><small>Using Tibi databse engine</small>";
	echo "</td><td valign=top>";
	if(isset($_GET["showtable"])){
		$table = $_GET["showtable"];
		$action = $_GET["type"];
		
		if($action == "schema"){
			if(isset($_GET["edit"])){
				if(isset($_POST["edit"])){
					$t = "ALTER TABLE ".$table." RENAME COLUMN ".$_GET["edit"]." TO ".$_POST["name"]." ".$_POST["type"]."";
					if($_POST["size"] != "") $t.="(".$_POST["size"].")";
					if($_POST["special"] != "") $t.=" ".$_POST["special"]."";
					if($_POST["others"] != "") $t.=" ".$_POST["others"]."";
					$result = dibi::query($t);	
					header("location:?showtable=".$table."&type=schema&edit=".$_POST["name"]."");
				}
				$result = dibi::query("describe ".$table." WHERE name = %d", $_GET["edit"])->fetch();
				echo "<b>Editing column <i>".$_GET["edit"]."</i> in table <i>".$table."</i></b>";
				echo "<form action=# method=post><table>";
					echo "<tr><td>Name</td><td><input type=text name=name value='".$result["name"]."'></td></tr>";
					echo "<tr><td>Type</td><td><input type=text name=type value='".$result["type"]."'></td></tr>";
					echo "<tr><td>Size</td><td><input type=text name=size value='".$result["size"]."'></td></tr>";
					echo "<tr><td>Special</td><td><input type=text name=special value='".$result["special"]."'></td></tr>";
					echo "<tr><td>Others</td><td><input type=text name=others value='".$result["others"]."'></td></tr>";
					echo "<tr><td></td><td><input type=submit name=edit value='Edit' style='width:60px;'></td></tr>";
				echo "</table></form>";
			}
			else if(isset($_GET["delete"])){
				$t = "ALTER TABLE ".$table." DROP ".$_GET["delete"];
				$result = dibi::query($t);	
				header("location:?showtable=".$table."&type=schema");
			}else{
				if(isset($_POST["addnew"])){
					$t = "ALTER TABLE ".$table." ADD ".$_POST["name"]." ".$_POST["type"]."";
					if($_POST["size"] != "") $t.="(".$_POST["size"].")";
					if($_POST["special"] != "") $t.=" ".$_POST["special"]."";
					if($_POST["others"] != "") $t.=" ".$_POST["others"]."";
					$result = dibi::query($t);	
					header("location:?showtable=".$table."&type=schema");
				}
				echo "Schema | <a href='?showtable=".$_GET["showtable"]."&type=data'>Data</a> | <a href='?showtable=".$_GET["showtable"]."&type=settings'>Settings</a>";
				echo '<form action=# method=post><table border="1" style="table-layout: fixed; border-collapse: collapse;">';
				echo "<tr><th>Name</th><th>Type</th><th>Size</th><th>Special</th><th>Others</th><th>Actions</th></tr>";
				$result = dibi::query("describe ".$table);
				foreach($result as $n => $row){
					echo "<tr>";
						echo "<td>".$n."</td>";
						echo "<td>".$row["type"]."</td>";
						if(isset($row["size"])) echo "<td>".$row["size"]."</td>"; else echo "<td></td>";
						if(isset($row["special"])) echo "<td>".$row["special"]."</td>"; else echo "<td></td>";
						if(isset($row["others"])) echo "<td>".$row["others"]."</td>"; else echo "<td></td>";
					echo "<td><a href='?showtable=".$table."&type=schema&edit=".$n."'>Edit</a> <a href='?showtable=".$table."&type=schema&delete=".$n."'>Delete</a></td>";
					echo "</tr>";
				}
				echo "<tr>";
				echo "<td><input type=text name=name></td>";
				echo "<td><input type=text name=type style='width:100px;'></td>";
				echo "<td><input type=text name=size style='width:50px;'></td>";
				echo "<td><input type=text name=special></td>";
				echo "<td><input type=text name=others style='width:100px;'></td>";
				echo "<td><input type=submit name=addnew value=Add></td>";
				echo "</tr>";
				echo "</table></form>";
			}
		}else if($action == "settings"){
			echo "<a href='?showtable=".$_GET["showtable"]."&type=schema'>Schema</a> | <a href='?showtable=".$_GET["showtable"]."&type=data'>Data</a> | Settings";
		}else{
			if(isset($_GET["edit"])){
				$result = dibi::query("describe ".$table);
				
				if(isset($_POST["edit"])){
					$data = array();
					foreach($result as $n => $row){
						$data[$n] = $_POST["input_".$n];						
					}
					$result_ = dibi::query('UPDATE '.$table.' SET ', $data, 'WHERE `id`=%s', $_GET["edit"]);
					header("location:?showtable=".$_GET["showtable"]."&type=data");
				}
				
				$data = dibi::query("SELECT * FROM ".$table." WHERE id=%s", $_GET["edit"])->fetch();
				echo "<b>Editing record in table <i>".$table."</i></b>";
				echo "<form action=# method=post><table border=\"1\" style=\"table-layout: fixed; border-collapse: collapse;\"><tr><th>Name</th><th>Type</th><th>Value</th></tr>";			
				foreach($result as $n => $row){
					echo "<tr><td valign=top><b>".$n."</b></td>";
					echo "<td valign=top>".$row["type"];
					if($row["size"] != "")
						echo "(".$row["size"].")";
					echo "</td>";
					echo "<td>";
					if($row["type"] == "text" or ($row["type"] == "varchar" and $row["size"] > 100))
						echo "<textarea rows=3 style='width:250px;' name='input_".$n."'>".$data[$n]."</textarea>";
					else if($row["type"] == "int"){
						echo "<input type=number max=".(pow(10, $row["size"])-1)." name='input_".$n."' value='".$data[$n]."' style='width:100%;'>";						
					}else
						echo "<input type=text name='input_".$n."' style='width:100%;' value='".$data[$n]."'>";
					echo "</td></th>";
				}
				echo "<tr><td colspan=3 align=right><input type=submit name=edit value=Edit style='width:80px;'></td></tr>";
				echo "</table></form>";
			}
			else if(isset($_GET["delete"])){
				dibi::query('DELETE FROM '.$table.' WHERE id=%s', $_GET["delete"]);
				header("location:?showtable=".$_GET["showtable"]."&type=data");
			}
			else if(isset($_GET["add"])){
				$result = dibi::query("describe ".$table);
				if(isset($_POST["add"])){
					$data = array();
					foreach($result as $n => $row){
						$data[$n] = $_POST["input_".$n];
					}
					$result = dibi::query("INSERT INTO ".$table, $data);
					header("location:?showtable=".$_GET["showtable"]."&type=data");
				}
				echo "<b>Add record to table <i>".$table."</i></b>";
				echo "<form action=# method=post><table border=\"1\" style=\"table-layout: fixed; border-collapse: collapse;\"><tr><th>Name</th><th>Type</th><th>Value</th></tr>";			
				foreach($result as $n => $row){
					echo "<tr><td valign=top><b>".$n."</b></td>";
					echo "<td valign=top>".$row["type"];
					if($row["size"] != "")
						echo "(".$row["size"].")";
					echo "</td>";
					echo "<td>";
					if($row["type"] == "text" or ($row["type"] == "varchar" and $row["size"] > 100))
						echo "<textarea rows=3 style='width:250px;' name='input_".$n."'></textarea>";
					else if($row["type"] == "int"){
						echo "<input type=number max=".(pow(10, $row["size"])-1)." name='input_".$n."' style='width:100%;'>";
						if($row["special"] == "AUTO_INCREMENT")
							echo "<br><small>Empty for AUTO_INCREMENT value</small>";
					}else
						echo "<input type=text name='input_".$n."' style='width:100%;'>";
					echo "</td></th>";
				}
				echo "<tr><td colspan=3 align=right><input type=submit name=add value=Add style='width:80px;'></td></tr>";
				echo "</table></form>";
			}else{
				echo "<a href='?showtable=".$_GET["showtable"]."&type=schema'>Schema</a> | Data | <a href='?showtable=".$_GET["showtable"]."&type=settings'>Settings</a>";
				echo '<table border="1" style="table-layout: fixed; border-collapse: collapse;">';
				echo "<tr>";
				$countt = 0;
				$result = dibi::query("describe ".$table);
				foreach($result as $n => $row){
					echo "<th>".$n."</th>";
					$countt++;
				}
				echo "<th>Actions</th></tr>";
				$result = dibi::query("SELECT * FROM ".$table);
				if($result->count() == 0)
					echo "<tr><td colspan=".($countt+1)."><i>Tibi return zero result</i></td></tr>";
				foreach($result as $n => $row){
					foreach($row as $q){
						if(strlen($q) > 35)
							echo "<td>".str_replace("\n", "<br>", substr($q,0,35)."...")."</td>";
						else
							echo "<td>".str_replace("\n", "<br>", $q)."</td>";
					}
					echo "<td><a href='?showtable=".$table."&type=data&edit=".$n."'>Edit</a> <a href='?showtable=".$table."&type=data&delete=".$n."'>Delete</a></td>";
					echo "</tr>";
				}
				echo "</tr></table>";
				echo "[ <a href='?showtable=".$_GET["showtable"]."&type=data&add'> + Add new record</a> ]";
			}
		}
	}
	echo "<div style='border:1px solid silver;margin:4px;padding:5px;width:400px;margin-top:20px;'>";
		echo "<form action=# method=post><b>Run SQL</b>";
		echo "<br><textarea name=sql rows=3 style='width:400px;'>".(isset($_POST["sql"])?$_POST["sql"]:"SELECT * FROM ".$table)."</textarea>";
		echo "<br><input type=submit name=execute value=Execute></form>";
		if(isset($_POST["sql"])){
			$result = dibi::query($_POST["sql"]);
			echo "Result ".count($result)." in ".$result->totalTime."<br>";
			echo "<table border=\"1\" style=\"table-layout: fixed; border-collapse: collapse;\">";
			if($result->fetch() == null) echo "<i>Tibi return zero result</i>";			
			foreach($result as $n => $re){
				echo "<tr><td>".$n."</td>";
				foreach($re as $q){
					echo "<td>".$q."</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		}
	echo "</div>";
	echo "</td></tr>";
echo "<table>";
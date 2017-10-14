<?php
class Substitutes {
	public $prefix = "";
}

class TibiWhere {
	public $left = null;
	public $how  = "";
	public $right = null;	
	public $condition = null;
	
	public function __construct($left, $how, $right, $condition){
		$this->left = $left;
		$this->how = $how;
		$this->right = $right;
		$this->condition = $condition;
		
		if(substr($this->left, 0, 1) == "`" or substr($this->left, 0, 1) == "\"" or substr($this->left, 0, 1) == "'")
			$this->left = substr($this->left, 1, strlen($this->left)-2);
		if(substr($this->right, 0, 1) == "`" or substr($this->right, 0, 1) == "\"" or substr($this->right, 0, 1) == "'")
			$this->right = substr($this->right, 1, strlen($this->right)-2);
	}
	
	public function match($value, $last_resul = null){
		//echo "value: ".$value.", ";		
		//echo "<br>---<br>".$this->left." ".$this->how." ".$this->right."<br>";
		
		if($this->how == "="){
			if($value == $this->right)
				return $this->ret($last_resul, true);
		}else if($this->how == "!="){			
			if($value != $this->right)
				return $this->ret($last_resul, true);
		}else if($this->how == "<="){
			if($value <= $this->right)
				return $this->ret($last_resul, true);
		}else if($this->how == ">="){			
			if($value >= $this->right)
				return $this->ret($last_resul, true);
		}
		return $this->ret($last_resul, false);
	}
	public function ret($last, $hnow){		
		//echo "<br>---<br>".$this->left." ".$this->how." ".$this->right." (".($last?"true":"false")." ".$this->condition." ".($hnow?"true":"false").")<br>";
		if($last === null){
			//if($hnow === null) echo " xNULLx ";
			//echo "<b>last is null(".$hnow.")</b><br>";
			return $hnow;
		}
		if($this->condition == "AND"){
			//echo "<br>".($last?"true":"false")." - ".($hnow?"true":"false")."<br>";
			if($last and $hnow)
				return true;
		}
		else if($this->condition == "OR"){
			if($last or $hnow)
				return true;
		}
		//echo "<u>return false</u> ";
		return false;
	}
}

class TibiResult implements Iterator, Countable {
	private $data = null;
	private $data_keys = array();
	private $position = 0;
	public $sql = "";
	public $totalTime = 0;
	
	public function setData($array){ 
		$this->data = $array; 
		foreach($array as $n => $a){
			if($n == "__table_data") continue;
			$this->data_keys[] = $n;
		}
	}
	public function fetch(){ return (count($this->data_keys) == 0 ? null : $this->data[$this->data_keys[$this->position++]]); }
	
	public function __construct() { $this->position = 0; }
    public function rewind() { $this->position = 0; }
    public function current() { return $this->data[$this->data_keys[$this->position]]; }
    public function key() { return $this->data_keys[$this->position]; }
    public function next() { ++$this->position; }
    public function valid() { return isset($this->data_keys[$this->position]) and isset($this->data[$this->data_keys[$this->position]]); }
	public function count() { return count($this->data_keys); } 
}

class dibi {
	public static $folder = "data";
	private static $root = null;
	public static $numOfQueries = 0;
	public static $totalTime = 0;
	
	static function connect($options){
		dibi::$substitutes = new Substitutes();
		if(isset($options["folder"])){ dibi::$folder = $options["folder"]; }
		if(isset($options["root"])){   dibi::$root = $options["root"]; }
		return true;
	}
	
	static function getConnection(){
		return null;
	}
	
	static $substitutes = null;
	static function getSubstitutes(){
		return dibi::$substitutes;
	}
	
	static $_token_pos = array();
	static $_token_tex = array();
	static function getNextToken($ii){
		$_ret = "";
		$_tmp = "";		
		for($i = dibi::$_token_pos[$ii]; $i < strlen(dibi::$_token_tex[$ii]); $i++){
			$curr_char = substr(dibi::$_token_tex[$ii], $i, 1);
			$curr_twoc = substr(dibi::$_token_tex[$ii], $i, 2);
			if($curr_char == " " and trim($_tmp) != ""){
				dibi::$_token_pos[$ii] = $i;
				if(substr($_tmp, 0, 1) == "`")
					$_tmp = substr($_tmp, 1, strlen($_tmp)-2);
				return trim($_tmp);
			}else if($curr_char == "("){
				dibi::$_token_pos[$ii] = $i + 1;
				return trim($_tmp);
			}else if($curr_twoc == ">=" or $curr_twoc == "<=" or $curr_twoc == "!="){				
				dibi::$_token_pos[$ii] = $i;
				if(trim($_tmp) != "")
					return trim($_tmp);
				dibi::$_token_pos[$ii]+=2;
				return $curr_twoc;
			}else if($curr_char == "="){
				dibi::$_token_pos[$ii] = $i;
				if(trim($_tmp) != "")
					return trim($_tmp);	
				dibi::$_token_pos[$ii]+=1;
				return "=";
			}else if($curr_char == ")"){
				dibi::$_token_pos[$ii] = $i + 1;
				return trim($_tmp);
			}else{
				$_tmp .= $curr_char;
			}
		}
		if($_tmp != ""){
			dibi::$_token_pos[$ii] = $i+1;
			return trim($_tmp);			
		}
		return trim($_ret);
	}
		
	static function whereCompile($where, $ii = 0, $right = false){
		$return = array();
		dibi::$_token_tex[$ii] = $where;
		dibi::$_token_pos[$ii] = 0;		
		
		$left = "";
		$how = "";
		$condition = "";
		
		$token = dibi::getNextToken($ii);
		$lastToken = "";
		while($token != ""){
			//echo "Token: '".$token."'<br>";
			$lastToken = $token;
			if($left == ""){
				$left = $token;
				if($right){
					//echo "set right to: ".$left."<br>";
					return $left;
				}else{
					//echo "set left to: ".$left."<br>";
				}
			}
			else if($token == "="){
				$return[] = new TibiWhere($left, "=", dibi::whereCompile(substr($where, dibi::$_token_pos[$ii], strlen($where)), $ii+1, true), $condition);
				dibi::$_token_pos[$ii] += dibi::$_token_pos[$ii+1];
			}else if($token == "!=" or $token == ">=" or $token == "<="){				
				$return[] = new TibiWhere($left, $token, dibi::whereCompile(substr($where, dibi::$_token_pos[$ii], strlen($where)), $ii+1, true), $condition);
				dibi::$_token_pos[$ii] += dibi::$_token_pos[$ii+1];
			}else if(strtolower($token) == "and"){
				//echo "condition: ".$token."<br>";
				$condition = "AND";
				$left = "";
			}else if(strtolower($token) == "or"){
				//echo "condition: ".$token."<br>";
				$condition = "OR";
				$left = "";
			}
			$token = dibi::getNextToken($ii);
		}		
		
		if(count($return) == 0)
			return $lastToken;
		return $return;
	}
	
	static function query($data){
		$sql = "";
		$time_start = microtime();
		dibi::$numOfQueries++;
		
		$select = array();
		$from = "";
		$where = array();
		
		$type_act = 0;
		$_where = array();
		$_where_i = 0;
		
		$tibi_result = new TibiResult();
		$__return_as_schema = false;
		
		$_replace_column_id   = null;
		$_replace_column_with = null;
		$_replace_column_name = null;
		
		$_add_column = null;
		$_add_column_data = null;
		
		$_create_table = null;
		
		$_delete_column = null;
		
		$_data_for_insert = null;
		
		$_update_record = null;
		
		$_delete_where = null;
		
		$args = func_get_args();
		
		$__posun_arg = 0;
		
		$n = 0;
			$arg = str_replace("  ", " ", $args[0]);
			if(gettype($arg) == "string"){
				$dt = explode(" ", $arg);
				$i_pos = 0;
				if(strtolower($dt[0]) == "alter"){
					if(strtolower($dt[1]) == "table"){
						$from = $dt[2];
						$from = str_replace(":prefix:", dibi::$substitutes->prefix, $from);
						if(strtolower($dt[3]) == "drop"){
							$column = $dt[4];
							$_delete_column = $column;
							$__return_as_schema = true;
						}
						else if(strtolower($dt[3]) == "add"){
							$column = $dt[4];
							$_add_column = $column;
							$size = null;
							$_ii = 5;
							$p = explode("(", $dt[$_ii]);
							$type = $p[0];
							$isAutoIncrement = false;
							$isPrimary = false;
							if(count($p)>1){
								$xx = explode(")", $p[1]);
								$size = $xx[0];
							}
							for($i = $_ii++; $i < count($dt); $i++){
								if(strtolower($dt[$i]) == "auto_increment") $isAutoIncrement = true;
								if(strtolower($dt[$i]) == "primary") $isPrimary = true;
							}
							$_add_column_data = array("type" => $type, "size" => $size);
							if($isAutoIncrement) $_add_column_data["special"] = "AUTO_INCREMENT"; else $_add_column_data["special"] = null;
							if($isPrimary) $_add_column_data["others"] = "PRIMARY"; else $_add_column_data["others"] = null;
							$__return_as_schema = true;
						}
						else if(strtolower($dt[3]) == "modify" or strtolower($dt[3]) == "rename"){
							if(strtolower($dt[4]) == "column"){
								$column = $dt[5];
								$_ii = 6;
								if(strtolower($dt[3]) == "rename"){
									$_replace_column_name = $dt[7];
									$_ii = 8;
								}									
								$_replace_column_id = $column;
								$size = null;
								$p = explode("(", $dt[$_ii]);
								$type = $p[0];
								$isAutoIncrement = false;
								$isPrimary = false;
								if(count($p)>1){
									$xx = explode(")", $p[1]);
									$size = $xx[0];
								}
								for($i = $_ii++; $i < count($dt); $i++){
									if(strtolower($dt[$i]) == "auto_increment") $isAutoIncrement = true;
									if(strtolower($dt[$i]) == "primary") $isPrimary = true;
								}
								$_replace_column_with = array("type" => $type, "size" => $size);
								if($isAutoIncrement) $_replace_column_with["special"] = "AUTO_INCREMENT"; else $_replace_column_with["special"] = null;
								if($isPrimary) $_replace_column_with["others"] = "PRIMARY"; else $_replace_column_with["others"] = null;
								$__return_as_schema = true;
							}
						}
					}
				}
				else if(strtolower($dt[0]) == "describe"){
					$from = $dt[1];
					$from = str_replace(":prefix:", dibi::$substitutes->prefix, $from);
					$__return_as_schema = true;
					$i_pos = 2;
				}
				else if(strtolower($dt[0]) == "create"){
					if(strtolower($dt[1]) == "table"){
						$from = str_replace(":prefix:", dibi::$substitutes->prefix, $dt[2]);
						$_create_table = trim($from);
					}
				}
				else if(strtolower($dt[0]) == "show"){
					if(strtolower($dt[1]) == "tables"){
						$__result = array();
						$f = scandir(dibi::$folder);
						$i = 0;
						foreach($f as $n => $file){
							if($file == "." or $file == "..") continue;
							$__result[$i++] = array("name" => str_replace(".txt", "", $file), "location" => dibi::$folder.$file);
						}
						$tibi_result->setData($__result);
						$tibi_result->totalTime = microtime() - $time_start;
						return $tibi_result;
					}
				}
				else if(strtolower($dt[0]) == "select"){
					$i_pos = 2;
					if($dt[1] == "*")
						$select = array("*");
					if(strtolower($dt[2]) == "from"){
						$from = $dt[3];
						$from = str_replace(":prefix:", dibi::$substitutes->prefix, $from);
						$i_pos = 4;				
					}					
				}
				else if(strtolower($dt[0]) == "insert"){
					if(strtolower($dt[1]) == "into"){
						$from = $dt[2];
						$from = str_replace(":prefix:", dibi::$substitutes->prefix, $from);
						
						if(!isset($dt[3])){
							$_data_for_insert = $args[1];
						}
						
						$__return_as_schema = true;
					}
				}
				else if(strtolower($dt[0]) == "update"){
					if(strtolower($dt[2]) == "set"){
						$from = $dt[1];
						$from = str_replace(":prefix:", dibi::$substitutes->prefix, $from);
						
						if(func_num_args() > 1){
							$__posun_arg = 0;
							$fsql = $arg;
							$_update_record = $args[1];
							$i_pos = 0;
							if(func_num_args() > 2){
								$dt = explode(" ", $args[2]);
								$__posun_arg = 1;
							}else{
								$dt = array();
							}
						}
					}
				}
				else if(strtolower($dt[0]) == "delete"){
					if(strtolower($dt[1]) == "from"){
						$from = $dt[2];
						$from = str_replace(":prefix:", dibi::$substitutes->prefix, $from);
						
						$_delete_where = true;
						$i_pos = 3;
					}
				}
				
				if(isset($dt[$i_pos]) and strtolower($dt[$i_pos]) == "where"){
					$type_act = 5;
					$str = "";
					if($_update_record == null){
						for($i=0;$i<$i_pos + 1;$i++){ $str.=$dt[$i]." "; }
					}else
						$str = $fsql;
					$sql = str_replace(":prefix:", dibi::$substitutes->prefix, $str);
					
					if($_update_record != null){
						//echo $dt[$i_pos];
						$__posun_arg+=1;
						$end = str_replace($dt[$i_pos]." ", "", $args[$__posun_arg]);
					}else
						$end = str_replace($str, "", $arg);	
						
					if(func_num_args() != 1){
						for($i=1 + $__posun_arg;$i<func_num_args();$i++){
							//echo $end.", ".$args[$i]."(".substr($end, strlen($end)-2,1).")<br>";
							
							$end = trim($end);
							if(substr($end, strlen($end)-2,1) == "%"){
								$end = substr($end, 0, strlen($end)-2);
								$end.=" \"".$args[$i]."\"";
							}else
								$end.=" ".$args[$i];
						}
					}
					$sql.= " ".$end;
					$tibi_result->sql = $sql;					
					
					$_where = dibi::whereCompile($end);
					//var_dump($_where);
				}else{
					$sql = str_replace(":prefix:", dibi::$substitutes->prefix,$data);
					$tibi_result->sql = $sql;
				}
			}
		
		$where_condition = array();
		//echo "<hr>";
		//echo $data."<br>";
		//var_dump($_where);
		//echo "<hr>";
		if(gettype($_where) == "array"){
			foreach($_where as $n => $w){
				$where_condition[$w->left] = $w;
				//echo $n." => ".$w->left." ".$w->how." ".$w->right."<br>";
			}
		}
		
		$file = dibi::$folder.$from.".txt";
		
		if($_create_table != null){
			$myfile = fopen(dibi::$folder.$_create_table.".txt", "w");
			$data_from_table = Config::ssave(array("__table_data" => array("auto_increment" => 1), "scheme" => array("id" => array("type" => "int", "size" => "11", "special" => "AUTO_INCREMENT", "others" => "PRIMARY"))));
			fwrite($myfile, $data_from_table);
			fclose($myfile);
			$tibi_result->setData(array("text" => "table created"));
			$tibi_result->totalTime = microtime() - $time_start;
			dibi::$totalTime += microtime() - $time_start;
			return $tibi_result;
		}
		
		if(!file_exists($file)){
			dibi::$totalTime += microtime() - $time_start;
			throw new Exception('Database "'.$from."\" not exists!");
		}
		
		if(filesize($file) == 0){
			$tibi_result->setData(array());
			$tibi_result->totalTime = microtime() - $time_start;
			dibi::$totalTime += microtime() - $time_start;
			return $tibi_result;
		}
		$ddsoubor = fopen($file, "r");
		//$text = fread($ddsoubor, filesize($file));
		$text = stream_get_contents($ddsoubor);
		fclose($ddsoubor);
				
		if($text == "") echo "EMPTY";
		
		$data_from_table = Config::sload($text);
		if(!isset($data_from_table["__table_data"])) $data_from_table["__table_data"] = array("auto_increment" => 1);
		$table_scheme = null;
		$table_id_column = null;
		$table_data_for = array();				
		
		$_last_result_where = null;
		$_is_auto_increment = false;
		
		
		foreach($data_from_table as $n => $tdata){
			if($n == "scheme"){
				$table_scheme = $tdata;
				$__add_me_pls = array();
				foreach($tdata as $e => $tg){
					if(isset($tg["others"]) and $tg["others"] == "PRIMARY"){						
						$tg["name"] = $e;
						$table_id_column = $e;						
					}	
					if(isset($tg["special"]) and $tg["special"] == "AUTO_INCREMENT"){						
						$_is_auto_increment = true;					
					}	
					if(gettype($tdata[$e]) != "array")
						$tdata[$e] = array();
					if(!isset($tdata[$e]["size"])) 
						$tdata[$e]["size"] = null;
					if(!isset($tdata[$e]["special"])) 
						$tdata[$e]["special"] = null;
					if(!isset($tdata[$e]["others"])) 
						$tdata[$e]["others"] = null;					
				}
				if($__return_as_schema){
					if(count($where_condition) == 0 and $_replace_column_id == null and $_add_column == null and $_delete_column == null and $_data_for_insert ==null and $_update_record == null){$__add_me_pls = $tdata;}else{
						foreach($tdata as $e => $tg){
							if($e == $_replace_column_id){
								$data_from_table[$n][$e] = $_replace_column_with;
								if($_replace_column_name == $e) $_replace_column_name = null;
								if($_replace_column_name != null){
									$data_from_table[$n][$_replace_column_name] = $data_from_table[$n][$e];
									unset($data_from_table[$n][$e]);
									foreach($data_from_table as $n => $tdata){
										if($n == "scheme"){continue;}
										foreach($tdata as $e => $tg){
											if($e == $_replace_column_id){
												$data_from_table[$n][$_replace_column_name] = $data_from_table[$n][$e];
												unset($data_from_table[$n][$e]);
											}
										}
									}
								}
								$arr = Config::ssave($data_from_table);															
								
								$_soubor = fopen($file, "w");
								fwrite($_soubor, $arr);
								fclose($_soubor);
								
								$_replace_column_with = array(0 => $_replace_column_with);
								if($_replace_column_name != null)
									$_replace_column_with[0]["name"] = $_replace_column_name;
								else
									$_replace_column_with[0]["name"] = $e;
								
								$tibi_result->setData($_replace_column_with);
								$tibi_result->totalTime = microtime() - $time_start;
								dibi::$totalTime += microtime() - $time_start;
								return $tibi_result;
							}							
							$addtol =true;
							if(isset($where_condition["name"])){
								if(!$where_condition["name"]->match($e))
									$addtol = false;
							} 
							
							if($_data_for_insert != null){
								$_id = $_data_for_insert[$table_id_column];
								unset($_data_for_insert[$table_id_column]);
								if($_id == "" and $_is_auto_increment){
									$_id = $data_from_table["__table_data"]["auto_increment"];
									$data_from_table["__table_data"]["auto_increment"] += 1;
								}
								//$data_from_table[$_id] = $_data_for_insert;
								foreach($tdata as $e => $tg){
									if(isset($tg["others"]) and $tg["others"] == "PRIMARY")
										continue;
									if(!isset($_data_for_insert[$e]))
										$data_from_table[$_id][$e] = "";
									else
										$data_from_table[$_id][$e] = $_data_for_insert[$e];
								}								
								$arr = Config::ssave($data_from_table);								
								$_soubor = fopen($file, "w");
								fwrite($_soubor, $arr);
								fclose($_soubor);
								
								$aq = array();
								$aq[$_id] = $_data_for_insert;
								$tibi_result->setData($aq);
								$tibi_result->totalTime = microtime() - $time_start;
								dibi::$totalTime += microtime() - $time_start;
								return true;
							}
							
							$tg["name"] = $e;
							if($addtol)
								$__add_me_pls[$e] = $tg;
						}
						if($_add_column != null){
							$data_from_table[$n][$_add_column] = $_add_column_data;
							foreach($data_from_table as $n => $tdata){
								if($n == "scheme"){continue;}
								$data_from_table[$n][$_add_column] = "";
							}
							$arr = Config::ssave($data_from_table);
							
							$_soubor = fopen($file, "w");
							fwrite($_soubor, $arr);
							fclose($_soubor);
							
							$tibi_result->setData($_add_column_data);
							$tibi_result->totalTime = microtime() - $time_start;
							dibi::$totalTime += microtime() - $time_start;
							return $tibi_result;	
						}
						if($_delete_column != null){
							unset($data_from_table[$n][$_delete_column]);
							foreach($data_from_table as $n => $tdata){
								if($n == "scheme"){continue;}
								unset($data_from_table[$n][$_delete_column]);
							}							
							$arr = Config::ssave($data_from_table);
							
							$_soubor = fopen($file, "w");
							fwrite($_soubor, $arr);
							fclose($_soubor);
							
							$tibi_result->setData(array("text" => "column deleted"));
							$tibi_result->totalTime = microtime() - $time_start;
							dibi::$totalTime += microtime() - $time_start;
							return $tibi_result;	
						}
					}														
					
					$tibi_result->setData($__add_me_pls);
					$tibi_result->totalTime = microtime() - $time_start;
					dibi::$totalTime += microtime() - $time_start;
					return $tibi_result;
				}
			}
			else if($n == "__table_data"){}
			else{
				$addMeSenpai = true;
				$_tdt = array();
				$l = true;
				$_last_result_where = null;
				$_data_for_compare = array();
				$_data_for_compare[$table_id_column] = $n;
				foreach($tdata as $e => $tg){
					$_data_for_compare[$e] = $tg;
					$_tdt[$e] = $tg;
				}
				//var_dump($_data_for_compare);
				//echo "<hr>";
				//var_dump($where_condition);
				foreach($where_condition as $f => $wcond){					
					$l = $wcond->match($_data_for_compare[$f], $_last_result_where);
					$_last_result_where = $l;
				}
				if($l){					
					$insered = array();
					$insered[$table_id_column] = $n;				
					$table_data_for[$n] = $insered + $_tdt;
					if($_update_record != null){
						foreach($_update_record as $p => $upd){
							$table_data_for[$n][$p] = $upd;
						}
					}
				}
			}
		}
		
		if($_update_record != null){
			//echo "<hr> <b>START:</b>";
			//var_dump($table_data_for);
			//echo "<hr>";
			foreach($table_data_for as $p => $upd){
				unset($table_data_for[$p][$table_id_column]);
			}
			
			//var_dump($data_from_table);
			//echo "<hr>";
			foreach($data_from_table as $p => $upd){
				if($p != "__table_data" and $p != "scheme"){
					if(isset($table_data_for[$p])){
						$data_from_table[$p] = $table_data_for[$p];
					}
				}
			}
			
			$arr = Config::ssave($data_from_table);		
			//echo "<hr><b>data(".filesize($file)."):</b>".$arr."<hr>";			
			$_soubor = fopen($file, "w");
			fwrite($_soubor, $arr);
			fclose($_soubor);
			
			$__soubor = fopen($file, "r");
			$text = fread($__soubor, filesize($file));
			fclose($__soubor);
			
			//echo "<br>END(".filesize($file).", ".$file.")<hr>";
			
			$tibi_result->setData($table_data_for);
			$tibi_result->totalTime = microtime() - $time_start;
			dibi::$totalTime += microtime() - $time_start;
			return $tibi_result;
		}else if($_delete_where){			
			foreach($data_from_table as $p => $upd){
				if($p != "__table_data" and $p != "scheme"){					
					if(isset($table_data_for[$p])){						
						unset($data_from_table[$p]);
					}
				}
			}
			
			$arr = Config::ssave($data_from_table);
			$_soubor = fopen($file, "w");
			fwrite($_soubor, $arr);
			fclose($_soubor);
			
			$tibi_result->setData($table_data_for);
			$tibi_result->totalTime = microtime() - $time_start;
			dibi::$totalTime += microtime() - $time_start;
			return $tibi_result;
		}
		$tibi_result->setData($table_data_for);
		$tibi_result->totalTime = microtime() - $time_start;
		dibi::$totalTime += microtime() - $time_start;
		return $tibi_result;
	}
}
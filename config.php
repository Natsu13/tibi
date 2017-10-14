<?php
class Config {
		private $variables = NULL;
		private $root;
		
		function __construct($root){
			$this->root = $root;
		}
		
		public function set_variable($name, $value, $protect = false, $group = "root"){
			$is_protect = false;
			if( $this->is_variable( $name ) ){
				if( $this->variables[$group][$name]["Protect"] ){ $is_protect=true; }
			}
			
			if($is_protect){
				$this->root->message[] = array( 
												"state"		=> $this->root->_MESSAGE_ERROR, 
												"message" 	=> "Triing to change PROTECTED variable! (" . $group . "::" .  $name . ")",
												"execution_time" => round(microtime(true) - $this->root->time_start, 4)
											);
				return false;
			}else{
				$this->variables[$group][$name] = array(
												"Value" => $value,
												"Protect" => $protect
											);
			}
			return true;
		}
		
		public function set($name, $value, $protect = false, $group = "root"){
			return $this->set_variable($name, $value, $protect, $group);
		}
		
		public function get($name, $group = "root"){
			return $this->get_variable($name, $group);
		}
		
		public function getD($name, $default = "", $group = "root"){
			$q = $this->get_variable($name, $group);
			if($q == NULL){
				return $default;
			}
			return $q;
		}
		
		public function get_variable($name, $group = "root"){
			if( $this->is_variable( $name ) ){
				return $this->variables[$group][$name]["Value"];
			}else return NULL;
		}
		
		public function is_variable($name, $group = "root"){
			if( isset( $this->variables[$group][$name] ) ){
				return true;
			}else return false;
		}
		
		public static function sload($text, $config = null, $hy_array = null){
			//Config Variable
			if($config==null){
				$config["level"] = 0;
				$config["level_name"] = null;
				$config["print"] = false;
			}

			$lines = explode("\n",$text);
			
			for($p=0;$p<count($lines);$p++){
				$line = $lines[$p];
				if(substr($line,0,2) == "//"){
					//Komentář
				}else{
					$otevreno=0;
					$var_name = "";
					$var_text = false;
					$text_buffer = "";
					$text_mam = false;
					for($i=0;$i<strlen($line);$i++){
						$z = $line[$i];
						if(($z=="\t" or $z==" ") and ($otevreno==0)){  }
						elseif($otevreno!=0 and $z=="\\"){
							$i++;
							$text_buffer.=$line[$i];
						}
						elseif($z=="\""){
							if($otevreno!=0){
								if($otevreno==1){ $var_name = $text_buffer; }
								else{ $var_text = $text_buffer; }
								$otevreno=0; $text_buffer="";
							}
							else if($var_name==""){ $otevreno=1; }
							else{ $otevreno=2;$text_mam=true; }
						}
						else{
							$text_buffer.=$z;
						}
					}
					if($var_text=="" and $text_mam==false and strlen($line)!=0){			
						$config["level"] +=1;
						$config["level_name"] = $var_name;
						
						$p++;
						$opened = true;
						$text_buffer_arg = "";
						$opened_buff = 1;
						if(trim(@$lines[$p]) == "{"){					
							for($o=$p+1;$o<count($lines);$o++){
								if(trim($lines[$o]) == "{"){
									$opened_buff++;
									$text_buffer_arg.=$lines[$o]."\n";
								}
								else if(trim($lines[$o]) == "}"){
									$opened_buff--;
									if($opened_buff==0){
										$opened = false;
										$o = count($lines);
									}else{
										$text_buffer_arg.=$lines[$o]."\n";
									}
								}else{
									$text_buffer_arg.=$lines[$o]."\n";
								}
								$p++;
							}
							if($opened){
								$last_dtf_error = "Výstup pole nebyl řádně ukončen!";
								return null;
							}else{
								$text_buffer_arg = substr($text_buffer_arg, 0, strlen( $text_buffer_arg ) - 2);
								$hy_array[$var_name] = Config::sload( $text_buffer_arg, $config );
							}	
						}else{
							@$hy_array[$var_name] = Config::sload( $lines[$p], $config );					
						}
					}else{
						if($var_name!=""){
							$var_text = str_replace("///n", "\n", $var_text);
							$var_text = html_entity_decode($var_text);
							$hy_array[$var_name] = $var_text;
						}
					}
				}
			}
			return $hy_array;
		}
		
		public function load($text, $config = null, $hy_array = null){
			//Config Variable
			if($config==null){
				$config["level"] = 0;
				$config["level_name"] = null;
				$config["print"] = false;
			}

			$lines = explode("\n",$text);
			
			for($p=0;$p<count($lines);$p++){
				$line = $lines[$p];
				if(substr($line,0,2) == "//"){
					//Komentář
				}else{
					$otevreno=0;
					$var_name = "";
					$var_text = null;
					$text_buffer = "";
					for($i=0;$i<strlen($line);$i++){
						$z = $line[$i];
						if(($z=="\t" or $z==" ") and ($otevreno==0)){  }
						elseif($otevreno!=0 and $z=="\\"){
							$i++;
							$text_buffer.=$line[$i];
						}
						elseif($z=="\""){
							if($otevreno!=0){ 
								if($otevreno==1){ $var_name = $text_buffer; }
								else{ $var_text = $text_buffer; }
								$otevreno=0; $text_buffer="";
							}
							else if($var_name==""){ $otevreno=1; }
							else{ $otevreno=2; $var_text = ""; }
						}
						else{
							$text_buffer.=$z;
						}
					}
					if($var_text==null and strlen($line)!=0){			
						$config["level"] +=1;
						$config["level_name"] = $var_name;
						
						$p++;
						$opened = true;
						$text_buffer_arg = "";
						$opened_buff = 1;
						if(trim(@$lines[$p]) == "{"){					
							for($o=$p+1;$o<count($lines);$o++){
								if(trim($lines[$o]) == "{"){
									$opened_buff++;
									$text_buffer_arg.=$lines[$o]."\n";
								}
								else if(trim($lines[$o]) == "}"){
									$opened_buff--;
									if($opened_buff==0){
										$opened = false;
										$o = count($lines);
									}else{
										$text_buffer_arg.=$lines[$o]."\n";
									}
								}else{
									$text_buffer_arg.=$lines[$o]."\n";
								}
								$p++;
							}
							if($opened){
								$last_dtf_error = "Výstup pole nebyl řádně ukončen!";
								return null;
							}else{
								$text_buffer_arg = substr($text_buffer_arg, 0, strlen( $text_buffer_arg ) - 2);
								$hy_array[$var_name] = $this->load( $text_buffer_arg, $config );
							}	
						}else{
							@$hy_array[$var_name] = $this->load( $lines[$p], $config );					
						}
					}else{
						if($var_name!="")
							$hy_array[$var_name] = $var_text;
					}
				}
			}
			return $hy_array;
		}

		public static function ssave($array, $level = 0){
			$return = "";
			foreach($array as $key => $value){
				if(is_array($value)){ 
						for($i=0;$i<$level;$i++){ $return.="\t"; }
					$return.="\"".$key."\"\r\n";
						for($i=0;$i<$level;$i++){ $return.="\t"; }
					$return.="{\r\n";
					$return.=Config::ssave($value, $level+1);
						for($i=0;$i<$level;$i++){ $return.="\t"; }
					$return.="}\r\n";
				}else{
					for($i=0;$i<$level;$i++){ $return.="\t"; }
					$value = htmlentities($value, ENT_QUOTES);
					$value = str_replace("\n", "///n", $value);
					$return.="\"".$key."\" \"".$value."\"\r\n";
				}
			}
			return $return;
		}
		
		public function save($file, $array, $level = 0){
			$return = "";
			foreach($array as $key => $value){
				if(is_array($value)){ 
						for($i=0;$i<$level;$i++){ $return.="\t"; }
					$return.="\"".$key."\"\r\n";
						for($i=0;$i<$level;$i++){ $return.="\t"; }
					$return.="{\r\n";
					$return.=$this->save($file, $value, $level+1);
						for($i=0;$i<$level;$i++){ $return.="\t"; }
					$return.="}\r\n";
				}else{
						for($i=0;$i<$level;$i++){ $return.="\t"; }
					$return.="\"".$key."\" \"".$value."\"\r\n";
				}
			}
			
			if($level==0){
				$soubor = fopen($file, "w");
				fwrite($soubor, $return);
				fclose($soubor);
			}
			return $return;
		}

		public function open($file){
			if(file_exists($file)){
				$soubor = fopen($file, "r");
				$text = fread($soubor, filesize($file));
				fclose($soubor);	
				return $this->load($text);
			}else{
				return false;
			}			
		}
	
		public function load_variables($group = "root"){
			$this->variables[$group] = $this->open(_ROOT_DIR . "/config/" . $group . ".config");
			if( $this->variables[$group] == false ){ 
				return false; 
			}else{ 
				return true; 
			}
		}
		
		public function save_variables($group = "root"){
			$this->save(_ROOT_DIR . "/config/" . $group . ".config", $this->variables[$group]);
		}
		
		private function get_group($group = "root"){
			return $this->variables[$group];
		}
		
		public function test(){
			if($this->load_variables()){
				foreach($this->get_group() as $k => $v){
					echo $k." : ".$v["Value"]."<br>";
				}
			}
		}
}
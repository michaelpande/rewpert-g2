<?php

	// Simple Cache Storage
	// 		A simple database storage, for storing parameters quickly and easy.
	// 
	
	
	
	// FIX PREPARED - UPDATES
	// MORE TESTING
	// FIX FILENAME (Constructor)
	// LINUX SAFE URL?
	
	
	
	//SimpleStorageTest::test();
	//AdvancedStorageTest::test();
	//PrepareStorageTest::test();
	
	class SimpleStorage{
		
		private $db;
		private $prepare = false; 
		private $preparedInsert = null;
		private $insertCount = 0;
		private $database_name;
		
		function __construct(){
			global $database_name;
			
			$database_name = "SimpleStorage.db";
			
		}
		
		
		public function database_name($name){
			global $database_name;
			if(isset($name) && is_string($name)){
				echo $name;
				$database_name = $name;
			}
		}
		
		

		
		
		private function db_location(){
			global $database_name;
			

			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				
				return dirname(__FILE__) . '\\' .$database_name; // Windows
			} else {
				return dirname(__FILE__) . '/' .$database_name; // Linux
			}
			
			
		}
		
		
		// Method to decide if all insert / update statements should be combined into a few large. Call on Execute after entire statement is prepared.
		public function prepare($bool){
			global $prepare;
			if(is_bool($bool)){
				if(!$bool){
					$preparedInsert = null;
				}
				$prepare = $bool;
			}
		}
		
		// Method to combine multiple insert queries into one. SQLite has a limitation on number of values to insert in one query, so this method will
		// split the queries to a few every 200 value. 
		private function prepareInsert($str){
			global $preparedInsert, $insertCount;
			
			if($preparedInsert != null){
				if($insertCount >= 200){
					$preparedInsert = substr_replace($preparedInsert,";",strlen($preparedInsert)-1);
					$preparedInsert .= "INSERT INTO SimpleStorage VALUES ";
					$insertCount = 0;
				}
				$preparedInsert .= "$str ,";
				$insertCount++;
			}else{
				$preparedInsert = "INSERT INTO SimpleStorage VALUES ";
				$preparedInsert .= "$str ,";
				$insertCount++;
			}
		}
		
		// Call on this to execute the prepared statement
		public function execute(){
			global $prepare, $preparedInsert;
			if($prepare != null && $preparedInsert != null){
			
				try{
					$db = $this->createDB();
					echo ("<br>execute() - EXECUTE<br>");
					$preparedInsert = substr_replace($preparedInsert,";",strlen($preparedInsert)-1);


					$result = $db->exec($preparedInsert);
					$preparedInsert = null;
					return $result;
				}catch(Exception $e){
					
					return false;
				}
			}else{
				return false;
			}
		}
		
		
		// Create Database
		private function createDB(){
			global $db;
			if(isset($db) && $db != null){	
				return $db;
			}
			
			try{
				$db = new PDO('sqlite:'.$this->db_location());
				$db->exec("CREATE TABLE SimpleStorage (key1 TEXT NOT NULL, key2 TEXT NOT NULL, val TEXT NOT NULL, dt DATETIME, PRIMARY KEY(key1,key2))");  
				return $db;
			}
			catch(PDOException $e){
				print 'Exception : '.$e->getMessage();
				return null;
			}
			
		}
		
		
		
		
		
		// Get value matching id, returns string or null
		public function get($key1, $key2){

			try{
				$db = $this->createDB();
				$result = $db->query("SELECT * FROM SimpleStorage WHERE key1 LIKE '$key1' AND key2 LIKE '$key2'");
				if($result == null){
					echo "<br>$key1, $key2 returns null";
					return null;
				}
				foreach($result as $row)
				{
					return $this->output($row['val']); // Returns first row
				}
				
			}catch(Exception $e){
				print 'Exception : '.$e->getMessage();
				return null;
			}
			return null;
		}
		
		
		
		
		// Update or set value with matching id, returns true/false
		public function update($key1, $key2, $value){
			
			if($this->get($key1,$key2) == null){
				return $this->set($key1,$key2,$value);
			}
			
			try{
				$db = $this->createDB();
				$value = $this->input($value);
				$result = $db->exec("UPDATE SimpleStorage SET val = '$value' WHERE key1 LIKE '$key1' AND key2 LIKE '$key2' ");
				if($result){
					return true;
				}
				
				
			}catch(Exception $e){
				print 'Exception : '.$e->getMessage();
				return false;
			}
			return false;
		}
		

		// Stores value if id don't exist already. Returns true if value was stored, false if not. 
		public function set($key1, $key2, $value){
			global $prepare;
			try{
				$db = $this->createDB();
				$value = $this->input($value);
				
				if($prepare){
					$this->prepareInsert("('$key1','$key2','$value','')");
					return true;
				}
				
				$result = $db->exec("INSERT INTO SimpleStorage VALUES ('$key1','$key2','$value','')");
				if($result > 0){
					return true;
				}
			}catch(Exception $e){
				print 'Exception : '.$e->getMessage();
				return false;
			}
			return false;
		}
		
		
		public function remove($key1, $key2){
	
			
			try{
				$db = $this->createDB();
				$key1 = $this->input($key1);
				$key2 = $this->input($key2);
				$result = $db->exec("DELETE from SimpleStorage WHERE key1 LIKE '$key1'");
				if($result > 0){
					return true;
				}
			}catch(Exception $e){
				print 'Exception : '.$e->getMessage();
				return false;
			}
			return false;
		}
		
		
		
		
		
		private function input($str){
			$str = str_replace("'","''",$str); // Replace the one occurence when the test fails due to ', SQLite supports '' for the character '.
			$str = htmlspecialchars($str);
			return $str;
			
		}
		
		
		private function output($str){
			return htmlspecialchars_decode($str);
		}
		
		// Returns number of rows
		public function length(){
			
			try{
				$db = $this->createDB();
				$result = $db->query("SELECT COUNT(*) as count FROM SimpleStorage");
				return $result->fetch(PDO::FETCH_NUM)[0];
			}catch(Exception $e){
				print 'Exception : '.$e->getMessage();
				return 0;
			}
			return 0;
		}
		
	}
	

	
	class SimpleStorageTest{
		
		public static function test(){
			
			$db = new SimpleStorage();
		
			// Arrange
			$idArray = array(
				'Basic', 
				'White Space',
				'Special characters',
				'More Special Characters',
				'English',
				'Chinese',
				'Norwegian',
				'QCodes'
			);
			$valueArray = array(
				'Simple',
				'Nothing will work',
				'*\'',
				'?\%,#¤%"',
				'This is a test',
				'这是一个测试',
				'Dette er en test æøå',
				'subj:06005000'
			);
			$updateArray = array(
				"Updated",
				'More whitespace',
				'\'<>',
				'@**',
				'Refresh this',
				'这刷新',
				'Oppdater dette åæø',
				'subj:06005001'
			);
			
			// Test set, get, update, delete
			for($i = 0; $i < count($idArray); $i++){
				
				
				// Act
				$id = $idArray[$i];
				$value1 = $valueArray[$i];
				$updatedValue1 = $updateArray[$i];
				
				// Act
				$db->set($id,"", $value1);  // SET
				$answer1 = $db->get($id,""); // GET
				$db->update($id,"", $updatedValue1); // UPDATE
				$updatedAnswer1 = $db->get($id,""); // GET
				$db->remove($id,""); // DELETE
				$deleted1 = $db->get($id,""); // GET
				
				
				// Assert
				if($answer1 != $value1){
					echo "<strong>Test</strong> $idArray[$i] - ($i): Set/Get failed:<br>  \"$answer1\" != \"$value1\"<br><br>";
				}
				if($updatedAnswer1 != $updatedValue1){
					echo "<strong>Test</strong> $idArray[$i] - ($i): Update/Get Failed:<br>  \"$updatedAnswer1\" != \"$updatedValue1\"<br><br>";
				}
				if($deleted1 != null){
					echo "<strong>Test</strong> $idArray[$i] - ($i): Delete Failed<br>";
				}
			
			}
			
			// Test length
			
			$db->set("CountThis0","Nothing","Nothing");
			$db->set("CountThis1","Nothing","Nothing");
			
			if($db->length() != 2){
					echo "<strong>Test</strong><br> Length should be 2, but is ". $db->length()."<br>";
			}
			
			$db->remove("CountThis0","Nothing");
			$db->remove("CountThis1","Nothing");

			if($db->length() != 0){
					echo "<strong>Test</strong><br> Length should be 0, but is ". $db->length()."<br>";
			}
			
			
			print("<h2>Test completed.</h2>");
			
			
		}
		
	}
	

	class AdvancedStorageTest{
		
		public static function test(){
			
		
			// Arrange
			$idArray = array(
				'Basic', 
				'White Space',
				'Special characters',
				'More Special Characters',
				'English',
				'Chinese',
				'Norwegian',
				'QCodes'
			);
			// Arrange
			$id2Array = array(
				'Basic', 
				'White Space',
				'Special characters',
				'More Special Characters',
				'English',
				'Chinese',
				'Norwegian',
				'QCodes'
			);
			$valueArray = array(
				'Simple',
				'Nothing will work',
				'*\'',
				'?\%,#¤%"',
				'This is a test',
				'这是一个测试',
				'Dette er en test æøå',
				'subj:06005000'
			);
			$updateArray = array(
				"Updated",
				'More whitespace',
				'\'<>',
				'@**',
				'Refresh this',
				'这刷新',
				'Oppdater dette åæø',
				'subj:06005001'
			);
			
			// Test set, get, update, delete
			for($i = 0; $i < count($idArray); $i++){
				
				
				// Act
				$id = $idArray[$i];
				$id2 = $id2Array[$i];
				$value1 = $valueArray[$i];
				$updatedValue1 = $updateArray[$i];
				
				// Act
				SimpleStorage::set($id, $id2, $value1);  // SET
				$answer1 = SimpleStorage::get($id,$id2); // GET
				SimpleStorage::update($id,$id2, $updatedValue1); // UPDATE
				$updatedAnswer1 = SimpleStorage::get($id, $id2); // GET
				SimpleStorage::remove($id, $id2); // DELETE
				$deleted1 = SimpleStorage::get($id, $id2); // GET
				
				
				// Assert
				if($answer1 != $value1){
					echo "<strong>Test</strong> $idArray[$i] - ($i): Set/Get failed:<br>  \"$answer1\" != \"$value1\"<br><br>";
				}
				if($updatedAnswer1 != $updatedValue1){
					echo "<strong>Test</strong> $idArray[$i] - ($i): Update/Get Failed:<br>  \"$updatedAnswer1\" != \"$updatedValue1\"<br><br>";
				}
				if($deleted1 != null){
					echo "<strong>Test</strong> $idArray[$i] - ($i): Delete Failed<br>";
				}
			
			}
			
			// Test length
			
			SimpleStorage::set("CountThis0","CountThis0","Nothing");
			SimpleStorage::set("CountThis1","CountThis0","Nothing");
			
			if(SimpleStorage::length() != 2){
					echo "<strong>Test</strong><br> Length should be 2, but is ". SimpleStorage::length()."<br>";
			}
			
			SimpleStorage::remove("CountThis0","CountThis0");
			SimpleStorage::remove("CountThis1","CountThis0");

			if(SimpleStorage::length() != 0){
					echo "<strong>Test</strong><br> Length should be 0, but is ". SimpleStorage::length()."<br>";
			}
			
			
			print("<h2>Test completed.</h2>");
			
			
		}
		
	}

?>
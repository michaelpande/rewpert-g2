<?php



	//Testing method for development & debugging.
	//SimpleStorageTest::test(); 
	
	
	/**
	* Simple Cache Storage (SQLite based)
	* 	A simple database storage, for storing parameters quickly and easy.
	* 	By using the prepare(true) statement and executing afterwards, 
	*	you should make sure you only do one group of queries at the time. As the stacked queries are executed this order Insert, Update, Delete.
	* 
	* SQLite functionality is enabled by default in PHP 5.0 and up. 
	* @author Michael Pande
	*/
	class SimpleStorage{
		
		private $db;
		private $prepare = false; 
		private $preparedInsert = null;
		private $preparedUpdate = null;
		private $preparedRemove = null;
		private $insertCount = 0;
		private $updateCount = 0;
		private $removeCount = 0;
		private $database_name;
		
		function __construct($name = null){
			global $database_name;
			
			$database_name = is_string($name) ? $name : "SimpleStorage.db";
			if(is_string($name)){
				$database_name = $name;
				return;
			} 
			
		}

		
		

		 /**
		 * Returns the path to the database 
		 *
		 * @author Michael Pande
		 */
		private function db_location(){
			global $database_name;
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				
				return dirname(__FILE__) . '\\' .$database_name; // Windows
			} else {
				return dirname(__FILE__) . '/' .$database_name; // Linux
			}
			
			
		}
		
		 /**
		 * Method to decide if all insert / update statements should be combined into a few large. Call on Execute after entire statement is prepared.
		 *
		 * @param boolean $bool - true / false
		 *
		 * @author Michael Pande
		 */
		public function prepare($bool){
			global $prepare, $preparedInsert, $preparedUpdate, $preparedRemove, $insertCount, $updateCount, $removeCount;
			if(is_bool($bool)){
				if(!$bool){
					$preparedInsert = null;
					$preparedUpdate = null;
					$preparedRemove = null;
					$insertCount = 0;
					$updateCount = 0;
					$removeCount = 0;
				}
				$prepare = $bool;
			}
		}
		
		
		/**
		 * Method to combine multiple insert queries into one. SQLite has a limitation on number of values to insert in one query, so this method will
		 * split the queries to a few every 200 value.
		 *
		 * @param string to add to the prepared insert query.
		 *
		 * @author Michael Pande
		 */
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
		
		
		/**
		 * Method to combine multiple update queries into one. SQLite has a limitation on number of values to update in one query, so this method will
		 * split the queries to a few every 200 value.
		 *
		 * @param string to add to the prepared update query.
		 *
		 * @author Michael Pande
		 */
		private function prepareUpdate($str){
			global $preparedUpdate, $updateCount;
			
			if($preparedUpdate != null){
				if($updateCount >= 200){
					$preparedUpdate = substr_replace($preparedUpdate,";",strlen($preparedUpdate)-1);
					$preparedUpdate .= "INSERT OR REPLACE INTO SimpleStorage VALUES ";
					$updateCount = 0;
				}
				$preparedUpdate .= "$str ,";
				$updateCount++;
			}else{
				$preparedUpdate = "INSERT OR REPLACE INTO SimpleStorage VALUES ";
				$preparedUpdate .= "$str ,";
				$updateCount++;
			}
			
		}
		
		/**
		 * Method to combine multiple delete queries into one. SQLite has a limitation on number of values to delete in one query, so this method will
		 * split the queries to a few every 200 value.
		 *
		 * @param string to add to the prepared delete query.
		 *
		 * @author Michael Pande
		 */
		private function prepareDelete($str){
			global $preparedDelete, $DeleteCount;
			
			if($preparedDelete != null){
				if($DeleteCount >= 20){
					$preparedDelete = substr_replace($preparedDelete,";",strlen($preparedDelete)-3);
					$preparedDelete .= "DELETE from SimpleStorage WHERE  "; // DELETE from SimpleStorage WHERE key1 LIKE '$key1'
					$DeleteCount = 0;
				}
				$preparedDelete .= "($str) OR ";
				$DeleteCount++;
			}else{
				$preparedDelete = "DELETE FROM SimpleStorage WHERE ";
				$preparedDelete .= "($str) OR ";
				$DeleteCount++;
			}
		}
		
		
		
		
		/**
		 * Method to call after the prepared queries has been made.
		 *
		 * @author Michael Pande
		 */
		public function execute(){
			global $prepare, $preparedInsert, $preparedDelete, $preparedUpdate;
			if($prepare != null && ($preparedInsert != null || $preparedUpdate != null ||  $preparedDelete != null)){
			
				try{
					$db = $this->createDB();
					echo ("<br>execute() - EXECUTE<br>");
					
					if($preparedInsert != null){
						$preparedInsert = substr_replace($preparedInsert,";",strlen($preparedInsert)-1);
						$result = $db->exec($preparedInsert);
					}
					if($preparedUpdate != null){
						$preparedUpdate = substr_replace($preparedUpdate,";",strlen($preparedUpdate)-1);
						$result = $db->exec($preparedUpdate);
					}
					if($preparedDelete != null){
						$preparedDelete = substr_replace($preparedDelete,";",strlen($preparedDelete)-3);
						$result = $db->exec($preparedDelete);
						
					}
					
					
					
					$preparedInsert = null;
					$preparedUpdate = null;
					$preparedDelete = null;
					
					return $result;
				}catch(Exception $e){
					
					return false;
				}
			}else{
				return false;
			}
		}
		
		
		/**
		 * Creates the database and required tables at the location set (or default) in the creation of the SimpleStorage object (object of this class).
		 *
		 * @author Michael Pande
		 */
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
		
		
		
		/**
		 * Get first matching value from database based on the key(s)
		 * 
		 * @param $key1 - First key (Can be empty)
		 * @param $key2 - Second key (Can be empty)
		 * @return string or null
		 *
		 * @author Michael Pande
		 */
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
		
		
		
		
		/**
		 * Update (or set if it does not exist) value from database based on the key(s) and the new value
		 * 
		 * @param $key1 - First key (Can be empty)
		 * @param $key2 - Second key (Can be empty)
		 * @param $value - The new string value (Supports serialization)
		 * @return true/false - If it was successful or not
		 *
		 * @author Michael Pande
		 */
		public function update($key1, $key2, $value){
			global $prepare;
			if($this->get($key1,$key2) == null){
				return $this->set($key1,$key2,$value);
			}
			
			try{
				$db = $this->createDB();
				$value = $this->input($value);
				
				if($prepare){
					$this->prepareUpdate("('$key1','$key2','$value','')");
					return true;
				}
				
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
		

		/**
		 * Stores value if id don't exist already
		 * 
		 * @param $key1 - First key (Can be empty)
		 * @param $key2 - Second key (Can be empty)
		 * @param $value - The string value (Supports serialization)
		 * @return true if value was stored, false if not. 
		 *
		 * @author Michael Pande
		 */
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
		
		
		/**
		 * Removes entry matching the key values
		 * 
		 * @param $key1 - First key (Can be empty)
		 * @param $key2 - Second key (Can be empty)
		 * @return true if value was deleted, false if not. 
		 *
		 * @author Michael Pande
		 */
		public function remove($key1, $key2){
			global $prepare;
			
			try{
				$db = $this->createDB();
				$key1 = $this->input($key1);
				$key2 = $this->input($key2);
				
				if($prepare){
					$this->prepareDelete("key1 LIKE '$key1' AND key2 LIKE '$key2' ");
					return true;
				}
				
				$result = $db->exec("DELETE from SimpleStorage WHERE key1 LIKE '$key1' AND key2 LIKE '$key2'");
				if($result > 0){
					return true;
				}
			}catch(Exception $e){
				print 'Exception : '.$e->getMessage();
				return false;
			}
			return false;
		}
		
		/**
		 * Returns input safe for database queries.
		 * 
		 * @param $str - The string to be used in query 
		 * @return $str - Database safe string
		 *
		 * @author Michael Pande
		 */
		private function input($str){
			$str = str_replace("'","''",$str); // Replace the one occurence when the test fails due to ', SQLite supports '' for the character '.
			$str = htmlspecialchars($str);
			return $str;
			
		}
		
		/**
		 * Returns output from database to restore after using htmlspecialchars.
		 * 
		 * @param $str - The string returned from the database
		 * @return $str - Restored string
		 *
		 * @author Michael Pande
		 */
		private function output($str){
			return htmlspecialchars_decode($str);
		}
		
		/**
		 * Number of entries in the storage 
		 * 
		 * @return numeric - number of rows
		 *
		 * @author Michael Pande
		 */
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
	

	/**
	* Simple Storage Test
	* 	This class is used for developing, testing and debugging the Simple Storage class (in the same file)
	*
	* @author Michael Pande
	*/
	class SimpleStorageTest{
		
		/**
		* Start the test
		*
		* @author Michael Pande
		*/
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

		
	

?>
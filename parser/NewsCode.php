x<?php
/*
	
	Retrieves and stores NewsCodes from the IPTC Controlled Vocabulary (CV) for NewsCodes
	
	

*/


NewsCodes::test();

class NewsCodes{
	
	$destination_url = "cache";
	
	
	public static function test(){
		
	// Arrange
	
		// Related Concept (skos:exactMatch & skos:broader):
		$subjectCode1 = "subj:15015005";
		$medtop1 = "medtop:20000979";
		$correct1EN_GB = "K1 (kayaking)";
		$correct1DE = "";
		$correct1FR = "";
		$correct1NO = "";
		
		
	
		

		
	// Act
		$result1EN_GB = getBestMatch($subjectCode1, "EN-GB");	
		$result1DE = getBestMatch($subjectCode1, "DE");
		$result1FR = getBestMatch($subjectCode1, "FR");
		$result1NO = getBestMatch($subjectCode1, "NO");
		
		
	// Assert
		$success = true;
		
		if($result1EN_GB != $correct1EN_GB){
			echo "<br>$subjectCode1 failed<br> ". $result1EN_GB ." != ". $correct1EN_GB."<br>";
			$success = false;
		}
		
		
		
		
	}

	// Returns the string best matching the NewsCode in given language.
	public static function getBestMatch($subjectCode, $mediatopic, $lang){
		if($lang == null){
			$lang = "EN-GB";
		}
		
		$result = null;
		
		// Get subject matching subject code
		$result = getSubject($subjectCode,$lang);
		
		
		// Check if subject matching failed
		if($result == null){
			
			// Subject matching failed and it will now attempt to use provided mediatopic
			$result = getMediatopic($mediatopic,$lang);
		}
		
		// No match found, return empty string.
		if($result == null){
			return "";
		}
		
		return $result;
		
		
	}	
		
		
		
		
		
	// Get newscode with matching subj::
	private static function getSubject($subjectCode, $lang){
		
		// CONTACT 
		
		
	}
	
	// Get mediatopic with matching medtop::
	private static function getMediatopic($mediatopic, $lang){
		
		
	}
	
	
	
	private static function updateSubjects(){
		
		file_put_contents("Tmpfile.zip", fopen("http://someurl/file.zip", 'r'));
	}
	
	private static function updateMediatopics(){
		
	}
	
	
	
	
	
	
	
	
	
	// Update newscodes
	
	
	
	
	
	
}	
	

?>
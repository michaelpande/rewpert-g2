<?php
/*
	
	Retrieves and stores NewsCodes from the IPTC Controlled Vocabulary (CV) for NewsCodes
	
	

*/


QCodes::test();

class QCodes{

	
	
	public static function test(){
		
	require_once('KnowledgeItem/KnowledgeItem.php');
	//KnowledgeItemParse::test();
	QCodes::update("KnowledgeItem/test_subjectcode.xml");
	//QCodes::update("http://cv.iptc.org/newscodes/subjectcode?format=g2ki&lang=en-GB");
	
	
	/*// Arrange
	
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
		
		
		*/
		
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
		
		
		
	// Update qcodes by URL or File	
	public static function update($file){
		require_once('/KnowledgeItem/KnowledgeItem.php');
		$subjects = KnowledgeItemParse::getQCodes($file);
		
		include('/Database/API.php');
		
		// Updates DB with the new QCodes. 
		$db = new SimpleStorage();
		$db->database_name("Test.db");
		$db->prepare(true);
		foreach($subjects as $value){
			$db->update($value['qcode'],"", $value['name']);
		}
		$db->execute();
		
		echo "<br><br>----CONTENTS----<br><br>";
		foreach($subjects as $value){
			echo("SimpleStorage : <br>". $value['qcode'] ." : ". $db->get($value['qcode'],"") . "<br><br>");

		}
		
		
	}
		
		
		
		
		
		
		
		
		
		
	// Get newscode with matching subj::
	private static function getSubject($subjectCode, $lang){
		
		// CONTACT 
		
		
	}
	
	// Get mediatopic with matching medtop::
	private static function getMediatopic($mediatopic, $lang){
		
		
	}
	
	
	
	private static function updateWP(){
		
		// Update every week
				// QUERY -> LATEST NML2_UPDATED_TIME > 1 WEEK
				
		// DO UPDATE
				// QUERY -> UPDATE NML2_UPDATED_TIME
		
	}
	
	private static function updateMediatopics(){
		
	}
	
	
	
	
	
	
	
	
	
	// Update newscodes
	
	
	
	
	
	
}	
	

?>
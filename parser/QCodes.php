<?php



require_once('Database/API.php');
require_once('KnowledgeItem/KnowledgeItem.php');

//QCodes::test(); (Testing method)


/**
* Retrieves and stores NewsCodes (QCodes) from the IPTC Controlled Vocabulary (CV) for NewsCodes

*
* @author Michael Pande
*/
class QCodes{

	/**
	 * Method for testing functionality, and debugging.
	 *
	 * @author Michael Pande
	 */
	public static function test(){
		
	
		QCodes::update("KnowledgeItem/test_scene_de.xml");
		QCodes::update("KnowledgeItem/test_subjectcode-en.xml");
		QCodes::update("KnowledgeItem/test_mediatopic.xml");
		QCodes::update("KnowledgeItem/test_scene.xml");
		//QCodes::update("http://cv.iptc.org/newscodes/subjectcode?format=g2ki&lang=en-GB"); // Max 10/hour 
		
	
	}

	// Returns the string best matching the NewsCode in given language.
	public static function getSubject($qcode, $lang){
		$lang = ($lang == null) ? "" : $lang; // Guarantees a set value.
		$qcode = ($qcode == null) ? "" : $qcode; // Guarantees a set value.
		echo "<br>getSubject($qcode , $lang);<br>";
		$db = new SimpleStorage();
		return unserialize($db->get($qcode,$lang));

	}	
	
	// Removes all qcodes with same language and qcode from database as given KnowledgeItem.
	public static function remove($file){
		$subjects = KnowledgeItemParse::getQCodes($file);
		
		
		$db = new SimpleStorage();
		$db->prepare(false);
		foreach($subjects as $value){
			$db->remove($value['qcode'],$value['lang']);
		}
		$db->execute();
		
	}	
		
		
	public static function setSubject($qcode, $lang, $value){
		$lang = ($lang == null) ? "" : $lang; // Guarantees a set value.
		$qcode = ($qcode == null) ? "" : $qcode; // Guarantees a set value.
		$value = ($value == null) ? "" : $value; // Guarantees a set value.
		$db = new SimpleStorage();
		$db->update($qcode, $lang,serialize($value));
	}	
		
	// Update qcodes by URL or File	
	public static function update($file){
		
		$subjects = KnowledgeItemParse::getQCodes($file);

		// Updates DB with the new QCodes. 
		$db = new SimpleStorage();

		$db->prepare(true);
		// Update/Insert all values
		foreach($subjects as $value){
			$db->update($value['qcode'],$value['lang'], serialize($value));
			
		}
		$db->execute();
		
		
		echo "<br><br>----CONTENTS----<br><br>";
		foreach($subjects as $value){
			$var = $db->get($value['qcode'],$value['lang']);
			var_dump(unserialize($var));
			
		}
		
		
		
		
		
		
	}
		
		
		
	
	
	
	
	// Update newscodes
	
	
	
	
	
	
}	
	

?>
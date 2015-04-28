<?php
require_once('Database/SimpleStorage.php');
require_once('KnowledgeItem/KnowledgeItem.php');

//QCodes::test(); //(Testing method)


/**
* Retrieves and stores NewsCodes (QCodes) from KnowledgeItems
*
* @author Michael Pande
*/
class QCodes{

	/**
	 * Method for testing functionality, and debugging
	 *
	 * @author Michael Pande
	 */
	public static function test(){
		
	
		
		QCodes::updatePluginDB("KnowledgeItem/test_subjectcode-en.xml");
		QCodes::updatePluginDB("KnowledgeItem/test_mediatopic.xml");
		//QCodes::update("http://cv.iptc.org/newscodes/subjectcode?format=g2ki&lang=en-GB"); // Max 10/hour (IPTC Controlled Vocabulary)
		
	
	}


    /**
     *  Returns the string best matching the QCode in given language.
     * @param $qcode
     * @param $lang
     * @return mixed
     * @author Michael Pande
     */
    public static function getSubject($qcode, $lang){
		$lang = ($lang == null) ? "" : $lang; // Guarantees a set value.
		$qcode = ($qcode == null) ? "" : $qcode; // Guarantees a set value.

		$db = new SimpleStorage();
		return unserialize($db->get($qcode,$lang));

	}	

    /**
     * Removes all QCodes with same language and QCode from database as given KnowledgeItem.
     * @param $file
     */
    public static function remove($file){
		$subjects = KnowledgeItemParse::getQCodes($file);
		
		
		$db = new SimpleStorage();
		$db->prepare(false);
		foreach($subjects as $value){
			$db->remove($value['qcode'],$value['lang']);
		}
		$db->execute();
		
	}


    /**
     * Set subject value with given language and QCode
     *
     * @param $qcode
     * @param $lang
     * @param $value
     */
    public static function setSubject($qcode, $lang, $value){
		$lang = ($lang == null) ? "" : $lang;       // Guarantees a set value.
		$qcode = ($qcode == null) ? "" : $qcode;    // Guarantees a set value.
		$value = ($value == null) ? "" : $value;    // Guarantees a set value.
		$db = new SimpleStorage();
		$db->update($qcode, $lang,serialize($value));
	}	
		

    /**
     * Update QCodes by URL or File
     *
     * @param $file - file or url
     * @return bool
     */
    public static function updatePluginDB($file){
		
		$subjects = KnowledgeItemParse::getQCodes($file);

		if($subjects == null){
			return false;
		}

		$db = new SimpleStorage(); // Updates DB with the new QCodes.

		$db->prepare(true);

		foreach($subjects as $value){   // Update/Insert all values
			$db->update($value['qcode'],$value['lang'], serialize($value));
			
		}
		$db->execute();

		
		
		return true;
		
		
		
	}

	
	
	
	
}	

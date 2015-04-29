<?php

// Simply looks KnowledgeItems for concepts with all the necessary data to store an item in the database.

/**
* This is a parser class that partially parses NewsML-G2 KnowledgeItems, the test only tests some files gotten from the IPTC CV of subjects and mediatopics.
* Every concept it finds will be added to an array that will be returned. 
* Test it with KnowledgeItem::test();
* @author Michael Pande
*/
class KnowledgeItemParse {
	
	
	
		/**
		 * Tests the retrieval of QCodes from KnowledgeItems
		 * 
		 * @author Michael Pande
		 */
		public static function test(){
			
			var_dump(KnowledgeItemParse::getQCodes("KnowledgeItem/test_subjectcode.xml"));
			var_dump(KnowledgeItemParse::getQCodes("KnowledgeItem/test_mediatopic.xml"));
			

		
		
		}

		/**
		 *  Accepts a NewsML-G2 KnowledgeItem and retrieves the concepts & QCodes.
		 * 
		 * @return Array of concepts & QCodes or null
		 *
		 * @author Michael Pande
		 */
		public static function getQCodes($file){
			
			
			$doc = KnowledgeItemParse::getDocument($file);
			if($doc == null){
				return null;
			}
			$xpath = new DOMXPath($doc);
			
			
		
			// XML register namespaces
			$xpath->registerNamespace('html', "http://www.w3.org/1999/xhtml");
			$xpath->registerNamespace('knowledgeItem', "http://iptc.org/std/nar/2006-10-01/");
			
			return KnowledgeItemParse::getSubjects($xpath);
			
			
		}
	
		
		/**
		 * Accepts a DomXPATH object from a NewsML-G2 KnowledgeItem and retrieves the concepts & QCodes.
		 * 
		 * @return Array of concepts & QCodes
		 *
		 * @author Michael Pande
		 */
		private static function getSubjects($xpath) {
			
			$subjects = array();

			$nodeList = $xpath->query("//knowledgeItem:concept");
			
			foreach($nodeList as $node) {
			
				$subjectName = null;
				$subjectDescription = null;
				$qcode = null;
				$lang = null;
				$date = null;
				
				
				
				
				// Language
				$names = $xpath->query("knowledgeItem:name[@xml:lang]/@xml:lang", $node);
		
				foreach($names as $name){
					$lang = $name->nodeValue;
				}
				
				
				
				// Name
				$names = $xpath->query("knowledgeItem:name", $node);
		
				foreach($names as $name){
					$subjectName = $name->nodeValue;
				}
				
				// Description
				$names = $xpath->query("knowledgeItem:definition", $node);
				
				foreach($names as $name){
					$subjectDescription = $name->nodeValue;
				}
				
				// Concept QCode 
				$names = $xpath->query("knowledgeItem:conceptId[@qcode]", $node); 
				
				foreach($names as $name){
					$qcode = $name->getAttribute('qcode');
				}
				
				// Date
				$date = $node->getAttribute('modified');
				
				
				
				$item = array(

					'qcode'      => $qcode, // The unique qcode
					'lang'     => $lang, // The title of your post.
					'name'          => $subjectName, // name tag
					'description'   => $subjectDescription, // definition tag
					'date' => $date,
					
				);
				
				//echo $subjectName ." : ";
				array_push($subjects, $item);
			
				
			}
			
			
			
			
			
			
			
			
			
			
			
			return $subjects;
		}

	
	
	
	
	
	
	/**
	 *  Checks if URL to file exists  
	 * 
	 * @return boolean
	 *
	 * @author Michael Pande
	 */
	private static function urlExist($url){
		$file_headers = @get_headers($url);
		if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
			return false;
		}
		else {
			return true;
		}
	}
	
	
	/**
	 * Gets document either from file or from URL
     *
	 * @param $file
	 * @return DOMDocument
	 *
	 * @author Michael Pande
	 */
	private static function getDocument($file){
		
		$doc = new DOMDocument();
		
		if($file == null){
			return null;
		}


        set_error_handler(function() { /* ignore errors */ });
        try{
            $file = ltrim($file);
            if(is_file($file)) {    //Checks if $file is file or text
                $doc->load($file);
            }
            else {
                $doc->loadXML($file);
            }
        }catch(Exception $e){
            restore_error_handler();
            return null;
        }
        restore_error_handler();



		return $doc;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
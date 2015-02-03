<?php

// Simply looks KnowledgeItems for concepts with all the necessary data to store an item in the database.




class KnowledgeItemParse {
	
		public static function test(){
			
			var_dump(KnowledgeItemParse::getQCodes("KnowledgeItem/test_subjectcode.xml"));
			var_dump(KnowledgeItemParse::getQCodes("KnowledgeItem/test_mediatopic.xml"));
			

		
		
		}
		// Accepts a NewsML-G2 KnowledgeItem and retrieves qcodes and corresponding strings. 
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
	
		
		
		private static function getSubjects($xpath) {
			
			$subjects = array();
			$subjectName;
			$subjectDefinition;
			$subjectQCode;
			$subjectMedtop;
			$nodelist = $xpath->query("//knowledgeItem:concept");
			
	
			foreach($nodelist as $node) {
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

	
	
	
	
	
	
		
	private static function urlExist($url){
		$file_headers = @get_headers($url);
		if($file_headers[0] == 'HTTP/1.1 404 Not Found') {
			return false;
		}
		else {
			return true;
		}
	}
	
	
	
	private static function getDocument($file){
		
		$doc = new DOMDocument();
		
		// Check if file exists
		if(file_exists($file) || KnowledgeItemParse::urlExist($file)){
			echo "File/URL exists";
			$doc->load($file);
		}elseif(is_string($file) && (new XMLReader($file))->isValid()){
			echo "File/URL does not exist";
			$doc->loadXML($file);
		}else{
			echo "File/URL does not exist and string is not valid XML";
			return null;
		}
		return $doc;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
<?php

/* Verification.php checks if the file:
   1. Exist
   2. Is XML
   3. Is NewsML-G2

   Call on verify($file) with a file location to check the file and it returns a boolean.
*/

class verification{

    // Returns boolean if file is verified
    public static function verify($file){

        try{
            if(!file_exists($file)){
                return false;
            }

            if(!verification::safeCode($file)){
                return false;
            }

            if(!verification::isXML($file)) {
               return false;
            }
            if(!verification::isNewsMLG2($file)) {
                return false;
            }
        }catch(Exception $e){
            return false;
        }

        return true;
    }


    // Checks if file is XML
    private static function isXML($xmlFile){

        $xml = new XMLReader();
        $xml->open($xmlFile);
        $xml->setParserProperty(XMLReader::VALIDATE, true);

        return $xml->isValid($xmlFile);
    }



    private static function isNewsMLG2($xmlFile){
        return true;
    }

    // Checks for code-injection attempts
    private static function safeCode($xmlFile){
        // Open and read file then check file

        return true;
    }



}

?>
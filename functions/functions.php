<?php

	/**
	 * Returns useful debugging messages if &debug=true
	 * echos strings and xs everything else.
	 *
	 * @param $str - String or item
	 *
	 * @author Michael Pande
	 */
	function debug($str){
		global $DEBUG;
		if($DEBUG){
			if(is_string($str)){
				echo "<p>".$str."</p>"; // Using <p> to create new lines. 
			}else{
				var_dump($str);
			}
		}
	}

    function getUserInput(){

        // Checks request method, returns 400 if not HTTP POST
        if($_SERVER['REQUEST_METHOD'] != 'POST'){
            debug("The REQUEST_METHOD was not POST");
            setHeader(400); // Bad Request
            exitAPI();
        }

        $userInput = null;

        $file = fileUpload();
        if($file != null){
            $userInput = $file;
        }else{
            $userInput = getRequestParams();
        }

        return $userInput;

    }


	/**
	 * @author Stefan Grunert
	 */
	function getRequestParams()
	{
		 if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			return $_GET;
		 } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
			return file_get_contents("php://input");
		 } elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
			return file_get_contents("php://input");
		 } elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
			return file_get_contents("php://input");
		 }
	}


/**
 * Gets content from uploaded file
 * @return File contents
 *
 * @author Michael Pande
 */
function fileUpload(){
    if($_FILES == null || isset($_FILES["uploaded_file"]) || isset($_FILES["uploaded_file"]["tmp_name"])){
        return null;
    }
    return file_get_contents($_FILES["uploaded_file"]["tmp_name"]);
}

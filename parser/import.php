<?php

    




function StartImport($file){
    /* Checks for valid HTTP_POST
    if(isset($_POST["submit"]) && isset($_FILES["importedFile"])) {
        $file = $_FILES["importedFile"]["tmp_name"];
    }else{
        return;
    }*/





// Verification.php: Checks if file is NewsML-G2
    /*if(!@include("verification.php")) throw new Exception("'verification.php' seems to be missing.");
    $ver = verification::verify($file);

    if(!$ver){
        echo "The file is not a NewsML-G2 XML file.";
        return;
    }*/





// Parse.php: Parses contents into file.
    include('Parse.php');
    $post = Parse::createPost($file);
    //var_dump($post);
	
	return json_encode($post);



}



?>


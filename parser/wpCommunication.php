<?php

    // Inserts post into the wordpress database by using the API.
    public static function insertPost($post){

        $post_id = wp_insert_post( $post, "" );//wp_insert_post( $post, $wp_error );

    }


    // Inserts post into the wordpress database by using the API.
    function newsml_ajax_insert_handler(){
		
		
		// POST DATA $_POST m.m
		$post = "";
		if(isset($_POST['xmlItem']) && !empty($_POST['xmlItem'])) {
			
			$file = $_POST['xmlItem'];
			
			$post = StartImport(stripslashes($file)); // Removes backslashes, which will not parse.
			
			if(isset($post)){
				$post_id = wp_insert_post( $post, "" );//wp_insert_post( $post, $wp_error );
			}
		}
		else{
			echo '$_POST["xmlItem"] is empty';
		}
	
		
       
		
		
		
		
		// Return some info
		$response = array(
			'status' => '200',
			'message' => 'OK',
			'post_ID' => $post_id
		);

		echo json_encode( $response );

		
		
		die();
    }

	
	function StartImport($file){


		// Parse.php: Parses contents into file.
		include('Parse.php');
		$post = Parse::createPost($file);
		var_dump($post);
		
		return $post;



}




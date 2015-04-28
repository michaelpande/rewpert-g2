<?php

    /**
     * Plugin Name: Rewpert-G2
     * Plugin URI: http://demo-nmlg2wp.rhcloud.com/
     * Description: A RESTful WordPress plugin for importing NewsML G2. The repository and documentation can be found on plugin url.
     * Version: 1
     * Author: Michael Pande, Petter Lundberg Olsen, Diego A. Pasten Bahamondes
     * Author URI: http://demo-nmlg2wp.rhcloud.com/
     * License: MIT
     *
     */


    /**
     * @Author Michael Pande
     */
	add_action('wp_ajax_newsml_ajax_insert_post', 'newsml_ajax_insert_handler');
	add_action('nml2_get_all_authors','nml2_get_all_authors');
	//add_action('wp_ajax_nopriv_newsml_ajax_insert_post', 'newsml_ajax_insert_handler');
	
	
	// Adds a menu link to the admin panel in wordpress
	add_action('admin_menu', 'NewsML_Admin_Menu');
    function newsML_Admin_Menu(){
		
	
        // Image path should be set for /images/thumbnail.png
        add_posts_page( "Rewpert-G2", "Rewpert-G2", "manage_options", "rewpert-g2", "panel_init","/images/thumbnail.png" );
    }

	
    // Initializes the  panel for import
    function panel_init(){
		
        define( 'PLUGIN_DIR', dirname(__FILE__).'/' );
        
        // CSS
        EnqueueStyles();

        include("admin_panel.php");

    }


	function nml2_get_all_authors(){
		$id = get_the_ID();
		
		$multi_authors = get_post_meta($id, 'nml2_multiple_authors');
		
		if($multi_authors != null){
			$multi_authors = explode(",", $multi_authors[0]);
			$multi_authors = array_unique($multi_authors);
			$count = 0;
			$author_string = "";
			foreach($multi_authors as $nameKey=>$nameVal){
				
				$user = get_user_by('id',$nameVal);
				if($user){
					if($count > 0){
						$author_string .= ", ";
					}
					$count++;
					$url = esc_url( get_author_posts_url( $user->ID ));
					$author_string .= '<a href="'.$url.'">'.$user->user_login.'</a>';
					
				}
				
			}
			
			if($author_string != ""){
				return $author_string;
			}
		}
		
		return get_the_author();
		
	}
	
	
	function nml2_get_author_posts(){
		
		// NML2_MULTI_AUTHOR SUPPORT:
		if(isset($_GET['author'])){
			$ID = $_GET['author'];
		}else{
			$ID = the_author_meta('ID');
		}
		

		$author = get_posts(array(
		
		'post_status' => 'publish',
		'post_type' => 'post',
		'relation' => 'OR',
		'author' => $ID
		
	
		));
		
		$meta_author = get_posts(array(
			
			'post_status' => 'publish',
			'post_type' => 'post',
			'relation' => 'AND',
			
			'meta_key'     => 'nml2_multiple_authors',
			'meta_value'   => $ID,
			'meta_compare' => 'LIKE'
		
	
								
			
			
		
			));
			
		$mergedPosts = array_merge($meta_author, $author);
		

		$postIDs = array();

		foreach( $mergedPosts as $item ) {
			
				$postIDs[]=$item->ID;
			
		}
		
		$uniquePosts = array_unique($postIDs);
		
		
		$posts = get_posts(array(
				'post__in' => $uniquePosts,
				));
				
		
		return $posts;	

		
		
		
		
	}



    // Adds CSS theme through the wordpress API
    function EnqueueStyles() {

        wp_enqueue_style(
            'newsmlg2-plugin',
            getPathToPluginDir() .'theme.css' ,
            null,
            null
        );
    }






    // Returns the path to plugin directory relative to the wordpress root folder
    function getPathToPluginDir(){


        if(!defined('PLUGIN_DIR')){
            define( 'PLUGIN_DIR', dirname(__FILE__).'/' );
        }
		$str = WP_PLUGIN_URL .'/'. basename(__DIR__) .'/';
		
		
		$str = str_replace(' ', '%20', $str);
		
        return  $str;

    }


?>
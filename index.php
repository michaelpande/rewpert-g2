<?php

    /**
     * Plugin Name: RESTFul NewsML-G2 Import
     * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
     * Description: A NewsML-G2 Import plugin for wordpress
     * Version: 1.0.0
     * Author: Name of the plugin author
     * Author URI: http://URI_Of_The_Plugin_Author
     * Text Domain: Optional. Plugin's text domain for localization. Example: mytextdomain
     * Domain Path: Optional. Plugin's relative directory path to .mo files. Example: /locale/
     * Network: Optional. Whether the plugin can only be activated network wide. Example: true
     * License: A short license name. Example: GPL2
     */


    
	// Creates a hook for the ajax actions
	//include('parser/wpCommunication.php');
	
	add_action('wp_ajax_newsml_ajax_insert_post', 'newsml_ajax_insert_handler');
	add_action('nml2_get_all_authors','nml2_get_all_authors');
	//add_action('wp_ajax_nopriv_newsml_ajax_insert_post', 'newsml_ajax_insert_handler');
	
	
	// Adds a menu link to the admin panel in wordpress
	add_action('admin_menu', 'NewsML_Admin_Menu');
    function newsML_Admin_Menu(){
		
	
        // Image path should be set for /images/thumbnail.png
        add_posts_page( "NewsML-G2 Import", "NewsML-G2 REST API", "manage_options", "newsml-g2", "panel_init","/images/thumbnail.png" );
    }

	
    // Initializes the  panel for import
    function panel_init(){
		
        define( 'PLUGIN_DIR', dirname(__FILE__).'/' );
        
        // CSS
        EnqueueStyles();

        // Javascript
        EnqueueScripts();

        include("admin_panel.php");

    }

	
	
	
	// This method is used to get multiple authors.
	/*
	if(function_exists('nml2_get_all_authors'))
   				nml2_get_all_authors();
			else
				the_author_posts_link();
	
	*/
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
		
		
		ob_start();
		the_author_meta('ID');
		$ID = ob_get_contents();
		ob_end_clean();
		
		$author = get_posts(array(
		
		'post_status' => 'publish',
		'post_type' => 'post',
		'relation' => 'OR',
		'author' => $ID
		
	
		));
		$meta_author = get_posts(array(
			
			'post_status' => 'publish',
			'post_type' => 'post',
			'relation' => 'OR',
			
			'meta_query' => array(
									'relation' => 'OR',
									array(
										'key'     => 'nml2_multiple_authors',
										'value'   => $ID,
										'compare' => 'LIKE'
									)
								
								)
			
			
		
			));

			
		$mergedposts = array_merge($meta_author, $author);
		

		$postids = array();
		foreach( $mergedposts as $item ) {
			$postids[]=$item->ID;
		}
		
		$uniqueposts = array_unique($postids);
		
		
		$posts = get_posts(array(
				'post__in' => $uniqueposts,
				));
				
		
		return $posts;	
		/* 
		$meta_query_args = array(
			'relation' => 'OR',
								
			'meta_query' => array(
								
								  array(
										'key'     => 'nml2_multiple_authors',
										'value'   => $ID,
										'compare' => 'LIKE'
									)
				)
			);
			*/
		
		
		
		
	}
	
	


    function EnqueueScripts(){

        // JQuery
        wp_enqueue_script('jquery-1112', "//code.jquery.com/jquery-1.11.2.min.js",null,null);
        wp_enqueue_script('jquery-mig', "//code.jquery.com/jquery-migrate-1.2.1.min.js",null,null);

		
		

		//wp_enqueue_script('ajaxcomm', getPathToPluginDir() . '/js/ajaxcomm.js',null,null);
        wp_enqueue_script('newsmlg2script', getPathToPluginDir() . '/js/newsmlg2.js',null,null);



        // Sets javascript variables
        /*wp_localize_script('newsmlg2script', 'php_vars', array(
                'plugin_path' => __(getPathToPluginDir()),
				'wp_ajax' => admin_url( 'admin-ajax.php' )
            )
        );
		*/
		
		
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
        define( 'PLUGIN_DIR', dirname(__FILE__).'/' );

		$str = WP_PLUGIN_URL .'/'. basename(__DIR__) .'/';
		
		
		$str = str_replace(' ', '%20', $str);
		
        return  $str;

    }


?>
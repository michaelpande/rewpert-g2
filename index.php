
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




    function EnqueueScripts(){

        // JQuery
        wp_enqueue_script('jquery-1112', "//code.jquery.com/jquery-1.11.2.min.js",null,null);
        wp_enqueue_script('jquery-mig', "//code.jquery.com/jquery-migrate-1.2.1.min.js",null,null);



		wp_enqueue_script('ajaxcomm', getPathToPluginDir() . '/js/ajaxcomm.js',null,null);
        wp_enqueue_script('newsmlg2script', getPathToPluginDir() . '/js/newsmlg2.js',null,null);



        // Sets javascript variables
        wp_localize_script('newsmlg2script', 'php_vars', array(
                'plugin_path' => __(getPathToPluginDir()),
				'wp_ajax' => admin_url( 'admin-ajax.php' )
            )
        );
		
		
		
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


        // Necessary to get the actual URL to plugins in wordpress, instead of wp-admin.
        $current_directory = explode('\\', PLUGIN_DIR);
        $dir = $current_directory[count($current_directory)-1]; // Name of pluginfolder



        return  WP_PLUGIN_URL .'/'. $dir;

    }


?>


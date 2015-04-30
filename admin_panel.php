<?php


    /**
     * @Author Michael Pande
     */

	if(isset($_POST["NewKey"])){
		updateAPIkey();
	}else{
		createAPIkey();
	}
	
	
	// Returns the API key from the Wordpress Database
	function getAPIkey(){
		if(strlen(get_option("nml2-plugin-api-key")) < 1){
			return createAPIkey();
		}
		return get_option("nml2-plugin-api-key");
	}
	
	// Stores API key in database
	function createAPIkey(){
		$key = bin2hex(openssl_random_pseudo_bytes(32));
		add_option( 'nml2-plugin-api-key', $key, '', 'yes' ); 
		return $key;
	}
	
	// Stores API key in database
	function updateAPIkey(){
		$key = bin2hex(openssl_random_pseudo_bytes(32));
		update_option( 'nml2-plugin-api-key', $key, '', 'yes' ); 
		return $key; 
	}



?> 






<!-- Import file -->

	<script language="javascript">
	window.onload = function(e){ 
	
		
		// Appends / Removes HTTP_GET parameter from URL
		
		var urlInput = document.getElementById('url');
		var url = urlInput.value;
		var debugbox = document.getElementById('debugbox');
		var updatebox = document.getElementById('updatebox');
        var validatebox = document.getElementById('validatebox');
		
		
		debugbox.addEventListener("click", function(){
			updateURL();
		});
		
		updatebox.addEventListener("click", function(){
			updateURL();
		});

        validatebox.addEventListener("click", function(){
            updateURL();
        });

		
		function updateURL(){

			var full_url = url;
			if(updatebox.checked)
				full_url = full_url + "&update_override=true";
			if(debugbox.checked)
				full_url = full_url + "&debug=true"
            if(!validatebox.checked)
                full_url = full_url + "&validate=false"


            urlInput.value = full_url;
		}
		
		
	};
	</script>
    
	<h1>Rewpert-G2</h1><br>
	
	
	<h2>Usage</h2>
	<ul class="indent">
		<li>The API returns <strong>HTTP Status Codes</strong> and <strong>requires authentication</strong>  (API Key)</li>
		<li>Content-Type: <strong>application/xml</strong></li>
	</ul>
	<br>
	
	<?php echo '<form class="well" action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'?page=rewpert-g2" method="post" enctype="multipart/form-data">' ?>
	<h3>POST the XML to the following url</h3>
<label for="validatebox" >Validate NewsML-G2</label> <input type="checkbox" name="validatebox" id="validatebox" value="true" checked/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<label for="debugbox" >Debug</label> <input type="checkbox" name="debugbox" id="debugbox" value="false" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<label for="updatebox" >Update Override</label> <input type="checkbox" name="updatebox" id="updatebox" value="false" /><br><br>

    <input style='width:100%' id="url" type="text" value='<?php echo getPathToPluginDir();?>RESTApi.php?key=<?php echo getAPIkey(); ?>' />
	<br><br>
	<br>
	
	<form class="well" action="" method="post">
		<input type="hidden" name="NewKey" value="true" /><br>
		<input type="submit"  value="Create new API key and update the URL" />

	</form><br><br>
	
	
	<div class="nml2-container">
	<h2>Manual Upload</h2><br>
	<p class="indent"><strong>Supports: </strong>NewsItems & KnowledgeItems. </p>
	<p class="indent"><strong>Update: </strong>It overwrites existing post with GUID, and ignores version.</p>
<br>
	
	<form class="indent" id="manual" action='<?php echo getPathToPluginDir();?>RESTApi.php?key=<?php echo getAPIkey(); ?>&manual=true&update_override=true' enctype="multipart/form-data" method="post">
		
		<input type="file" name="uploaded_file" id="uploaded_file" ><br><br>
		<input type="submit" id="startImport" value="Start import" name="submit" >
	</form><br><br>
	<div id="response">
	</div>


<h2>Quick reference</h2>
		<div class="nml2-right">
			<h4>Documentation</h4>

			<a href="http://demo-nmlg2wp.rhcloud.com/documentation.php">Documentation</a><br>
			<a href="https://bitbucket.org/michaelpande/newsml-g2-restful-wordpress-import">Source code</a><br>
		
		</div>
		<div class="nml2-right">
			<h4>Links</h4>
            <a href="http://demo-nmlg2wp.rhcloud.com">Plugin page</a><br>

            <a href="http://www.iptc.org/site/News_Exchange_Formats/NewsML-G2/">IPTC NewsML-G2</a><br>
			
			
		</div>
	</div>
	<br><br>	
	<h2>Security</h2>
	<ul class="indent">
		<li>The API key will be <strong>hidden when using SSL</strong></li>
		<li>The API key is generated with openssl_random_pseudo_bytes(32)</li>
		<li>The API key is stored in the Wordpress Database </li>
	</ul>

	
	
	
	
	
	
	
	<br><br><br><br>
	















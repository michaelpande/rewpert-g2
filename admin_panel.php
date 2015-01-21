<?php
	
    // errorLogger.php is our global error logger. It saves the errorlogs to a file.
    include('/parser/errorLogger.php');
	
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
		//add_option( 'myhack_extraction_length', '255', '', 'yes' ); 
	}
	
	// Stores API key in database
	function updateAPIkey(){
		$key = bin2hex(openssl_random_pseudo_bytes(32));
		update_option( 'nml2-plugin-api-key', $key, '', 'yes' ); 
		return $key;
		//add_option( 'myhack_extraction_length', '255', '', 'yes' ); 
	}



?> 







<!-- Import file -->
<?php echo '<form action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'?page=newsml-g2" method="post" enctype="multipart/form-data">' ?>
    
	<h1>RESTful NewsML-G2</h1><br>
	<h2>Usage</h2>
	<ul>
		<li>The API returns <strong>HTTP Status Codes</strong> and <strong>requires authentication</strong>  (API Key)</li>
		<li>The API key is randomly generated and is part of the URL </li>
		<li>The key will be <strong>hidden when using SSL</strong></li>
		<li>Content-Type: <strong>application/xml</strong></li>
	</ul>
	<br>
	<h4>POST the XML directly to the following url:</h4> <input style='width:100%' type="text" value='<?php echo getPathToPluginDir();?>RESTApi.php?key=<?php echo getAPIkey(); ?>' />
	
	<br>
	
	<form action="" method="post">
		<input type="hidden" name="NewKey" value="true" /><br>
		<input type="submit"  value="Create new API key and update the URL" />

	</form>
			<p>
		The API key is generated with openssl_random_pseudo_bytes(32)
	</p>
	<br><br>
	<!--<h2>Examples:</h2>-->
	<br>
	<pre>
		
	</pre>
	<br>
	
	
	
	
	
	
	
<!--	
	<br><br><br><br>
	
	<h2>Select NewsML-G2 File</h2>
<?php echo '<input type="file" name="files[]" id="importedFile"  enctype="multipart/form-data"  data-url="'.getPathToPluginDir().'/lib/jQuery-File-Upload/server/php"  multiple><br><br>' ?>
    <div id="selectedFiles">
		<table id="selectedFiles">

		</table>
	</div>
	
	
	
	


    <input type="submit" id="startImport" value="Start importing" name="submit" >
</form>

-->














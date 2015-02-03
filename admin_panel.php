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

    
	<h1>RESTful NewsML-G2</h1><br>
	
	
	<h2>Usage</h2>
	<ul class="indent">
		<li>The API returns <strong>HTTP Status Codes</strong> and <strong>requires authentication</strong>  (API Key)</li>
		<li>Content-Type: <strong>application/xml</strong></li>
	</ul>
	<br>
	
	<?php echo '<form class="well" action="'.htmlspecialchars($_SERVER["PHP_SELF"]).'?page=newsml-g2" method="post" enctype="multipart/form-data">' ?>
	<h3>POST the XML to the following url</h3> 
	<label for="debugbox" >Debug</label> <input type="checkbox" name="debugbox" id="debugbox" value="false" /><br><br>
	<input style='width:100%' id="url" type="text" value='<?php echo getPathToPluginDir();?>RESTApi.php?key=<?php echo getAPIkey(); ?>' />
	<br><br>
	<br>
	
	<form class="well" action="" method="post">
		<input type="hidden" name="NewKey" value="true" /><br>
		<input type="submit"  value="Create new API key and update the URL" />

	</form><br><br>
	<h2>QCodes, (Subjects, Mediatopics)</h2>
	<p><strong>IPTC CV Subjects: </strong>http://cv.iptc.org/newscodes/subjectcode?format=g2ki&lang=en-GB</p>
	<p><strong>IPTC CV Mediatopics: </strong>http://cv.iptc.org/newscodes/mediatopic?format=g2ki&lang=en-GB</p>
	<form action="" method="post" style="display:inline-block">
		<input type="hidden" name="NewKey" value="true" /><br>
		<input type="text" value="http://cv.iptc.org/newscodes/subjectcode?format=g2ki&lang=en-GB" />
		<input type="submit"  value="Update QCodes" /><br>
		

	</form>
		<form action="" method="post" style="display:inline-block">
		<input type="hidden" name="DeleteQCodes" value="true" /><br>
		<input type="submit"  value="Delete all QCodes" /><br>
		

	</form>
		
		
		
		
		<br><br>
	
	<br><br>
	
	<div class="nml2-container">
	<h2>Quick reference</h2>
		<div class="nml2-right">
			<h4>Documentation</h4>
			<a href="<?php echo getPathToPluginDir();?>docu">Documentation</a><br>
			<a href="<?php echo getPathToPluginDir();?>docu#jumpto_2">Examples</a><br>
			<a href="http://github.com">Source code</a><br>
		
		</div>
		<div class="nml2-right">
			<h4>Links</h4>
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














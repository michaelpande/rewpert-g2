







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
	<h4>POST the XML directly to the following url:</h4> <input style='width:100%' type="text" value='<?php echo getPathToPluginDir();?>RESTApi.php?key=qibgUASv9D489EL6tDEuNXyH3faoHkvWDxTssIWJhF3UlIGkGlUvGUDoMIPxeGGo' />
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








<?php
	
    // errorLogger.php is our global error logger. It saves the errorlogs to a file.
    include('/parser/errorLogger.php');




?> -->







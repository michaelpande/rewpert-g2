$(function(){
	

	// Adds event to file input
	//document.getElementById('importedFile').addEventListener('change', readFiles, false);
	
	
	
	// Appends / Removes HTTP_GET parameter from URL
	var url = $("#url").val();
	
	$('#debugbox').click(function (e) {
		
	   if($('#debugbox').prop('checked')){
		   $('#url').val(url + "&debug=true");
		   
	   }else{
		   $('#url').val(url);
		   
	   }

	});
	
});

	
	
/*	
	
    // If user has used the file input dialog
    function readFiles(fileList){
			
			
            

			// FOR EACH 
			for( var i = 0; i < fileList.target.files.length;i++){
            
				var f = fileList.target.files[i]; 
				$('').html("");
                $('#selectedFiles').append(
                    "<tr id='file"+i+"' class='not_imported'>files[i].<td class='fileIcon' id='fileIcon"+i+"'></td><td>"+ f.name + "</td><td class='fileText' id='fileText"+i+"'>Not imported</td></tr><br>"
                );
				
			}; // FOREND 
			
			

			
            $('#startImport').prop('disabled', false);
            // If the "Start Importing" button is clicked
            $('#startImport').click(function (e) {
                $(this).attr('disabled', 'disabled');

                e.preventDefault();

                importFile(fileList,0);

            });
    };




	// http://codex.wordpress.org/AJAX_in_Plugins
	 function ajaxcomm(xmlItem, id) {
		
		console.log("Ajax call to: " + php_vars.wp_ajax);
		$.ajax({
			type:'POST',
			url: php_vars.wp_ajax,
			
			
			data : { action: "newsml_ajax_insert_post", 'xmlItem': xmlItem},
			
			success: function(output) {
				console.log("Success response for "+id+": "+output); // Shows PHP errors and echos in the same page 
				$('#file'+id).removeClass("not_imported");
				$('#fileText'+id).html("Successful");
				$('#fileIcon'+id).html("ICON");
				
							
							
							
			},error: function(jqXHR, textStatus, errorThrown) {
				console.log(JSON.stringify(jqXHR));
				console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
				
			}
			
		});
		
	};



    function importFile(fileList, id){

		
			var f = fileList.target.files[id]; 
			
			
			if (f) {
				  var r = new FileReader();
				  
				  // ajaxurl is a global parameter gotten from Wordpress and sent from the initial php file (index.php)
				  r.onload = function(e) { 
				
						var contents = e.target.result;
						ajaxcomm(contents, id);
						id = id +1;
						
						setTimeout(importFile(fileList,id), 0);
						
				  }
				  r.readAsText(f);
				  
				   
				  
				  
				  
				  
				  
				} else { 
				  console.log("Failed to load file");
				}
			
			
           

            setTimeout(function(){
                $('#file'+i).removeClass("not_imported");
                $('#fileText'+i).html("Successful");
                $('#fileIcon'+i).html("ICON");

                // ScrollTo
                $("#selectedFiles").scrollTop($('#file'+i).height()*i);
            }, 500*i);






            /*$parent = $('#selectedFiles');
            $parent.scrollTop($parent.scrollTop() + $('file'+i).position().top);


	};

        // FOR EACH FILE IN FILES DO CHANGES TO MATCHING ROW WITH ID OF FILE.
        // 1. SCROLL TO
        // 2. CHANGE ICON, COLOR AND TEXT TO LOADING
        // 3. IF OK -> CHANGE ICON COLOR AND TEXT TO SUCCESSFUL
        // 3. IF FAIL -> CHANGE ICON COLOR AND TEXT TO FAIL


        







});
*/
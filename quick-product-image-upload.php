<?php

/**
 * Plugin Name: Quick Product Image Upload
 * Description: Great for product image management in WooCommerce! Easily upload and delete product images on main product screen, without having to edit each product. Uses AJAX - no page refresh between each upload.
 * Version: 1.0
 * Author: B Bendel
 */

// add set image column to product table in admin
add_filter('manage_posts_columns', 'set_image_column_header',1);
add_action('manage_posts_custom_column', 'set_image_column_content',1,2);

// set column header - filter
function set_image_column_header($defaults) {
    $defaults['upload'] = 'Upload';
    return $defaults;
}

// set column content (the upload and delete links) - action
function set_image_column_content($column_name, $post_ID) {
    if ($column_name == 'upload') {             	
  		echo "<div style='font-size:11px'>";
		echo "<a class='upload' id='".$post_ID."' href='#'>[Set Image]</a>";
		
		// show delete link (if no image, set to display:none)
		$thumbnail_id = get_post_meta( $post_ID, '_thumbnail_id', true );		
		echo "<br><a ";
		if( $thumbnail_id == ""){		
			echo " style='display:none' ";
		}
		echo " class='remove' data-thumbnailid='". $thumbnail_id ."' id='".$post_ID."' href='#'>[Delete]</a>";		
		echo "</div>";
    }
}
	
// open up upload media dialogue 
add_action('admin_footer', 'uploadProdImageInTable');
function uploadProdImageInTable() { 
	$post_type =  $_REQUEST['post_type'];	
    if(is_admin() and $post_type == 'product'){
	?>
	<script type='text/javascript'>
  		jQuery(document).ready(function() {
		   //uploading files variable
		   var custom_file_frame;
		   var postid; // make global
		   
		   // delete existing image
		   jQuery('.remove').click(function(e) {
		   	  	postid = jQuery(this).attr("id");
				var thumbid = jQuery(this).attr("data-thumbnailid");	
				e.preventDefault();	
				e.stopPropagation();
				
				// change thumbnail to placeholder	
				var targetE = "table.posts tr.post-" + postid + " td.thumb  img.wp-post-image";		
					var placeHolderImg = "<?= plugins_url('placeholder.png', __FILE__ ) ?>";								
				  	jQuery(targetE).attr("src",placeHolderImg);  	  					  
		   	  	
			  	var data = {	
					action: 			'deleteProductImage', 
					post_id: 			postid, 
					thumbnail_id:		thumbid				
				};									
					
				jQuery.post(ajaxurl, data, function(response) {					
					// hide the delete image link
					var targetE = "table.posts tr.post-" + postid + " td.upload a.remove";	
					jQuery(targetE).hide();		
					
				});
		   	});
			
			// upload new image
		    jQuery('.upload').click(function(e) {
		   	  	postid = jQuery(this).attr("id");	
				e.preventDefault();	
				e.stopPropagation();	  	  					  
		   	  	
			  	//If the frame already exists, reopen it
			  	if (typeof(custom_file_frame)!=="undefined") {
				 	custom_file_frame.close();
			  	}
			 
			  	//Create WP media frame.
			  	custom_file_frame = wp.media.frames.customHeader = wp.media({
				 	//Title of media manager frame
				 	title: "Set Product Image",
					 library: {
						type: 'image'
				 	},
				 	button: {
						//Button text
						text: "Set Product Image"
					 },
					 //Do not allow multiple files, if you want multiple, set true
					 multiple: false
			  	});
			
			  	//callback for selected image
			  	custom_file_frame.on('select',function() {
									 
					var attachment = custom_file_frame.state().get('selection').first().toJSON();				 	
					
					// update thumbnail in table			
						var targetE = "table.posts tr.post-" + postid + " td.thumb  img.wp-post-image";					
				  		jQuery(targetE).attr("src",attachment.url) 	
						
					// update post meta (php function below - uploadProductImage		
					var data = {	
						action: 			'uploadProductImage', 
						post_id: 			postid, 
						thumbnail_id:		attachment.id						
					};									
					
					jQuery.post(ajaxurl, data, function(response) {
						// show the delete image link
						var targetE = "table.posts tr.post-" + postid + " td.upload a.remove";	
						jQuery(targetE).show();			 	
					});
										 	
			  });		 
			  	//Open modal
			 	custom_file_frame.open();
		   	});
		});
	</script>
<?php }
}

// get the postid and imageid from the json and delete the product's image using delete_post_meta
add_action('wp_ajax_deleteProductImage', 'deleteProductImage');
function deleteProductImage(){
	 $post_id =  $_POST['post_id'] ;
	 $thumbnail_id = $_POST['thumbnail_id'] ;	 
	 delete_post_meta($post_id,'_thumbnail_id',$thumbnail_id);	
}

// get the postid and imageid from the json and update the product's image using update_post_meta
add_action('wp_ajax_uploadProductImage', 'uploadProductImage');
function uploadProductImage(){
	 $post_id =  $_POST['post_id'] ;
	 $thumbnail_id = $_POST['thumbnail_id'] ;	 
	 update_post_meta($post_id,'_thumbnail_id',$thumbnail_id);	
}

?>
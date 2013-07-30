<?php

$options4 = array (

array (	"name" => "Upload Image",
"id" => $shortname."_edus_image1",
"std" => "",
"type" => "media-upload"),

array (	"name" => "Service headline 1",
"id" => $shortname."_edus_headline1",
"std" => "Service Headline 1",
"type" => "text"),

array (	"name" => "Service 1 link <em>*must have http://</em>",
"id" => $shortname."_edus_headline1_link",
"std" => "",
"type" => "text"),

array (	"name" => "Service short text 1",
"id" => $shortname."_edus_text1",
"std" => "",
"type" => "textarea")

);


$options5 = array (

array (	"name" => "Upload Image",
"id" => $shortname."_edus_image2",
"std" => "",
"type" => "media-upload"),

array (	"name" => "Service headline 2",
"id" => $shortname."_edus_headline2",
"std" => "Service Headline 2",
"type" => "text"),

array (	"name" => "Service 2 link <em>*must have http://</em>",
"id" => $shortname."_edus_headline2_link",
"std" => "",
"type" => "text"),

array (	"name" => "Service short text 2",
"id" => $shortname."_edus_text2",
"std" => "",
"type" => "textarea")

);


$options6 = array (

array (	"name" => "Upload Image",
"id" => $shortname."_edus_image3",
"std" => "",
"type" => "media-upload"),

array (	"name" => "Service headline 3",
"id" => $shortname."_edus_headline3",
"std" => "Service Headline 3",
"type" => "text"),

array (	"name" => "Service 3 link <em>*must have http://</em>",
"id" => $shortname."_edus_headline3_link",
"std" => "",
"type" => "text"),

array (	"name" => "Service short text 3",
"id" => $shortname."_edus_text3",
"std" => "",
"type" => "textarea")

);


$options7 = array (

array (	"name" => "Upload Image",
"id" => $shortname."_edus_image4",
"std" => "",
"type" => "media-upload"),

array (	"name" => "Service headline 4",
"id" => $shortname."_edus_headline4",
"std" => "Service Headline 4",
"type" => "text"),

array (	"name" => "Service 4 link <em>*must have http://</em>",
"id" => $shortname."_edus_headline4_link",
"std" => "",
"type" => "text"),


array (	"name" => "Service short text 4",
"id" => $shortname."_edus_text4",
"std" => "",
"type" => "textarea")

);

$options8 = array (

array (	"name" => "Upload Image",
"id" => $shortname."_edus_image5",
"std" => "",
"type" => "media-upload"),

array (	"name" => "Service headline 5",
"id" => $shortname."_edus_headline5",
"std" => "Service Headline 5",
"type" => "text"),

array (	"name" => "Service 5 link <em>*must have http://</em>",
"id" => $shortname."_edus_headline5_link",
"std" => "",
"type" => "text"),

array (	"name" => "Service short text 5",
"id" => $shortname."_edus_text5",
"std" => "",
"type" => "textarea")

);


$options9 = array (

array (	"name" => "Upload Image",
"id" => $shortname."_edus_image6",
"std" => "",
"type" => "media-upload"),

array (	"name" => "Service headline 6",
"id" => $shortname."_edus_headline6",
"std" => "Service Headline 6",
"type" => "text"),

array (	"name" => "Service 6 link <em>*must have http://</em>",
"id" => $shortname."_edus_headline6_link",
"std" => "",
"type" => "text"),

array (	"name" => "Service short text 6",
"id" => $shortname."_edus_text6",
"std" => "",
"type" => "textarea")

);



function edus_features_page() {
global $blog_id, $themename, $theme_version, $options4, $options5, $options6, $options7, $options8, $options9;

////////////////////////////////////////////
$uploads = wp_upload_dir();
$upload_files_path = get_option('upload_path');
$upload_url_trim = str_replace( WP_CONTENT_DIR, "", $uploads['basedir'] );
if (substr($upload_url_trim, -1) == '/') {
$upload_url_trim = rtrim($upload_url_trim, '/');
}
/////////////////////////////////////////////////////You can alter these options///////////////////////////
$tpl_url = get_site_url();
$ptp = get_template();
$uploads_folder = "thumb";
$upload_path = $uploads['basedir'] . '/' . $uploads_folder . "/";
$upload_path_check = $uploads['basedir'] . '/' . $uploads_folder;

$ttpl = get_template_directory_uri();
$ttpl_url = get_site_url();

$upload_url = WP_CONTENT_URL . $upload_url_trim  . '/' . $uploads_folder;
?>

<?php
	if ( isset($_REQUEST['resetall']) )
		echo '<div id="message" class="updated fade"><p><strong>All images deleted and settings reset</strong></p></div>';
	if ( isset($_REQUEST['saved']) )
		echo '<div id="message" class="updated fade"><p><strong>Settings saved</strong></p></div>';
?>

<div id="options-panel">
<div id="options-head"><h2><?php echo $themename; ?> <?php _e("Custom Homepage Options", TEMPLATE_DOMAIN); ?></h2>
<div class="theme-versions"><?php _e("Version",TEMPLATE_DOMAIN); ?> <?php echo $theme_version; ?></div>
</div>

<div id="sbtabs_uploads">

<form method="post">

	<?php
		edus_get_service( __("Featured images 1 Setting",TEMPLATE_DOMAIN), $options4, 'edu1', $upload_path, $upload_url );
		edus_get_service( __("Featured images 2 Setting",TEMPLATE_DOMAIN), $options5, 'edu2', $upload_path, $upload_url );
		edus_get_service( __("Featured images 3 Setting",TEMPLATE_DOMAIN), $options6, 'edu3', $upload_path, $upload_url );
		edus_get_service( __("Featured images 4 Setting",TEMPLATE_DOMAIN), $options7, 'edu4', $upload_path, $upload_url );
		edus_get_service( __("Featured images 5 Setting",TEMPLATE_DOMAIN), $options8, 'edu5', $upload_path, $upload_url );
		edus_get_service( __("Featured images 6 Setting",TEMPLATE_DOMAIN), $options9, 'edu6', $upload_path, $upload_url );
	?>
	<div class="tabc">
		<input name="save" type="submit" class="sbutton button-primary" value="Save All Settings" />
		<input type="hidden" name="action" value="saveall" />
	</div>
</form>


<div id="reset-box">
<form method="post">
<div class="submit">
<input name="reset" type="submit" class="sbutton button-secondary" onclick="return confirm('Are you sure you want to delete all images and reset all text options?. This action cannot be restore.')" value="Delete all images and reset all text options" />
<input type="hidden" name="action" value="resetall" />&nbsp;&nbsp;<?php _e("by pressing this reset button, all your uploaded services images and saved text settings will be deleted.",TEMPLATE_DOMAIN); ?>
</div>
</form>
</div>

</div>
</div>

<?php

}



function edus_get_service( $title, $options, $image_name, $upload_path, $upload_url ){

?>
	<div class="tabc">
	<?php
	/////////////////////////////////////////////////////You can alter these options/////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////////////////
	$normal_image_name = $image_name."_normal.jpg";
	$large_image_name = $image_name.'.jpg'; 		     // New name of the large image
	$thumb_image_name = $image_name.'_thumb.jpg'; 	// New name of the thumbnail image
	$max_file = "1000000"; 						        // Approx below 1MB
	$max_width = "800";							        // Max width allowed for the large image
	$thumb_width = "200";						        // Width of thumbnail image
	$thumb_height = "100";                              // Height of thumbnail image

	//Image Locations
	$normal_image_location = $upload_path. $normal_image_name;
	$large_image_location = $upload_path . $large_image_name;
	$thumb_image_location = $upload_path . $thumb_image_name;

	//Check to see if any images with the same names already exist
	if (file_exists($large_image_location)){
	if (file_exists($thumb_image_location)){
	$thumb_photo_exists = "<img src=\"".$upload_path.$thumb_image_name."\" alt=\"Thumbnail Image\"/>";
	} else {
	$thumb_photo_exists = "";
	}
	$large_photo_exists = "<img src=\"".$upload_path.$large_image_name."\" alt=\"Large Image\"/>";
	} else {
	$large_photo_exists = "";
	$thumb_photo_exists = "";
	}

	// normal photo check
	if (file_exists($normal_image_location)){
	$normal_photo_exists = "<img src=\"".$upload_path.$normal_image_name."\" alt=\"Large Image\"/>";
	} else {
	$normal_photo_exists = "";
	}

	?>


	<h4><?php echo $title; ?> - <small>suitable size for 'upload' only ( <?php echo $thumb_width . 'x' . $thumb_height; ?> )</small></h4>
	<div class="tab-option">
	<div class="option-save">


	<?php foreach ($options as $value) {   ?>
	<?php
	switch ( $value['type'] ) {
	case 'text':
	$valuex = stripslashes($value['id']);
	$text_code = get_option($valuex, $value['std']);
	?>

	<div class="description"><?php echo $value['name']; ?></div>
	<p><input name="<?php echo $value['id']; ?>" class="myfield" id="<?php echo $value['id']; ?>" type="<?php echo $value['type']; ?>" value="<?php echo stripslashes($text_code); ?>" />
	</p>

	<?php
	break;
	case 'textarea':
	?>

	<?php
	$valuex = $value['id'];
	$valuey = stripslashes($valuex);
	$video_code = get_option($valuey, $value['std']);
	?>
	<div class="description"><?php echo $value['name']; ?></div>
	<p><textarea name="<?php echo $valuey; ?>" class="mytext" cols="40%" rows="8" /><?php echo stripslashes($video_code); ?></textarea></p>

	<?php
	break;
	case 'media-upload':
	?>

	<?php
	$valuex = stripslashes($value['id']);
	$attachment_id = get_option($valuex);
	$thumbnail = '';
	if ( $attachment_id )
		$thumbnail = wp_get_attachment_image_src($attachment_id, 'service-thumbnail');
		if ( isset($thumbnail[3]) && $thumbnail[3] == false )
			$thumbnail = wp_get_attachment_image_src($attachment_id, 'post-thumbnail');
	?>

	<p>
	<?php if($attachment_id && $thumbnail) { ?>
		<img src="<?php echo $thumbnail[0]; ?>" class="timg"/>
	<?php } elseif(strlen($thumb_photo_exists)>0) { ?>
		<img src="<?php echo "$upload_url/$thumb_image_name"; ?>" class="timg"/>
	<?php } ?>
	</p>

	<input type="hidden" name="<?php echo $valuex ?>" id="<?php echo $valuex ?>" value="<?php echo $attachment_id; ?>" />

	<p>
		<button type="button" class="media-delete button" data-for="<?php echo $valuex ?>" <?php if ( !$attachment_id && !$thumb_photo_exists ){ ?>style="display: none;"<?php } ?>><?php _e('Delete Image', TEMPLATE_DOMAIN) ?></button>
		<button type="button" class="media-upload button" data-for="<?php echo $valuex ?>"><?php echo $value['name'] ?></button>
	</p>
	<?php
	break;
	default;
	?>



	<?php
	break;
	} ?>


	<?php } ?>

	</div>
	</div>

	</div><!-- end tabc -->
<?php
}

function edus_features_register() {
	global $themename, $shortname, $options4, $options5, $options6, $options7, $options8, $options9;

	if ( isset($_GET['page']) && $_GET['page'] == "custom-homepage.php" ) {
		if ( isset($_REQUEST['action']) && 'saveall' == $_REQUEST['action'] ) {
			foreach ($options4 as $value) {
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}
			foreach ($options5 as $value) {
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}
			foreach ($options6 as $value) {
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}
			foreach ($options7 as $value) {
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}
			foreach ($options8 as $value) {
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}
			foreach ($options9 as $value) {
				update_option( $value['id'], $_REQUEST[ $value['id'] ] );
			}
			wp_redirect("themes.php?page=custom-homepage.php&saved=true");
			die;
		}
	}

	if ( isset($_GET['page']) && $_GET['page'] == "custom-homepage.php" ) {
		if( isset($_REQUEST['action']) && 'resetall' == $_REQUEST['action'] ) {
			foreach ($options4 as $value){ delete_option( $value['id'] ); }
			foreach ($options5 as $value){ delete_option( $value['id'] ); }
			foreach ($options6 as $value){ delete_option( $value['id'] ); }
			foreach ($options7 as $value){ delete_option( $value['id'] ); }
			foreach ($options8 as $value){ delete_option( $value['id'] ); }
			foreach ($options9 as $value){ delete_option( $value['id'] ); }

			////////////////////////////////////////////
			$uploads = wp_upload_dir();
			$upload_files_path = get_option('upload_path');
			$upload_url_trim = str_replace( WP_CONTENT_DIR, "", $uploads['basedir'] );
			if (substr($upload_url_trim, -1) == '/') {
			$upload_url_trim = rtrim($upload_url_trim, '/');
			}
			/////////////////////////////////////////////////////You can alter these options///////////////////////////
			$tpl_url = get_site_url();
			$ptp = get_template();
			$uploads_folder = "thumb";
			$upload_path = $uploads['basedir'] . '/' . $uploads_folder . "/";
			$upload_path_check = $uploads['basedir'] . '/' . $uploads_folder;

			$ttpl = get_template_directory_uri();
			$ttpl_url = get_site_url();

			$upload_url = WP_CONTENT_URL . $upload_url_trim  . '/' . $uploads_folder;

			if(file_exists($upload_path . 'edu1.jpg')) {
			unlink("$upload_path_check/edu1.jpg");
			unlink("$upload_path_check/edu1_thumb.jpg");
			}

			if(file_exists($upload_path . 'edu2.jpg')) {
			unlink("$upload_path_check/edu2.jpg");
			unlink("$upload_path_check/edu2_thumb.jpg");
			}

			if(file_exists($upload_path . 'edu3.jpg')) {
			unlink("$upload_path_check/edu3.jpg");
			unlink("$upload_path_check/edu3_thumb.jpg");
			}

			if(file_exists($upload_path . 'edu4.jpg')) {
			unlink("$upload_path_check/edu4.jpg");
			unlink("$upload_path_check/edu4_thumb.jpg");
			}

			if(file_exists($upload_path . 'edu5.jpg')) {
			unlink("$upload_path_check/edu5.jpg");
			unlink("$upload_path_check/edu5_thumb.jpg");
			}

			if(file_exists($upload_path . 'edu6.jpg')) {
			unlink("$upload_path_check/edu6.jpg");
			unlink("$upload_path_check/edu6_thumb.jpg");
			}

			if(file_exists($upload_path . 'edu1_normal.jpg')) {
			unlink("$upload_path_check/edu1_normal.jpg");
			}

			if(file_exists($upload_path . 'edu2_normal.jpg')) {
			unlink("$upload_path_check/edu2_normal.jpg");
			}

			if(file_exists($upload_path . 'edu3_normal.jpg')) {
			unlink("$upload_path_check/edu3_normal.jpg");
			}

			if(file_exists($upload_path . 'edu4_normal.jpg')) {
			unlink("$upload_path_check/edu4_normal.jpg");
			}

			if(file_exists($upload_path . 'edu5_normal.jpg')) {
			unlink("$upload_path_check/edu5_normal.jpg");
			}

			if(file_exists($upload_path . 'edu6_normal.jpg')) {
			unlink("$upload_path_check/edu6_normal.jpg");
			}

			wp_redirect("themes.php?page=custom-homepage.php&resetall=true");
			die;
		}
	}

	add_theme_page(_g ('Services', TEMPLATE_DOMAIN),  _g ('Services Setting', TEMPLATE_DOMAIN),  'edit_theme_options', 'custom-homepage.php', 'edus_features_page');
}

add_action('admin_menu', 'edus_features_register');


function edus_features_enqueue(){
	if ( strpos(get_current_screen()->id, 'appearance_page_custom-homepage') !== false ){
		wp_enqueue_script('jquery');
		wp_enqueue_script('thickbox');
		wp_enqueue_style('thickbox');
		wp_enqueue_script('media-upload');
		wp_enqueue_script('edus-features-upload', get_template_directory_uri().'/_inc/js/features-upload.js');
	}
}
add_action('admin_enqueue_scripts', 'edus_features_enqueue');

function edus_admin_init() {
	global $pagenow;
	if ( 'media-upload.php' == $pagenow || 'async-upload.php' == $pagenow ) {
		add_filter( 'gettext', 'edus_replace_thickbox_text'  , 1, 3 );
	}
}
add_action( 'admin_init', 'edus_admin_init' );
function edus_replace_thickbox_text($translated_text, $text, $domain) {
	if ('Insert into Post' == $text) {
		$referer = strpos( wp_get_referer(), 'custom-homepage' );
		if ( $referer != '' ) {
			return __('Use this image', TEMPLATE_DOMAIN);
		}
	}
	return $translated_text;
}

?>
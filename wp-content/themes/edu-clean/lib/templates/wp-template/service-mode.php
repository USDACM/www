<?php include (TEMPLATEPATH . '/options.php'); ?>

<?php
function edus_display_service( $args ){
	global $upload_path, $upload_url;
?>
	<div class="service-block">
	<?php if ( '' == $args['image'] ): ?>
		<?php if ( file_exists($upload_path . $args['old_image'] . '_normal.jpg') ): ?>
			<a href="<?php echo $args['link']; ?>"><img src="<?php echo "$upload_url/".$args['old_image']."_normal.jpg"; ?>" width="200" height="100" alt="<?php echo stripslashes($args['headline']); ?>" /></a>
		<?php elseif( file_exists($upload_path . $args['old_image'] . '_thumb.jpg') ): ?>
			<a href="<?php echo $args['link']; ?>"><img src="<?php echo "$upload_url/".$args['old_image']."_thumb.jpg"; ?>" width="200" height="100" alt="<?php echo stripslashes($args['headline']); ?>" /></a>
		<?php else: ?>
			<img src="<?php echo get_template_directory_uri(); ?>/_inc/images/default.jpg" width="200" height="100" alt="img" />
		<?php endif; ?>
	<?php elseif ( $args['image'] > 0 && ( $image_src = wp_get_attachment_image_src($args['image'], 'service-thumbnail') ) ): ?>
		<?php $image_src = ( isset($image_src[3]) && $image_src[3] == false ) ? wp_get_attachment_image_src($args['image'], 'post-thumbnail') : $image_src; ?>
		<a href="<?php echo $args['link']; ?>"><img src="<?php echo $image_src[0]; ?>" width="<?php echo $image_src[1]; ?>" height="<?php echo $image_src[2]; ?>" alt="<?php echo stripslashes($args['headline']); ?>" /></a>
	<?php else: ?>
		<img src="<?php echo get_template_directory_uri(); ?>/_inc/images/default.jpg" width="200" height="100" alt="img" />
	<?php endif; ?>
	<h3><?php echo stripslashes($args['headline']); ?></h3>
	<p>
	<?php if($args['text'] == ""){ ?>
	<?php _e('You can replace this area with a new text in <a href="wp-admin/themes.php?page=custom-homepage.php">your theme options</a> and <a href="wp-admin/themes.php?page=custom-homepage.php">upload and crop new images</a> to replace the image you can see here already.', TEMPLATE_DOMAIN); ?>
	<?php } else { ?>
	<?php
	$com_short = $args['text'];
	$chars = 120;
	$com_short = $com_short . " ";
	$com_short = substr($com_short,0,$chars);
	$com_short = substr($com_short,0,strrpos($com_short,' '));
	$com_short = $com_short . "...";
	?>
	<?php
	if( function_exists('do_shortcode') ) {
	echo do_shortcode(stripslashes($com_short));
	} else {
	echo stripslashes($com_short);
	}
	?>
	<?php }  ?>
	</p>
	</div>

<?php
}

edus_display_service(array(
	'image' => $tn_edus_image1,
	'headline' => $tn_edus_headline1,
	'link' => $tn_edus_headline1_link,
	'text' => $tn_edus_text1,
	'old_image' => 'edu1'
));
edus_display_service(array(
	'image' => $tn_edus_image2,
	'headline' => $tn_edus_headline2,
	'link' => $tn_edus_headline2_link,
	'text' => $tn_edus_text2,
	'old_image' => 'edu2'
));
edus_display_service(array(
	'image' => $tn_edus_image3,
	'headline' => $tn_edus_headline3,
	'link' => $tn_edus_headline3_link,
	'text' => $tn_edus_text3,
	'old_image' => 'edu3'
));
edus_display_service(array(
	'image' => $tn_edus_image4,
	'headline' => $tn_edus_headline4,
	'link' => $tn_edus_headline4_link,
	'text' => $tn_edus_text4,
	'old_image' => 'edu4'
));
edus_display_service(array(
	'image' => $tn_edus_image5,
	'headline' => $tn_edus_headline5,
	'link' => $tn_edus_headline5_link,
	'text' => $tn_edus_text5,
	'old_image' => 'edu5'
));
edus_display_service(array(
	'image' => $tn_edus_image6,
	'headline' => $tn_edus_headline6,
	'link' => $tn_edus_headline6_link,
	'text' => $tn_edus_text6,
	'old_image' => 'edu6'
));

?>


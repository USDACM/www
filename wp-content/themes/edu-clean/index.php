<?php
/*
customization on index.php so static frontpage and post page can be use properly
*/

if ( '' == get_option( 'page_on_front' )  || '0' == get_option( 'page_on_front' ) ) : $post_set_page = get_option( 'page_for_posts' );

	if( is_home() && !is_page( $post_set_page )  ) {
		locate_template( array('index-home.php'), true );
	} else {
		locate_template( array('index-post.php'), true );
	}

else: //if static frontpage were set

	locate_template( array('index-post.php'), true );

endif;

?>
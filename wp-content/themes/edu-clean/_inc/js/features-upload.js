var upload_for;

jQuery(document).ready(function($) {
	$('.media-upload').click(function(e) {
		e.preventDefault();
		upload_for = $(this).data('for');
		tb_show('Upload image', 'media-upload.php?referer=custom-homepage&type=image&post_id=0&TB_iframe=true', false);
	});

	window.send_to_editor = function(html){
		var attachment_post_id=0;
		m = html.match(/wp-image-(\d+)/i);
		if (m != null) {
			attachment_post_id = m[1];
		}
		var image_url = $('img',html).attr('src');
		var id = '#'+upload_for;
		$(id).val(attachment_post_id);
		var parent = $(id).closest('.tab-option');
		var preview = parent.find('img.timg');
		if ( preview.size() > 0 )
			preview.attr('src', image_url);
		else
			$(id).before('<p><img src="'+image_url+'" class="timg" /></p>');
		parent.find('.media-delete').show();
		tb_remove();
	};
	
	$('.media-delete').click(function(e){
		e.preventDefault();
		$(this).hide();
		var parent = $(this).closest('.tab-option');
		parent.find('img.timg').parent('p').remove();
		var id = '#'+$(this).data('for');
		$(id).val(0);
	});
});


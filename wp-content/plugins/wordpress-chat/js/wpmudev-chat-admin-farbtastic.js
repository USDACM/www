// Used for the Farbtastic color picker used on chat settings panels.
(function ($) {
	jQuery(document).ready(function () {
		
		jQuery('input.pickcolor_input').each(function() {
			/* Older Farbtastic color picker */
			var color_val 		= jQuery(this).val();
			jQuery(this).css('background-color', color_val);
			
			var input_id		= jQuery(this).attr('id');
			var input_picker	= input_id + '-colorpicker';
			jQuery(this).after('<div id="'+input_picker+'" style="z-index: 100; background:#eee; border:1px solid #ccc; position:absolute; display:none;"></div>');
			jQuery('#'+input_picker).farbtastic('#'+input_id).hide();
			
			jQuery('#'+input_id).focus(function() {
				jQuery('#'+input_picker).slideDown();
			});

			jQuery('#'+input_id).blur(function() {
				jQuery('#'+input_picker).slideUp();
			});			
		});
	});
})(jQuery);

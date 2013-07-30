
jQuery(document).ready( function($){
	var settings = [
		// WordPress Custom Background 
		{
			setting: 'background_color',
			callback: function(to){
				theme_change_style('body', 'background-color', to, '!important');
			}
		},
		{
			setting: 'background_image',
			callback: function(to){
				theme_queue_style('body', 'background-image', 'url('+to+')', '!important');
				theme_queue_style('body', 'background-repeat', wp.customize('background_repeat').get(), '!important');
				theme_queue_style('body', 'background-position', 'top '+wp.customize('background_position_x').get(), '!important');
				theme_queue_style('body', 'background-attachment', wp.customize('background_attachment').get(), '!important');
				theme_update_css();
			}
		},
		{
			setting: 'background_repeat',
			callback: function(to){
				theme_change_style('body', 'background-repeat', to, '!important');
			}
		},
		{
			setting: 'background_position_x',
			callback: function(to){
				theme_change_style('body', 'background-position', 'top '+to, '!important');
			}
		},
		{
			setting: 'background_attachment',
			callback: function(to){
				theme_change_style('body', 'background-attachment', to, '!important');
			}
		},
		// CSS Options
		{
			setting: theme_prefix+'body_font',
			callback: function(to){
				theme_change_font_family('body', to, '!important');
			}
		},
		{
			setting: theme_prefix+'headline_font',
			callback: function(to){
				theme_change_font_family('h1, h2, h3, h4, h5, h6', to, '!important');
			}
		},
		{
			setting: theme_prefix+'font_size',
			callback: function(to){
				var size = '0.75em';
				if ( to == 'small' )
					size = '0.6875em';
				else if ( to == 'bigger' )
					size = '0.85em';
				else if ( to == 'largest' )
					size = '1em';
				theme_change_style('#custom', 'font-size', size, '');
			}
		},
		{
			setting: theme_prefix+'link_colour',
			callback: function(to){
				theme_change_style('#container a, #edublog-free p a', 'color', to, '!important');
			}
		},
		// BuddyPress options
		{
			setting: theme_prefix+'span_meta_color',
			callback: function(to){
				theme_queue_style('#custom .activity-list .activity-header a:first-child, #custom span.highlight', 'background', to, '!important');
				theme_queue_style('span.activity', 'background', to, '!important');
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'span_meta_border_color',
			callback: function(to){
				theme_queue_style('#custom .activity-list .activity-header a:first-child, #custom span.highlight', 'border-color', to, '!important');
				theme_queue_style('span.activity', 'border', '1px solid '+to, '!important');
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'span_meta_text_color',
			callback: function(to){
				theme_queue_style('#custom .activity-list .activity-header a:first-child, #custom span.highlight', 'color', to, '!important');
				theme_queue_style('span.activity', 'color', to, '!important');
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'span_meta_hover_color',
			callback: function(to){
				theme_change_style('#custom .activity-list .activity-header a:first-child:hover, #custom span.highlight:hover', 'background', to, '!important');
			}
		},
		{
			setting: theme_prefix+'span_meta_border_hover_color',
			callback: function(to){
				theme_change_style('#custom .activity-list .activity-header a:first-child:hover, #custom span.highlight:hover', 'border', '1px solid '+to, '!important');
			}
		},
		{
			setting: theme_prefix+'span_meta_text_hover_color',
			callback: function(to){
				theme_change_style('#custom .activity-list .activity-header a:first-child:hover, #custom span.highlight:hover', 'color', to, '!important');
			}
		},
		// Navigation options
		{
			setting: theme_prefix+'nav_bg_color',
			callback: function(to){
				theme_change_style('#nav li a, #home a', 'background', to, '!important');
			}
		},
		{
			setting: theme_prefix+'nav_text_color',
			callback: function(to){
				theme_change_style('#nav li a, #home a', 'color', to, '!important');
			}
		},
		{
			setting: theme_prefix+'nav_hover_bg_color',
			callback: function(to){
				theme_change_style('#nav ul li a, #nav li:hover a, #nav li a:hover, #nav li.selected a, #nav li.current_page_item a, #nav li.current_page_item a:hover', 'background', to, '!important');
			}
		},
		{
			setting: theme_prefix+'nav_hover_border_color',
			callback: function(to){
				theme_queue_style('#nav ul li a, #nav ul li a:hover', 'background', to, '!important');
				theme_queue_style('#nav ul li a, #nav ul li a:hover', 'border-bottom', '1px solid '+to, '!important');
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'nav_hover_text_color',
			callback: function(to){
				theme_change_style('#nav ul li a, #nav li:hover a, #nav li a:hover, #nav li.selected a, #nav li.current_page_item a, #nav li.current_page_item a:hover', 'color', to, '!important');
			}
		},
		// Top header options
		{
			setting: theme_prefix+'top_header_bg_colour',
			callback: function(to){
				theme_change_style('.top-header-wrap', 'background-color', to, '!important');
			}
		},
		{
			setting: theme_prefix+'top_header_bg_image',
			callback: function(to){
				theme_queue_style('.top-header-wrap', 'background-image', 'url("'+to+'")', '!important');
				theme_queue_style('.top-header-wrap', 'background-repeat', 'repeat-x', '!important')
				theme_queue_style('.top-header-wrap', 'background-position', 'left top', '!important');;
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'top_header_text_colour',
			callback: function(to){
				theme_change_style('.top-header-wrap', 'color', to, '!important');
			}
		},
		{
			setting: theme_prefix+'top_header_text_link_colour',
			callback: function(to){
				theme_change_style('.top-header-wrap h1 a', 'color', to, '!important');
			}
		},
		{
			setting: theme_prefix+'top_header_text_link_hover_colour',
			callback: function(to){
				theme_change_style('.top-header-wrap h1 a:hover', 'color', to, '!important');
			}
		},
		// Intro options
		{
			setting: theme_prefix+'pri_bg_colour',
			callback: function(to){
				theme_change_style('#main-header-content, #top-right-panel, #footer, ul.sidebar_list li h3, #post-navigator a', 'background', to, '!important');
			}
		},
		{
			setting: theme_prefix+'pri_bg_border_colour',
			callback: function(to){
				theme_queue_style('#main-header-content', 'border-bottom', '5px solid '+to, '!important');
				theme_queue_style('#top-right-panel, ul.sidebar_list li h3', 'border', '1px solid '+to, '!important');
				theme_queue_style('#footer', 'border-top', '1px solid '+to, '!important');
				theme_queue_style('input.inbox', 'border', '1px solid '+to, '!important');
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'pri_text_colour',
			callback: function(to){
				theme_queue_style('#main-header-content, #top-right-panel, #footer, ul.sidebar_list li h3, #post-navigator a', 'color', to, '!important');
				theme_queue_style('.footer a', 'color', to, '!important');
				theme_queue_style('#top-right-panel, #top-right-panel label, #top-right-panel p', 'color', to, '!important');
				theme_queue_style('#container #top-right-panel a, #container ul.sidebar_list h3 a', 'color', to, '!important');
				theme_queue_style('#container #top-right-panel a, #container ul.sidebar_list h3 a', 'font-weight', 'bold', '!important');
				theme_update_css();
			}
		},
		// Features options
		{
			setting: theme_prefix+'feat_header_title',
			callback: function(to){
				$('#services h4').text(to);
			}
		},
		{
			setting: theme_prefix+'tab_bg_colour',
			callback: function(to){
				theme_queue_style('#container .rss-feeds', 'background', to, '!important');
				theme_queue_style('#container ul.tabbernav li.tabberactive a', 'background', to, '!important');
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'tab_border_colour',
			callback: function(to){
				theme_queue_style('#container .rss-feeds', 'border-right', '1px solid '+to, '!important');
				theme_queue_style('#container .rss-feeds', 'border-bottom', '1px solid '+to, '!important');
				theme_queue_style('#container .rss-feeds', 'border-left', '1px solid '+to, '!important');
				theme_queue_style('#container .feed-pull', 'border-bottom', '1px solid '+to, '!important');
				theme_queue_style('#container ul.tabbernav li.tabberactive a', 'border-top', '1px solid '+to, '!important');
				theme_queue_style('#container ul.tabbernav li.tabberactive a', 'border-right', '1px solid '+to, '!important');
				theme_queue_style('#container ul.tabbernav li.tabberactive a', 'border-left', '1px solid '+to, '!important');
				theme_queue_style('#container ul.tabbernav li a', 'border-top', '1px solid '+to, '!important');
				theme_queue_style('#container ul.tabbernav li a', 'border-right', '1px solid '+to, '!important');
				theme_queue_style('#container ul.tabbernav li a', 'border-left', '1px solid '+to, '!important');
				theme_update_css();
			}
		},
		{
			setting: theme_prefix+'tab_text_colour',
			callback: function(to){
				theme_change_style('#container .rss-feeds', 'color', to, '!important');
			}
		},
		{
			setting: theme_prefix+'tab_link_colour',
			callback: function(to){
				theme_queue_style('#container .rss-feeds a', 'color', to, '!important');
				theme_queue_style('#container ul.tabbernav li.tabberactive a', 'color', to, '!important');
				theme_update_css();
			}
		}
	];
	theme_bind_customize( settings );
	
} );
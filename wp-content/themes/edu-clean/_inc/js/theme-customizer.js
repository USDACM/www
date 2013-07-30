var theme_customizer_css = [];

/**
 * Update CSS (causes repaint) 
 */
function theme_update_css(){
	var css = '';
	for ( s in theme_customizer_css ){
		css += theme_customizer_css[s].selector + '{';
		for ( p in theme_customizer_css[s].properties ){
			var property = theme_customizer_css[s].properties[p];
			for ( v in property ){
				if ( v == 0 || v == 1 || typeof property[v] != 'string' ) continue;
				css += property[0] + ':' + property[v] + property[1] + ';';
			}
		}
		css += '}';
	}
	jQuery('#theme-customizer-css').html('<style type="text/css">'+css+'</style>');
}

/**
 * Queue a style change 
 */
function theme_queue_style( selector_list, property, values, priority ){
	if ( !priority ) priority = '';
	var prop = [property, priority];
	if ( typeof values == 'string' ) prop.push(values);
	else {
		for ( v in values ) prop.push(values[v]);
	}
	var add_selector = true, add_property = true;
	for ( s in theme_customizer_css ){
		if ( theme_customizer_css[s].selector == selector_list ){
			add_selector = false;
			for ( p in theme_customizer_css[s].properties ){
				if ( theme_customizer_css[s].properties[p][0] == property ){
					theme_customizer_css[s].properties[p] = prop;
					add_property = false;
					break;
				}
			}
			if ( add_property ) theme_customizer_css[s].properties.push(prop)
		}
	}
	if ( add_selector ){
		theme_customizer_css.push({
			selector: selector_list,
			properties: [prop]
		});
	}
}

/**
 * Queue a style change and update it, suitable if you are only changing one, otherwise use queue
 */
function theme_change_style( selector_list, property, values, priority ){
	theme_queue_style(selector_list, property, values, priority);
	theme_update_css();
}

/**
 * Queue a font family change 
 */
function theme_queue_font_family( selector, value, priority ){
	// load font from Google Fonts API
	var fonts = value.split(',');
	var font = fonts[0];
	var supported_fonts = ["Cantarell", "Cardo", "Crimson Text", "Droid Sans", "Droid Serif", "IM Fell DW Pica",
		"Josefin Sans Std Light", "Lobster", "Molengo", "Neuton", "Nobile", "OFL Sorts Mill Goudy TT", 
		"Reenie Beanie", "Tangerine", "Old Standard TT", "Volkorn", "Yanone Kaffessatz", "Just Another Hand", 
		"Terminal Dosis Light", "Ubuntu"];
	var load_external = false;
	for ( i in supported_fonts ){
		if ( font == supported_fonts[i] ){
			load_external = true;
			break;
		}
	}
	if ( load_external ){
		if ( font == 'Ubuntu' ) font += ":light,regular,bold";
		font = font.replace(' ', '+');
		jQuery('body').append("<link href='http://fonts.googleapis.com/css?family="+font+"' rel='stylesheet' type='text/css'/>");
	}
	theme_queue_style(selector, 'font-family', value, priority);
}

/**
 * Queue a font family change and update it, suitable if you are only changing one, otherwise use queue
 */
function theme_change_font_family( selector, value, priority ){
	theme_queue_font_family(selector, value, priority);
	theme_update_css();
}

/**
 * Queue a background gradient change 
 */
function theme_queue_bg_gradient( selector, main_color, secondary_color, priority ) {
	var bg_values = [main_color];
	bg_values.push('-moz-linear-gradient(top, ' + main_color + ' 0%, ' + secondary_color + ' 100%)');
	bg_values.push('-webkit-gradient(linear, left top, left bottom, color-stop(0%,' + main_color + '), color-stop(100%,' + secondary_color + '))');
	bg_values.push('-webkit-linear-gradient(top, ' + main_color + ' 0%, ' + secondary_color + ' 100%)');
	bg_values.push('-o-linear-gradient(top, ' + main_color + ' 0%, ' + secondary_color + ' 100%)');
	bg_values.push('-ms-linear-gradient(top, ' + main_color + ' 0%, ' + secondary_color + ' 100%)');
	bg_values.push('linear-gradient(top, ' + main_color + ' 0%, ' + secondary_color + ' 100%)');
	theme_queue_style(selector, 'background', bg_values, priority);
	theme_queue_style(selector, 'filter', 'progid:DXImageTransform.Microsoft.gradient( startColorstr="' + main_color + '", endColorstr="' + secondary_color + '",GradientType=0)', priority);
}

/**
 * Queue a background gradient change and update it, suitable if you are only changing one, otherwise use queue
 */
function theme_change_bg_gradient( selector, main_color, secondary_color, priority ) {
	theme_queue_bg_gradient(selector, main_color, secondary_color, priority);
	theme_update_css();
}

function theme_color_creator(color, per){
	color = color.toString().substring(1);
	rgb = '';
	per = per/100*255;
	if  (per < 0 ){
        per =  Math.abs(per);
        for (x=0;x<=4;x+=2)
        {
        	c = parseInt(color.substring(x, x+2), 16) - per;
        	c = Math.floor(c);
            c = (c < 0) ? "0" : c.toString(16);
            rgb += (c.length < 2) ? '0'+c : c;
        }
    }
    else{
        for (x=0;x<=4;x+=2)
        {
        	c = parseInt(color.substring(x, x+2), 16) + per;
        	c = Math.floor(c);
            c = (c > 255) ? 'ff' : c.toString(16);
            rgb += (c.length < 2) ? '0'+c : c;
        }
    }
    return '#'+rgb;
}

/**
 * Get an hex color by offset from original color
 * 
 * h, s, l must be in the set [0, 1]
 * 
 * @param	String	hex		The original hex color
 * @param	Number	h		The offset from hue
 * @param	Number 	s		The offset from saturation
 * @param	Number	l		The offset from lightness
 * @return	String			The offset hex color
 */
function theme_color_hsl_offset ( hex, h, s, l ) {
	/**
	 * Converts an RGB color value to HSL. Conversion formula
	 * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
	 * Assumes r, g, and b are contained in the set [0, 255] and
	 * returns h, s, and l in the set [0, 1].
	 * 
	 * http://mjijackson.com/2008/02/rgb-to-hsl-and-rgb-to-hsv-color-model-conversion-algorithms-in-javascript
	 *
	 * @param   Number  r       The red color value
	 * @param   Number  g       The green color value
	 * @param   Number  b       The blue color value
	 * @return  Array           The HSL representation
	 */
	function rgbToHsl(r, g, b){
	    r /= 255, g /= 255, b /= 255;
	    var max = Math.max(r, g, b), min = Math.min(r, g, b);
	    var h, s, l = (max + min) / 2;
	
	    if(max == min){
	        h = s = 0; // achromatic
	    }else{
	        var d = max - min;
	        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
	        switch(max){
	            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
	            case g: h = (b - r) / d + 2; break;
	            case b: h = (r - g) / d + 4; break;
	        }
	        h /= 6;
	    }
	
	    return [h, s, l];
	}
	
	/**
	 * Converts an HSL color value to RGB. Conversion formula
	 * adapted from http://en.wikipedia.org/wiki/HSL_color_space.
	 * Assumes h, s, and l are contained in the set [0, 1] and
	 * returns r, g, and b in the set [0, 255].
	 * 
	 * http://mjijackson.com/2008/02/rgb-to-hsl-and-rgb-to-hsv-color-model-conversion-algorithms-in-javascript
	 *
	 * @param   Number  h       The hue
	 * @param   Number  s       The saturation
	 * @param   Number  l       The lightness
	 * @return  Array           The RGB representation
	 */
	function hslToRgb(h, s, l){
	    var r, g, b;
	
	    if(s == 0){
	        r = g = b = l; // achromatic
	    }else{
	        function hue2rgb(p, q, t){
	            if(t < 0) t += 1;
	            if(t > 1) t -= 1;
	            if(t < 1/6) return p + (q - p) * 6 * t;
	            if(t < 1/2) return q;
	            if(t < 2/3) return p + (q - p) * (2/3 - t) * 6;
	            return p;
	        }
	
	        var q = l < 0.5 ? l * (1 + s) : l + s - l * s;
	        var p = 2 * l - q;
	        r = hue2rgb(p, q, h + 1/3);
	        g = hue2rgb(p, q, h);
	        b = hue2rgb(p, q, h - 1/3);
	    }
	
	    return [r * 255, g * 255, b * 255];
	}
	var hex_string = hex.toString().substring(1);
	var rgb = [
		parseInt(hex_string.substring(0, 2), 16), 
		parseInt(hex_string.substring(2, 4), 16), 
		parseInt(hex_string.substring(4, 6), 16)
	];
	var hsl = rgbToHsl( rgb[0], rgb[1], rgb[2] );
	hsl[0] = Math.round(hsl[0]*100)/100;
	hsl[1] = Math.round(hsl[1]*100)/100;
	hsl[2] = Math.round(hsl[2]*100)/100;
	var hsl_off = [ 
		( hsl[0]+h > 1 ? 1 : ( hsl[0]+h < 0 ? 0 : hsl[0]+h ) ), 
		( hsl[1]+s > 1 ? 1 : ( hsl[1]+s < 0 ? 0 : hsl[1]+s ) ), 
		( hsl[2]+l > 1 ? 1 : ( hsl[2]+l < 0 ? 0 : hsl[2]+l ) )
	];
	var rgb_off = hslToRgb( hsl_off[0], hsl_off[1], hsl_off[2] );
	var hex_off = '';
    for ( h=0; h<3; h++ ) {
    	var c = Math.floor(parseInt(rgb_off[h])).toString(16);
    	c = ( c.length < 2 ) ? '0'+c : c;
    	hex_off += c;
    }
    return '#' + hex_off;
}

/**
 * Bind all customize value change at once
 * 
 * @param {Array} settings
 */
function theme_bind_customize( settings ) {
	for ( var i = 0; i < settings.length; i++ ){
		wp.customize( settings[i].setting, function(value){
			value.bind( settings[i].callback );
		} );
	}
}


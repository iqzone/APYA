/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.slideshow.js - Gallery slideshow code	*/
/* (c) IPS, Inc 2009							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _lb = window.IPBoard;

_lb.prototype.gallery_lightbox = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.gallery_lightbox.js");		
	},
	
	/**
	 * L(a)unch
	 */
	launch: function()
	{
		/* Lets get this party started */
		$('ips_lightbox').show();
		
		if ( $('ips_lightbox').readAttribute('setup') == 'false' )
		{
			var dims  = $('ips_lightbox').readAttribute( 'dimensions' ).split('-');
			
			var img   = new Element('img').writeAttribute( 'src', $('ips_lightbox').readAttribute('fullimage') ).addClassName('lightbox_image');
			var div   = new Element('div').update( $('ips_lightbox').readAttribute('caption') ).addClassName( 'lightbox_caption' );
			var close = new Element('div', { id: 'close_lightbox', style: 'cursor: pointer' } );
			
			if ( dims[0] && dims[1] )
			{
				//img.setStyle( 'max-width:' + parseInt( dims[0] ) + 'px; max-height: ' + parseInt( dims[1] ) + 'px' );
			}
			
			$('ips_lightbox').setStyle( { cursor: 'pointer' } );
			$('ips_lightbox').insert( close );
			$('ips_lightbox').insert( img );
			$('ips_lightbox').insert( div );
			$('ips_lightbox').observe( 'click', function(e) { $('ips_lightbox').hide(); } );
			
			$('ips_lightbox').writeAttribute('setup', 'true');
		}
		
	}

};

ipb.gallery_lightbox.init();
/**************************************************/
/* IPB3 Javascript								  */
/* --------------------------------------------   */
/* ips.gallery_photostrip.js - Gallery javascript */
/* (c) IPS, Inc 2008							  */
/* --------------------------------------------   */
/* Author: Matt Mecham							  */
/**************************************************/

var _photostrip = window.IPBoard;

_photostrip.prototype.photostrip = {
	
	_photoData: {},
	_total: 0, 
	_boxes: 0,
	_width: 61,
	_current: { 'left' : false, 'right': false, 'rightHit': false, 'leftHit': false },
	_seen: new Hash(),
	_stripless: false,
	_stopLeft: false,
	_stopRight: false,
	_clicked: false,
	_size: 100,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.gallery_photostrip.js");
		
		document.observe("dom:loaded", function()
		{
			ipb.photostrip._stripless  = ipb.vars['img_url'] + '/gallery/slider/stripless.png';
			ipb.photostrip._stopLeft   = ipb.vars['img_url'] + '/gallery/slider/stopLeft.png';
			ipb.photostrip._stopRight  = ipb.vars['img_url'] + '/gallery/slider/stopRight.png';
		});
	},
	
	/**
	 * Manually set photo data
	 *
	 */
	setPhotoData: function( json )
	{
		// { total: xx, photos { } }
		ipb.photostrip._photoData = json;
	},
	
	/**
	 * main display function
	 */
	display: function()
	{
		if ( $H( ipb.photostrip._photoData ) )
		{
			var _offset = $('photostripwrap').cumulativeOffset();
			var _dims   = $('photostripwrap').getDimensions();
		
			/* Set total */
			ipb.photostrip._total = ipb.photostrip._photoData['total'];
			
			/* Create boxes*/
			for( i = -2 ; i <= 2 ; i++ )
			{
				/* insert blanks */
				$('strip').insert( new Element( 'li', { 'id': 'strip_li_' + i } ).insert( new Element( 'img', { 'src': ipb.photostrip._stripless, 'class': 'galattach', 'width': '100px', 'height': '100px' } ) ) );
				$('strip_li_' + i ).addClassName( '__li' ).setStyle( { 'left': ( ipb.photostrip._width * parseInt( i ) ) + 'px' } );
				$('strip_li_' + i ).imageId = 0;
			}
			
			/* Set box count */
			ipb.photostrip._boxes = 5;
			
			$('strip').setStyle( { 'width': ( 122 * ipb.photostrip._boxes ) + 'px' } );
			
			$H( ipb.photostrip._photoData['photos'] ).each( function(data)
			{
				var key   = data.key;
				var image = data.value;
				
				if ( ! Object.isFunction( image ) )
				{
					Debug.write( 'here' + key );
					$('strip_li_' + key ).update( image['thumb'] );
					$('strip_li_' + key ).imageId = image['id'];
				}
			} );
			
			/* Set up seen */
			ipb.photostrip._seen['l-2,r2'] = true;
			
			/* Check edges */
			ipb.photostrip._current['left']  = -2;
			ipb.photostrip._current['right'] = 2;
			
			ipb.photostrip._checkEdges('left');
			ipb.photostrip._checkEdges('right');
			
			ipb.photostrip._showSliders();
			
			/* When window is resized, replace sliders in correct location */
			Event.observe( window, 'resize', ipb.photostrip._showSliders );
		}
	},
	
	/**
	 * Show the sliders
	 *
	 */
	_showSliders: function( e )
	{
		if ( ! $('slide_left') )
		{
			nleft  = new Element('div', { 'class': 'photoStipNav nleft', 'id': 'slide_left', 'style': 'display:none' } );
			nright = new Element('div', { 'class': 'photoStipNav nright', 'id': 'slide_right', 'style': 'display:none' } );
			body   = $$('body').first();
			
			body.insert( nleft );
			body.insert( nright );
			
			$('slide_left').observe( 'click', ipb.photostrip.slide.bindAsEventListener( this, 'left' ) );
			$('slide_right').observe( 'click', ipb.photostrip.slide.bindAsEventListener( this, 'right' ) );
		}
	
		var _offset = $('photostripwrap').cumulativeOffset();
		var _dims   = $('photostripwrap').getDimensions();
		
		$('slide_left').setStyle( { 'top'    : ( _offset['top'] + 5 ) + 'px',
									'left'   : ( _offset['left'] - 7 ) + 'px',
									'display': 'block',
									'position': 'absolute' } );
	
	
						
		$('slide_right').setStyle( { 'top'    : ( _offset['top'] + 5 ) + 'px',
									 'left'   : ( ( _offset['left'] + _dims['width'] - 5 )  ) + 'px',
									 'display': 'block',
									 'position': 'absolute' } );
		
		if ( ipb.photostrip._current['leftHit'] !== false )
		{
			$('slide_left').hide();
		}
		
		if ( ipb.photostrip._current['rightHit'] !== false )
		{
			$('slide_right').hide();
		}
	},
	
	/**
	 * Show the sliders
	 *
	 */
	_hideSliders: function( e )
	{
		elem = Event.findElement(e);
		
		/* only hide if we're outside the hot zone */
		if ( elem.hasClassName('photoStipNav') )
		{
			return true;
		}
		 
		$('slide_left').hide();
		$('slide_right').hide();
	},
	
	/**
	 * Fetch current visible left image
	 * UL.margin-left + LI.left = 0
	 *
	 */
	fetchLeft: function()
	{
		if ( ipb.photostrip._current['left'] === false )
		{
			marginLeft = parseInt( $('strip').getStyle( 'margin-left' ) );
		
			/* Go through all ell eyes */
			$$('.__li').each( function( i )
			{ 
				id = i.id.replace( /strip_li_/, '' );
				
				if ( ipb.photostrip._current['left'] === false && $(i.id) && ( parseInt( $(i.id).getStyle('left') ) + marginLeft == 0 ) )
				{ 
					ipb.photostrip._current['left'] = id;
				}
			} );
		}
		
		return ipb.photostrip._current['left'];
	},
	
	/**
	 * Fetch current visible left right
	 * current.left + 4
	 *
	 */
	fetchRight: function()
	{
		if ( ipb.photostrip._current['right'] === false )
		{
			ipb.photostrip._current['right'] = parseInt( ipb.photostrip.fetchLeft() ) + 4;
		}
		
		return ipb.photostrip._current['right'];
	},
	
	/**
	 * Clear current cached data
	 *
	 */
	clearCurrent: function()
	{
		ipb.photostrip._current['left']  = false;
		ipb.photostrip._current['right'] = false;
	},
	
	/**
	 * Fetch element of left or right
	 *
	 */
	fetchElement: function( id )
	{
		if ( $('strip_li_' + id ) )
		{
			return $('strip_li_' + id );
		}
		else
		{
			return false;
		}
	},
	
	/**
	 * Generic album select pop-up
	 *
	 */
	slide: function( e, direction )
	{
		Event.stop(e);
		direction = ( direction == 'left' ) ? 'left' : 'right';
		
		/* Stop multiple clicks */
		if ( ipb.photostrip._clicked === true )
		{
			return;
		}
		
		ipb.photostrip._clicked = true;
		
		Debug.write( "A jump to the " + direction );
		
		/* Current */
		left       = ipb.photostrip.fetchLeft();
		right      = ipb.photostrip.fetchRight();
		marginLeft = parseInt( $('strip').getStyle( 'margin-left' ) );
		
		/* After */
		if ( direction == 'left' )
		{
			afterRight = left;
			afterLeft  = parseInt( afterRight ) - 4;
		}
		else
		{
			afterLeft  = ipb.photostrip._current['right'];
			afterRight = parseInt( afterLeft ) + 4;
		}
		
		Debug.write( "Before: left: " + left + ', right: ' + right );
		Debug.write( "After: left: " + afterLeft + ', right: ' + afterRight );
		
		/* Already hit our limits? */
		if ( ipb.photostrip._current[ direction + 'Hit' ] === true )
		{
			return false;
		}
		
		/* Reset */
		if ( direction == 'left' )
		{
			/* Reset right */
			ipb.photostrip._current['rightHit'] = false;
			$('slide_right').show();
		}
		else
		{
			/* Reset right */
			ipb.photostrip._current['leftHit'] = false;
			$('slide_left').show();
		}
		
		/* Been here before? */
		if ( ! Object.isUndefined( ipb.photostrip._seen[ 'l' + afterLeft.toString() + ',r' + afterRight.toString() ] ) )
		{
			ipb.photostrip._slideIt( marginLeft, direction );
			
			Debug.write( "Cached, just sliding" );
			
			/* Update edges */
			ipb.photostrip._current['left']  = afterLeft;
			ipb.photostrip._current['right'] = afterRight;
			
			/* Update hash */
			ipb.photostrip._seen[ 'l' + afterLeft.toString() + ',r' + afterRight.toString() ] = true;
			
			/* Check edges */
			ipb.photostrip._checkEdges( direction );
			
			setTimeout( "ipb.photostrip._resetClicked()", 700 );
					
			/* DONE */
			return;
		}
		
		if ( direction == 'right' )
		{
			/* Add in more boxes to the right */
			for( var c = 0 ; c <= 3 ; c++ )
			{
				i = right + c + 1;
				
				/* insert blanks */
				if ( ! $('strip_li_' + i ) )
				{
					$('strip').insert( new Element( 'li', { 'id': 'strip_li_' + i } ).insert( new Element( 'img', { 'src': ipb.photostrip._stripless, 'class': 'galattach', 'width': '100px', 'height': '100px' } ) ) );
					$('strip_li_' + i ).addClassName( '__li' ).setStyle( { 'left': ( ipb.photostrip._width * parseInt( i ) ) + 'px' } );
					$('strip_li_' + i ).imageId = 0;
				}
			}
			
			/* Set pos */
			dirPos = right;
			
			/* Shift */
			newLeft = marginLeft -= ( ipb.photostrip._width * 4 );
			
			new Effect.Morph( 'strip', { style: 'margin-left:' + newLeft + 'px;', 'duration': 0.6 } );
			
			setTimeout( "ipb.photostrip._resetClicked()", 700 );
		}
		
		else if ( direction == 'left' )
		{
			/* Add in more boxes to the left */
			for( var i = left - 1 ; i >= left - 5 ; i-- )
			{
				/* insert blanks */
				if ( ! $('strip_li_' + i ) )
				{
					$('strip').insert( new Element( 'li', { 'id': 'strip_li_' + i } ).insert( new Element( 'img', { 'src': ipb.photostrip._stripless, 'class': 'galattach', 'width': '100px', 'height': '100px' } ) ) );
					$('strip_li_' + i ).addClassName( '__li' ).setStyle( { 'left': ( ipb.photostrip._width * parseInt( i ) ) + 'px' } );
					$('strip_li_' + i ).imageId = 0;
				}
			}
			
			/* Set pos */
			dirPos = left;
			
			/* Shift */
			newLeft = marginLeft += ( ipb.photostrip._width * 4 );
			Debug.write( newLeft );
			
			new Effect.Morph( 'strip', { style: 'margin-left:' + newLeft + 'px;', 'duration': 0.6 } );
			
			setTimeout( "ipb.photostrip._resetClicked()", 700 );
		}
		
		/* Set box count */
		ipb.photostrip._boxes += 4;
		
		/* adjust widths */
		$('strip').setStyle( { 'width': ( 122 * ipb.photostrip._boxes ) + 'px' } );
		
		url = ipb.vars['base_url']+'app=gallery&module=ajax&section=photostrip&do=slide&secure_key=' + ipb.vars['secure_hash'] + '&directionPos=' + dirPos + '&direction=' + direction + '&left=' + ipb.photostrip.fetchElement( left ).imageId + '&right=' + ipb.photostrip.fetchElement( right ).imageId;
		Debug.write( url );
		
		/* Fetch JASON */
		new Ajax.Request( url,
		{
			method: 'post',
			evalJSON: 'force',
			onSuccess: function(t)
			{
				if( Object.isUndefined( t.responseJSON ) ){ alert( ipb.lang['action_failed'] ); return; }
				
				ipb.photostrip._photoData['photos'] = t.responseJSON['photos'];
				
				if ( $H( ipb.photostrip._photoData['photos'] ) )
				{
					$H( ipb.photostrip._photoData['photos'] ).each( function(data)
					{
						var key   = data.key;
						var image = data.value;
						
						try
						{
							if ( ! Object.isFunction( image ) && $('strip_li_' + key ) )
							{
								$('strip_li_' + key ).update( image['thumb'] );
								$('strip_li_' + key ).imageId = image['id'];
							}
						}
						catch(e) { Debug.dir( e ); }
					} );
					
					/* Update edges */
					ipb.photostrip._current['left']  = afterLeft;
					ipb.photostrip._current['right'] = afterRight;
					
					/* Update hash */
					ipb.photostrip._seen[ 'l' + afterLeft.toString() + ',r' + afterRight.toString() ] = true;
					
					/* Check edges */
					ipb.photostrip._checkEdges( direction );
				}
			}
		});
	},
	
	/**
	 * reset clicked value
	 *
	 */
	_resetClicked: function()
	{
		ipb.photostrip._clicked = false;
	},
	
	/**
	 * Slide the slider
	 */
	_slideIt: function( marginLeft, direction )
	{
		/* Just cha-cha-slide */
		if ( direction == 'right' )
		{
			newLeft = marginLeft -= ( ipb.photostrip._width * 4 );
		}
		else
		{
			newLeft = marginLeft += ( ipb.photostrip._width * 4 );
		}
		
		new Effect.Morph( 'strip', { style: 'margin-left:' + newLeft + 'px;', 'duration': 0.6 } );
	},
	
	/**
	 * Check edges
	 */
	_checkEdges: function( direction )
	{
		/* Reset edges */
		if ( direction == 'left' )
		{
			/* Test for limits */
			try
			{
				if ( ipb.photostrip.fetchElement( ipb.photostrip._current['left'] ).imageId == 0 )
				{
					ipb.photostrip._current['leftHit'] = true;
					$('slide_left').hide();
					
					/* Start from the right and find the first non image */
					for( i = ipb.photostrip._current['right'] ; i >= (ipb.photostrip._current['right'] - 5 ) ; i-- )
					{
						obj = ipb.photostrip.fetchElement( i );
						
						if ( obj !== false && obj.imageId == 0 )
						{
							obj.down('.galattach').src    = ipb.photostrip._stopLeft;
							obj.down('.galattach').width  = '100';
							obj.down('.galattach').height = '100';
							break;
						}
					}
					
					Debug.write( "Left hit" );
				}
			} catch( e ) { }
			
		}
		else
		{
			/* Test for limits */
			try
			{
				if ( ipb.photostrip.fetchElement( ipb.photostrip._current['right'] ).imageId == 0 )
				{
					ipb.photostrip._current['rightHit'] = true;
					$('slide_right').hide();
					
					/* Start from the left and find the first non image */
					for( i = ipb.photostrip._current['left'] ; i <= (ipb.photostrip._current['left'] + 5 ) ; i++ )
					{
						obj = ipb.photostrip.fetchElement( i );
						
						if ( obj !== false && obj.imageId == 0 )
						{
							obj.down('.galattach').src    = ipb.photostrip._stopRight;
							obj.down('.galattach').width  = '100';
							obj.down('.galattach').height = '100';
							break;
						}
					}
					
					Debug.write( "Right hit" );
				}
			} catch( e ) { }
		}
	}
};

ipb.photostrip.init();
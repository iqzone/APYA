/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.slideshow.js - Gallery slideshow code	*/
/* (c) IPS, Inc 2009							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _ss = window.IPBoard;

_ss.prototype.slideshow = {
	
	/* Current ID */
	currentImage: 0,
	/* Paused? */
	paused: false,
	/* Length of fade transition */
	transitionLen: 0.6,
	/* Time between images */
	speed: 4,
	/* Size of the window */
	holderSize: [],
	/* Size of the thumbnails */
	thumbSize: 0,
	/* Have we stopped auto-scrolling the thumbbar for now? */
	stopAutoScroll: false,
	/* Timer for autoscroll */
	autoScrollTimer: null,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.slideshow.js");
		
		document.observe("dom:loaded", function(){
			
			// Set up button events
			$( 'c_PREV' ).observe('click', ipb.slideshow.buttonPrevious);
			$( 'c_PAUSE_PLAY' ).observe('click', ipb.slideshow.buttonPause);
			$( 'c_NEXT' ).observe('click', ipb.slideshow.buttonNext);
			$( 'button_left' ).observe('click', ipb.slideshow.thumbPrev);
			$( 'button_right' ).observe('click', ipb.slideshow.thumbNext);
			
			
			// Preload first image
			var firstImage = IMAGES.first();			
			ipb.slideshow.loadImage( firstImage, IMAGE_DATA.get( firstImage )['filename'] );
			
			// While that's going, get holder size
			ipb.slideshow.holderSize = $('image_holder').getDimensions();
			Event.observe( window, 'resize', ipb.slideshow.resizeWindow );
			
			// Build thumbnails
			ipb.slideshow.buildThumbnails();
			ipb.delegate.register('.thumb', ipb.slideshow.thumbnailClick);
			
			// Get width of thumbnail wrapper
			Debug.write( "Viewport width: " + ipb.slideshow.holderSize['width'] );
			Debug.write( "Thumbnail wrapper: " + $('thumbnail_wrap').getDimensions()['width'] );
			
			// Kick the slideshow off
			Debug.write("Starting slideshow...");
			ipb.slideshow.play();
			
		});
	},
	
	/*------------------------------*/
	/* scrolls the thumb bar left 	*/
	thumbPrev: function(e)
	{
		if( !$('button_left').hasClassName('active') || $('button_left').hasClassName('disabled') ){
			return;
		}
		
		var barLeft = parseInt( $('thumbnail_wrap').getStyle('left') );
		var moveX = ipb.slideshow.thumbSize;
		
		if( ( barLeft + ipb.slideshow.thumbSize ) > 30 ){
			moveX = barLeft - 30;
		}
		 		
		new Effect.Move( $('thumbnail_wrap'), { x: moveX, y: 0, duration: 0.2, mode: 'relative', afterFinish: function(){ ipb.slideshow.recalculateThumbnailButtons(); } } );
		
		// Wait for more user input
		ipb.slideshow.stopAutoScroll = true;		
		clearTimeout( ipb.slideshow.autoScrollTimer );
		ipb.slideshow.autoScrollTimer = setTimeout( ipb.slideshow.clearAutoScrollWait, 8000 );				
	},
	
	/*------------------------------*/
	/* scrolls the thumb bar right	*/
	thumbNext: function(e)
	{
		if( !$('button_right').hasClassName('active') || $('button_right').hasClassName('disabled') ){
			return;
		} 
		
		new Effect.Move( $('thumbnail_wrap'), { x: ( -1 * ipb.slideshow.thumbSize ), y: 0, duration: 0.2, mode: 'relative', afterFinish: function(){ ipb.slideshow.recalculateThumbnailButtons(); } } );

		// Wait for more user input
		ipb.slideshow.stopAutoScroll = true;		
		clearTimeout( ipb.slideshow.autoScrollTimer );
		ipb.slideshow.autoScrollTimer = setTimeout( ipb.slideshow.clearAutoScrollWait, 8000 );		
	},
	
	/*-------------------------------*/
	/* Callback to resume autoscroll */
	clearAutoScrollWait: function()
	{
		ipb.slideshow.stopAutoScroll = false;
		return;		
	},	
	
	/*------------------------------*/
	/* User is jumping to an image  */
	thumbnailClick: function(e, elem)
	{
		Event.stop(e);
		
		var thumbID = $( elem ).id.replace('thumb_', '');
		
		// Are we paused? If not, do so
		if( !ipb.slideshow.paused ){
			ipb.slideshow.pause();
		}
		
		ipb.slideshow.loadImage( thumbID, IMAGE_DATA.get( thumbID )['filename'] );
		ipb.slideshow.update( thumbID );
	},	
	
	/*------------------------*/
	/* Plays the slideshow 	  */
	play: function()
	{
		var mainLoop = function()
		{
			var nextImage = ipb.slideshow.getNextID();
			ipb.slideshow.update( nextImage );
			
			// Get the next image ready
			var oneAfter = ipb.slideshow.getNextID( nextImage );
			ipb.slideshow.loadImage( oneAfter, IMAGE_DATA.get( oneAfter )['filename'] );
			
			// Continue slideshow
			ipb.slideshow.mainLoop = setTimeout( mainLoop, ( ipb.slideshow.speed * 1000 ) );
		};
		
		mainLoop();					
	},
	
	/*------------------------*/
	/* Pause button	          */
	buttonPause: function(e)
	{
		Event.stop(e);
		
		if( !ipb.slideshow.paused ){
			ipb.slideshow.pause();
		} else {
			ipb.slideshow.unpause();
		}		
	},
	
	/*-------------------------*/
	/* Next image button  	   */
	buttonNext: function(e)
	{
		Event.stop(e);
		
		// Are we paused? If not, do so
		if( !ipb.slideshow.paused ){
			ipb.slideshow.pause();
		}
		
		// Go to the next image
		var nextImage = ipb.slideshow.getNextID();
		ipb.slideshow.loadImage( nextImage, IMAGE_DATA.get( nextImage )['filename'] );
		ipb.slideshow.update( nextImage );		
	},
	
	/*-------------------------*/
	/* Next image button  	   */
	buttonPrevious: function(e)
	{
		Event.stop(e);
		
		// Are we paused? If not, do so
		if( !ipb.slideshow.paused ){
			ipb.slideshow.pause();
		}
		
		// Go to the next image
		var prevImage = ipb.slideshow.getPrevID();
		ipb.slideshow.loadImage( prevImage, IMAGE_DATA.get( prevImage )['filename'] );
		ipb.slideshow.update( prevImage );		
	},
	
	/*-------------------------*/
	/* Pauses				   */
	pause: function()
	{
		clearTimeout( ipb.slideshow.mainLoop );
		ipb.slideshow.paused = 1;
		$('c_PAUSE_PLAY').addClassName('paused');
		Debug.write("Slideshow is paused.");
	},
	
	/*-------------------------*/
	/* Unpauses				   */
	unpause: function()
	{
		ipb.slideshow.play();
		ipb.slideshow.paused = 0;
		$('c_PAUSE_PLAY').removeClassName('paused');
		Debug.write("Slideshow is playing.");
	},
	
	/*-----------------------------------*/
	/* Readies the next image to display */
	update: function( nextImage )
	{
		if( ipb.slideshow.currentImage == nextImage ){ return; }
		
		if( !$('img_' + nextImage) ){
			ipb.slideshow.loadImage( nextImage, IMAGE_DATA.get( nextImage )['filename'] );
		}
		
		// If the image hasn't finished loading, then go into a loop and wait
		if( $('img_' + nextImage).complete != true ){
			Debug.write("Loading image...");
			ipb.slideshow.loading(true);
			ipb.slideshow.update.delay( .1, nextImage );
			return;
		} else { 
			ipb.slideshow.loading(false);
			Debug.write("Image loaded.");
		}
		
		// See if we need to resize & position
		ipb.slideshow.checkSizing( nextImage );
		ipb.slideshow.positionImage( nextImage );
		
		// Build info box
		ipb.slideshow.buildImageInfo( nextImage );
		
		// Now transition
		if( ipb.slideshow.currentImage != 0 )
		{
			new Effect.Parallel([
				new Effect.Fade( $('img_' + ipb.slideshow.currentImage) ),
				new Effect.Appear( $('img_' + nextImage ) )
			], { duration: ipb.slideshow.transitionLen });
			
			//if( IMAGE_DATA.get( ipb.slideshow.currentImage )['author']['id'] != IMAGE_DATA.get( nextImage )['author']['id'] )
			//{
				new Effect.Parallel([
					new Effect.Fade( $('info_' + IMAGE_DATA.get( ipb.slideshow.currentImage )['author']['id'] + '_' + ipb.slideshow.currentImage) ),
					new Effect.Appear( $('info_' + IMAGE_DATA.get( nextImage )['author']['id'] + '_' + nextImage ) )
				], { duration: ipb.slideshow.transitionLen });
			//}
		}
		else
		{
			new Effect.Appear( $('img_' + nextImage ), { duration: ipb.slideshow.transitionLen } );
			new Effect.Appear( $('info_' + IMAGE_DATA.get( nextImage )['author']['id'] + '_' + nextImage ), { duration: ipb.slideshow.transitionLen } );
		}
		
		// Thumbnail stuff
		if( $('thumb_' + nextImage) )
		{		
			ipb.slideshow.positionThumbnailBar( nextImage );			
			
			if( ipb.slideshow.currentImage ){
				$('thumb_' + ipb.slideshow.currentImage).removeClassName('active');
			}
			$('thumb_' + nextImage).addClassName('active');
		}
		
		// Set the current ID
		ipb.slideshow.currentImage = nextImage;
	},
	
	/*----------------------------------*/
	/* Activates/deactivates buttons    */
	recalculateThumbnailButtons: function()
	{
		var thumbWrapLeft = parseInt( $('thumbnail_wrap').getStyle('left') );
		var barWidth = $('thumbnail_wrap').getWidth();
		var viewportWidth = document.viewport.getWidth();
		
		if( thumbWrapLeft >= 30 ){
			$('button_left').removeClassName('active').addClassName('disabled');
		} else {
			$('button_left').addClassName('active').removeClassName('disabled');
		}
		
		if( $('thumbnail_wrap').getWidth() < $('thumbnail_bar').getWidth() ){
			$('button_right').removeClassName('active').addClassName('disabled');
		}
		
		if( ( thumbWrapLeft + barWidth ) > ( viewportWidth - 30 ) ){
			$('button_right').removeClassName('disabled').addClassName('active');
		} else {
			$('button_right').removeClassName('active').addClassName('disabled');
		}
		
	},
	
	/*---------------------------------*/
	/* Positions the thumbnail bar	   */
	positionThumbnailBar: function( thumbID )
	{
		// We're not autoscrolling right now
		if( ipb.slideshow.stopAutoScroll ){
			ipb.slideshow.recalculateThumbnailButtons();
			return;
		}
		
		var barWidth = $('thumbnail_bar').getWidth();
		var thumbPosition = $('thumb_' + thumbID).viewportOffset();
		var positionedOffset = $('thumb_' + thumbID).positionedOffset();
		
		var rightPos = barWidth - 150;
		
		var afterFinish = function()
		{
			ipb.slideshow.recalculateThumbnailButtons();
		};
		
		// Is the thumbnail off to the left?
		if( thumbPosition['left'] < 30 )
		{
			if( positionedOffset['left'] > rightPos ){
				new Effect.Move( $('thumbnail_wrap'), { x: ( rightPos - thumbPosition['left'] ), y: 0, mode: 'relative', duration: 0.2, afterFinish: afterFinish } );
			} else {
				new Effect.Move( $('thumbnail_wrap'), { x: 30, y: 0, mode: 'absolute', duration: 0.2, afterFinish: afterFinish } );
			}
		}
		else if( thumbPosition['left'] > rightPos )
		{			
			new Effect.Move( $('thumbnail_wrap'), { x: (rightPos - thumbPosition['left']), y: 0, mode: 'relative', duration: 0.2, afterFinish: afterFinish } );
		}
		else
		{
			ipb.slideshow.recalculateThumbnailButtons();
		}
		
	},
	
	/*---------------------------------*/
	/* Builds the thumbnail bar		   */
	buildThumbnails: function()
	{
		var thumbCount = 0;
		
		IMAGES.each( function( imageID ){
			var content = ipb.slideshow.thumbnail.evaluate({ 'id': imageID });
			$('thumbnail_wrap').insert( content );
			$('thumb_' + imageID).setStyle( { backgroundImage: "url(" + ipb.slideshow.imageURL + IMAGE_DATA.get( imageID )['thumb'] + ")" } );
			
			if( ipb.slideshow.thumbSize == 0 )
			{
				ipb.slideshow.thumbSize = $('thumb_' + imageID).getWidth();
				
				if( $('thumb_' + imageID).getStyle('margin-right') )
				{
					ipb.slideshow.thumbSize += parseInt( $('thumb_' + imageID).getStyle('margin-right') );
				}
			}
			
			thumbCount++;
		});	
		
		$('thumbnail_wrap').setStyle( { width: ( thumbCount * ipb.slideshow.thumbSize ) + 'px' } );	
		$('thumbnail_wrap').writeAttribute('unselectable', 'on');
		$('thumbnail_bar').writeAttribute('unselectable', 'on');		
	},
	
	/*---------------------------------*/
	/* Build the info box for an image */
	buildImageInfo: function( imageID )
	{
		var authorInfo = IMAGE_DATA.get( imageID )['author'];
		var authorID = authorInfo['id'];
		
		if( $('info_' + authorID + '_' + imageID ) ){ return true; }
		
		var contents = ipb.slideshow.userInfo.evaluate( { 
				'id': authorID + '_' + imageID,
				'name': authorInfo['name'],
				'photo': ipb.slideshow.userPhotoURL + authorInfo['photo'],
				'title': IMAGE_DATA.get( imageID )['title'],
				'width': authorInfo['width'],
				'height': authorInfo['height']
	 	});
		
		$('image_info').insert( contents );
	},
	
	/*------------------------------*/
	/* Preloads an image			*/
	loadImage: function( id, filename )
	{
		if( $( 'img_' + id ) ){ return true; }
		
		var img = new Element('img', { 'id': 'img_' + id, 'src': ipb.slideshow.imageURL + filename }).hide();
		$('image_holder').insert( img );
	},
	
	/*------------------------------*/
	/* Checks the image will fit	*/
	checkSizing: function( imageID )
	{
		var img = $('img_' + imageID);
		
		if( !img.readAttribute('origWidth') || !img.readAttribute('origHeight') )
		{
			var dims = img.getDimensions();
			img.writeAttribute('origWidth', dims['width']).writeAttribute('origHeight', dims['height']);
		}
		
		//Debug.write("Holder size: " +  ipb.slideshow.holderSize['width'] + ", Image size: " + img.readAttribute('origWidth'));
		
		var newWidth = img.readAttribute('origWidth');
		var newHeight = img.readAttribute('origHeight');
		var aspect = img.readAttribute('origWidth') / img.readAttribute('origHeight');
		
		if( newWidth > ipb.slideshow.holderSize['width'] ){
			newWidth = ipb.slideshow.holderSize['width'];
			newHeight = ( newWidth / aspect );
		}
		
		if( newHeight > ipb.slideshow.holderSize['height'] ){
			newHeight = ipb.slideshow.holderSize['height'];
			newWidth = ( newHeight * aspect );
		}
		
		// Resize image
		img.width = newWidth;
		img.height = newHeight;
		
	},
	
	/*----------------------------------*/
	/* Positions an image in the middle */
	positionImage: function( imageID )
	{
		var img = $('img_' + imageID).getDimensions();
		
		var left = Math.ceil( ( ipb.slideshow.holderSize['width'] - img['width'] ) / 2 );
		var top = Math.ceil( ( ipb.slideshow.holderSize['height'] - img['height'] ) / 2 );
		
		// Border
		if( $('img_' + imageID).getStyle('border-top-width') )
		{
			var border = $('img_' + imageID).getStyle('border-top-width').replace('px', '');
			left = left - parseInt( border );
			top = top - parseInt( border );
		}
		
		//Debug.write( "Left: " + left + ", " + "top: " + top );
		// Position
		$('img_' + imageID).setStyle( { left: left + "px", top: top + "px" } );		
	},
	
	/*-------------------------------*/
	/* Returns the next ID in line   */
	getNextID: function( curID )
	{
		// If no ID or currentImage is specified, return first image
		if( Object.isUndefined( curID ) && ipb.slideshow.currentImage == 0 ){
			return IMAGES.first();
		}
		
		var curID = ( Object.isUndefined( curID ) ) ? ipb.slideshow.currentImage : curID;	
		var pos = IMAGES.indexOf( parseInt(curID) );
		
		// Last item?
		if( pos == ( IMAGES.length - 1 ) ){
			return IMAGES.first();
		} else {
			return IMAGES[ pos + 1 ];
		}		
	},
	
	/*-------------------------------*/
	/* Returns the next ID in line   */
	getPrevID: function( curID )
	{
		// If no ID or currentImage is specified, return first image
		if( Object.isUndefined( curID ) && ipb.slideshow.currentImage == 0 ){
			return IMAGES.first();
		}
		
		var curID = ( Object.isUndefined( curID ) ) ? ipb.slideshow.currentImage : curID;	
		var pos = IMAGES.indexOf( parseInt(curID) );
		
		// Last item?
		if( pos === 0 ){
			return IMAGES.last();
		} else {
			return IMAGES[ pos - 1 ];
		}		
	},
	
	/*------------------------------*/
	/* Show loading throbber        */
	loading: function( loading )
	{
		if( loading ){
			$('loading').show();
		} else {
			$('loading').hide();
		}
	},
	
	/*-------------------------------------*/
	/* Resize event to get new holder size */
	resizeWindow: function()
	{
		ipb.slideshow.holderSize = $('image_holder').getDimensions();
		ipb.slideshow.recalculateThumbnailButtons();
	}
};

ipb.slideshow.init();
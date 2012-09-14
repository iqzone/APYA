/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _post = window.IPBoard;

_post.prototype.post = {
	cal_open: '',
	cal_close: '',
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.post.js");
		
		document.observe("dom:loaded", function(){
			ipb.post.initEvents();
		});
	},
	initEvents: function()
	{		
		// Form validation
		if( $('postingform') ){
			$('postingform').observe('submit', ipb.post.postFormSubmit);
		}
		
		// Add calendars
		if( $('mod_open_date') && $('mod_open_date_icon') ){
			$('mod_open_date_icon').observe('click', function(){
				new CalendarDateSelect( $('mod_open_date'), { year_range: 6, close_on_click: true } );
			});
		}
		if( $('mod_close_date') && $('mod_close_date_icon') ){
			$('mod_close_date_icon').observe('click', function(){
				new CalendarDateSelect( $('mod_close_date'), { year_range: 6, close_on_click: true } );
			});
		}
		
		if( $('post_preview') ){
			ipb.post.externalizePreviewLinks( $('post_preview') );
		}

		if( $('review_topic') ){
			$('review_topic').observe('click', ipb.global.openNewWindow.bindAsEventListener( this, $('review_topic'), 1 ) );
		}
		
		if( $('topic_title') ){
			$('topic_title').focus();
		}
		
		if ( $('post_htmlstatus') )
		{
			ipb.textEditor.bindHtmlCheckbox( $('post_htmlstatus') );
		}
	},
	
	// Bug #16805
	// Forces all links in post preview to open in new window, to
	// prevent losing the post content when link is clicked
	externalizePreviewLinks: function( wrapper )
	{
		if( !$( wrapper ) ){ return; }
		
		Debug.write("Finding links in post preview");
		
		// Find all links
		$( wrapper ).select('a').each( function(elem){
			var curRel = ['external'];
			
			if( $( elem ).readAttribute('rel') != null ){
				curRel.push( $( elem ).readAttribute('rel').replace('external', '') );
			}
			
			$( elem ).writeAttribute('rel', curRel.join(' ') );
		});
	},
	
	postFormSubmit: function(e)
	{
		return true;
		
		Event.stop(e);
		Debug.write( "Submitting" );
		if( $('username') && $F('username').blank() ){
			alert( ipb.lang['post_empty_username'] );
			error = true;
		}
		if( $('topic_title')  ){
			alert( ipb.lang['post_empty_title'] );
			error = true;
		}
		if( $('ed-0_textarea') && $F('ed-0_textarea').blank() ){
			alert( ipb.lang['post_empty_post'] );
			error = true;
		}
		
		
		if( error ){ Event.stop(e); };
	}
};

ipb.post.init();
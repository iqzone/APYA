/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.profile.js - Forum view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _sharelinks = window.IPBoard;

_sharelinks.prototype.sharelinks = {
	popups: null,
	bname: null,
	url: null,
	title: null,
	maxTwitterLen: 115,
	
	init: function()
	{
		Debug.write("Initializing ips.sharelinks.js");
		
		document.observe("dom:loaded", function(){
			ipb.sharelinks.initEvents();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Initialize events
	*/
	initEvents: function()
	{
		ipb.delegate.register('._slink', ipb.sharelinks.sharePop);
	},
	
	/* ------------------------------ */
	/**
	 * Twitter pop innit
	 * 
	 * @param	{event}		e		The event
	*/
	sharePop: function(e, elem)
	{
		var shareKey = elem.id.replace( /slink_/, '' );
		var ok       = false;
		var callback = null;
		var h        = '190';
		
		if ( shareKey == 'twitter' )
		{
			if ( ipb.vars['member_id'] && ipb.vars['twitter_id'] )
			{
				callback = ipb.sharelinks.twitterPostPop;
				ok       = true;
				h        = '110';
			}
		}
		
		if ( ok === false || DISABLE_AJAX )
		{
			return false;
		}
		
		/* Still here? */
		Event.stop(e);

		var _url  = ipb.vars['base_url'] + '&app=core&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=sharelinks&do=' + shareKey + 'Form';
		
		var x = new ipb.Popup( 'shareKeyPop_' + Math.random(), { type: 'modal',
																 ajaxURL: _url,
																 modal: true,
																 stem: false,
																 hideAtStart: false,
																 w: '550px', h: h }, { 'afterAjax': callback, 'beforeAjax': ipb.sharelinks.resetPop } );
	},
	
	/**
	 * Post process twitter pop-up
	 */
	facebookPostPop: function( e, responseText )
	{
		/* Issue with FB API? */
		if( responseText == 'x' )
		{
			window.location = "http://www.facebook.com/sharer.php?u=" + ipb.sharelinks.url.encodeParam();
			return false;
		}
		
		$('fContent').update('');
		$('fSubmit').observe('click', ipb.sharelinks.goFacebook);
	},
	
	/**
	 * Post process twitter pop-up
	 */
	twitterPostPop: function( e, responseText )
	{
		/* Issue with Twitter API? */
		if( responseText == 'x' )
		{
			window.location = "http://twitter.com/intent/tweet?status=" + ipb.sharelinks.title.encodeParam() + '%20-%20' + ipb.sharelinks.url.encodeParam();
			return false;
		}
		
		/* clean up title */
		ipb.sharelinks.title = ipb.sharelinks.title.replace(/&#34;/g , '"');
		ipb.sharelinks.title = ipb.sharelinks.title.replace(/&#33;/g , "!");
		ipb.sharelinks.title = ipb.sharelinks.title.replace(/&#39;/g, "'");
		ipb.sharelinks.title = ipb.sharelinks.title.replace(/&quot;/g, '"');
		
		/* Clean up board name */
		ipb.sharelinks.bname = ipb.sharelinks.bname.replace(/&#34;/g , '"');
		ipb.sharelinks.bname = ipb.sharelinks.bname.replace(/&#33;/g , "!");
		ipb.sharelinks.bname = ipb.sharelinks.bname.replace(/&#39;/g , "'");
		ipb.sharelinks.bname = ipb.sharelinks.bname.replace(/&quot;/g, '"');
		Debug.write( ipb.sharelinks.title );
		$('tContent').value = ipb.sharelinks.bname + ': ' + ipb.sharelinks.title;
		$('tContent').observe('keyup', ipb.sharelinks.checkTwitterLength);
		$('tSubmit').observe('click', ipb.sharelinks.goTwitter);
		
		ipb.sharelinks.checkTwitterLength(e);
	},
	
	/**
	 * Reset pop-up
	 */
	resetPop: function(e)
	{
		if ( $('tContent') )
		{
			$('tContent').stopObserving();
			$('tContent').remove();
			$('tSubmit').stopObserving();
			$('tWrap').remove();
		}
		
		if ( $('cLeft') )
		{
			$('cLeft').remove();
		}
		
		if ( $('fContent') )
		{
			$('fContent').remove();
			$('fSubmit').stopObserving();
			$('fWrap').remove();
		}
	},
	
	/**
	 * Post a comment to twitter
	 */
	goTwitter: function(e)
	{
		if( DISABLE_AJAX )
		{
			return false;
		}
		
		Event.stop(e);
		
		if ( ! $('tContent') || $F('tContent').blank() )
		{
			return;
		}
		
		new Ajax.Request( ipb.vars['base_url'] + '&app=core&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=sharelinks&do=twitterGo',
						{
							method: 'post',
							parameters: {
								'tweet': $F('tContent').encodeParam(),
								'title': ipb.sharelinks.title.encodeParam(),
								'url': ipb.sharelinks.url.encodeParam()
							},
							onSuccess: function(t)
							{
								/* Check */
								if( t.responseText == 'failwhale' )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								else
								{
									$('tWrap').update( t.responseText );
								}
							}
						});
	},
	
	/**
	 * Post a comment to Facebook
	 * @Deprecated as of 3.3 - now using Facebook standard share button
	 */
	goFacebook: function(e)
	{
		if( DISABLE_AJAX )
		{
			return false;
		}
		
		Event.stop(e);
		
		new Ajax.Request( ipb.vars['base_url'] + '&app=core&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=sharelinks&do=facebookGo',
						{
							method: 'post',
							parameters: {
								'comment': $F('fContent').encodeParam(),
								'title': ipb.sharelinks.title.encodeParam(),
								'url': ipb.sharelinks.url.encodeParam()
							},
							onSuccess: function(t)
							{
								/* Check */
								if( t.responseText == 'finchersaysno' )
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
								else
								{
									$('fWrap').update( t.responseText );
								}
							}
						});
	},
		
	checkTwitterLength: function(e)
	{
		newTotal = parseInt( ipb.sharelinks.maxTwitterLen ) - parseInt( $F('tContent').length );
		
		if( newTotal < 0 )
		{
			$('tContent').value = $F('tContent').truncate( ipb.sharelinks.maxTwitterLen, '' );
			newTotal = 0;
		}
		
		$('cLeft').update( newTotal );
	}
};

ipb.sharelinks.init();
/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _search = window.IPBoard;

_search.prototype.search = {
	checks: [],
	curApp: null,
	updateFilters: false,
	vncPopup: null,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.search.js");
		
		document.observe("dom:loaded", function(){
			
			if( $('query') ){ $('query').focus(); }
			
			// set up calendars
			if( $('date_start') && $('date_start_icon') )
			{
				$('date_start_icon').observe('click', function(e){
					ipb.search.calendar_start = new CalendarDateSelect( $('date_start'), { year_range: 6, time: true, close_on_click: true } );
				});
			}
			
			if( $('date_end') && $('date_end_icon') )
			{
				$('date_end_icon').observe('click', function(e){
					ipb.search.calendar_start = new CalendarDateSelect( $('date_end'), { year_range: 6, time: true, close_on_click: true } );
				});
			}
			
			// Set up app selector
			if( $('sapps') ){
				$('sapps').select('input').each( function(elem){
					var id = $(elem).id.replace('radio_', '');
					var _d = false;
					
					if( $(elem).checked ){
						$(elem).up().addClassName('active');
						ipb.search.curApp = id;
					}
					
					if( $('app_filter_' + id) ){
						$('app_filter_' + id ).wrap('div', { id: 'app_filter_' + id + '_wrap' } ).addClassName('extra_filter').hide();
						$('app_filter_' + id ).show();
						if( id == ipb.search.curApp ){
							$('app_filter_' + id + '_wrap').show();
						}
					}
										
					$(elem).observe('click', ipb.search.selectApp);
				});
			}
			
			if( $('author') )
			{
				// Autocomplete stuff
				document.observe("dom:loaded", function(){
					var url = ipb.vars['base_url'] + 'secure_key=' + ipb.vars['secure_hash'] + '&app=core&module=ajax&section=findnames&do=get-member-names&name=';
					var ac = new ipb.Autocomplete( $('author'), { multibox: false, url: url, templates: { wrap: ipb.templates['autocomplete_wrap'], item: ipb.templates['autocomplete_item'] } } );
				});
			}
			
			if( $('vncForumFilter') )
			{
				$('vncForumFilter').observe('click', ipb.search.openVncForumFilter );
			}
		});
	},
	
	openVncForumFilter: function( e )
	{
		Event.stop(e);
		
		var url = ipb.vars['base_url'] + "app=core&module=ajax&section=search&do=showForumsVncFilter&secure_key=" + ipb.vars['secure_hash'];

		ipb.search.vncPopup	= new ipb.Popup( 'vnc_filter_popup', { type: 'modal',
											 ajaxURL: url,
											 hideAtStart: false,
											 hideClose: true,
											 w: '600px',
											 h: 450 }, { 'afterInit': function() { 
												$("save_vnc_filters").observe( 'click', ipb.search.saveVncFilters );
												$("cancel_vnc_filters").observe( 'click', ipb.search.cancelVncFilters );
											} } );
		
		/* delegate */
		ipb.delegate.register('li[class~="clickable"]', ipb.search.clickVncFilters);
		
		return false;
	},
	
	cancelVncFilters: function( e )
	{
		Event.stop(e);
		ipb.search.vncPopup.kill();
		return false;
	},
	
	saveVncFilters: function( e )
	{
		ipb.search.setVncFilters(e);
		
		/* Only reset filters if we changed something */
		if( ipb.search.updateFilters == false )
		{
			ipb.search.vncPopup.kill();
			return false;
		}
		
		var toSave	= '';
		
		$$('.search_filter_container ul li input').each( function( _elem ){
			if( $(_elem).value == 1 )
			{
				toSave += $(_elem).id.replace( /^hf_/, '' ) + ',';
			}
		});
		
		if( toSave == '' )
		{
			toSave = 'all';
		}
		
		var request = new Ajax.Request( ipb.vars['base_url'] + 'app=core&module=ajax&section=search&do=saveForumsVncFilter',
							{
								method: 'post',
								parameters: {
									secure_key: 	ipb.vars['secure_hash'],
									saveVncFilters:	toSave
								},
								onSuccess: function(t)
								{
									window.location.reload( true );
								}.bind( this )
							}
						);
	},
	
	clickVncFilters: function(event, elem)
	{
		var id = $(elem).id;
		if( !id ){ return false; }
		id	= id.replace( /^forum_/, '' );
		
		if( id == 'all' ){
			$$('.search_filter_container ul li.active').invoke('removeClassName', 'active');
			$('forum_all').addClassName('active');
		} else {
			if( $('forum_all').hasClassName('active') ){
				$('forum_all').removeClassName('active');
			}
			$(elem).toggleClassName('active');
		}
		
		ipb.search.updateFilters = true;		
	},
	
	setVncFilters: function( event, elem )
	{
		Event.stop(event);
		
		$$('.search_filter_container ul li input').invoke('setValue', 0);
		
		if( $('forum_all').hasClassName('active') ){
			$('hf_all').value = 1;
		} else {
			$('hf_all').value = 0;
			
			$$('.search_filter_container ul li.active').each( function( _elem ){
				$(_elem).down('input').value = 1;
			});			
		}
		
		return true;
	},
	
	selectApp: function(e)
	{
		var elem = Event.element(e);
		var id = $(elem).id.replace('radio_', '');
		if( !id || id == ipb.search.curApp ){ return; }
		
		if( ipb.search.curApp ){
			$('sapp_' + ipb.search.curApp).removeClassName('active');
		}
		$('sapp_' + id).addClassName('active');
		
		if( $('app_filter_' + ipb.search.curApp) && ( $('app_filter_' + id) ) ){
			new Effect.BlindUp( $('app_filter_' + ipb.search.curApp + '_wrap'), { duration: 0.3, afterFinish: function(){
				new Effect.BlindDown( $('app_filter_' + id + '_wrap'), { duration: 0.3 } );
			}});
		} else if( $('app_filter_' + ipb.search.curApp) ){
			new Effect.BlindUp( $('app_filter_' + ipb.search.curApp + '_wrap'), { duration: 0.3 } );
		} else if( $('app_filter_' + id) ){
			new Effect.BlindDown( $('app_filter_' + id + '_wrap'), { duration: 0.3 } );
		}

		if ( $('tag_row') )
		{
			if( $(elem).readAttribute('data-allowtags') === '1' ){
				if( !$('tag_row').visible() ){
					new Effect.BlindDown( $('tag_row'), { duration: 0.4 } );
				}
			} else {
				if( $('tag_row').visible() ){
					new Effect.BlindUp( $('tag_row'), { duration: 0.4 } );
				}
			}
		}
				
		ipb.search.curApp = id;		
	}
};
ipb.search.init();
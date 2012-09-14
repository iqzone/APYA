/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.calendar.js - Calendar code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _cal = window.IPBoard;

_cal.prototype.calendar = {
	inSection: 	'',
	popup: null,
	yearLimit: 6,
	startYear: 0,
	
	/* ------------------------------ */
	/**
	 * Constructor
	*/
	init: function()
	{
		Debug.write("Initializing ips.calendar.js");
		
		document.observe("dom:loaded", function(){
			ipb.calendar.initEvents();
			
			if( $('calendar_table') ){
				$('calendar_table').on( "click", 'td:not(.blank):not(.col_cal_date)', ipb.calendar.selectCell );
				$('calendar_table').on( "dblclick", 'td:not(.blank):not(.col_cal_date)', ipb.calendar.viewDay );
			}
			
			if( $('view_month') ){
				$('view_month').observe("click", ipb.calendar.viewMonth);
			}
			if( $('view_week') ){
				$('view_week').observe("click", ipb.calendar.viewWeek);
			}
			if( $('view_day') ){
				$('view_day').observe("click", ipb.calendar.viewDay);
			}
			ipb.delegate.register("#month_jump", ipb.calendar.showJump);
		});
	},
	
	showJump: function(e, elem)
	{
		Event.stop(e);
		
		if( ipb.calendar.popup ){
			ipb.calendar.popup.show();
		} else {
			ipb.calendar.popup = new ipb.Popup( 'month_jump', { 	type: 'balloon',
											initial: $('mini_calendar_jump').show(),
											hideAtStart: false,
											stem: true,
											hideClose: true,
											attach: { target: elem, position: 'auto' },
											w: '240px'
										} );
		}
	},
	
	selectCell: function(e, elem)
	{
		$$("#calendar_table td.selected").invoke("removeClassName", "selected").invoke("writeAttribute", "unselectable", "off");
		$( elem ).writeAttribute("unselectable", "on");
		$( elem ).addClassName('selected');
		
		if ( $('ips_addEventButton') )
		{
			url = $('ips_addEventButton').readAttribute('href');
			url = url.replace( /\/add(.*)$/, '/add' );
			
			bits = elem.id.split('-');
			
			y = bits[1];
			m = bits[2];
			d = bits[3];
			
			if ( y && m && d )
			{
				url = url + ipb.vars['seo_params']['varBlock']
						  + 'd' + ipb.vars['seo_params']['varSep'] + d + ipb.vars['seo_params']['varSep']
						  + 'm' + ipb.vars['seo_params']['varSep'] + m + ipb.vars['seo_params']['varSep']
						  + 'y' + ipb.vars['seo_params']['varSep'] + y;
						  
				$('ips_addEventButton').writeAttribute('href', url);
			}
		}
	},
	
	addCalEvent: function(e, elem)
	{
		window.location = ipb.vars['add_event_url'];
	},
	
	viewDay: function(e)
	{
		Event.stop(e);
		
		if( ipb.calendar.currentView == 'day' )
		{
			return;
		}
		
		var active = ipb.calendar.findActive();
		if( !active ){ return; }
		
		if( active.hasAttribute("data-furl") )
		{
			window.location = active.readAttribute("data-furl");
		}
		else
		{
			var date = $(active).id.match(/(\d+)/gi);		
			window.location = ipb.vars['day_url'] + "&y=" + date[0] + "&m=" + date[1] + "&d=" + date[2];
		}
	},
	
	viewWeek: function(e)
	{
		Event.stop(e);
		
		if( ipb.calendar.currentView == 'day' ){
			window.location = ipb.vars['week_url'] + "&week=" + ipb.calendar.weekToJump;
			return;
		}
		
		var active = ipb.calendar.findActive();
		if( !active ){ return; }
		
		// Find the active row
		var timestamp = $( active ).up("tr").readAttribute("data-week");
		window.location = ipb.vars['week_url'] + "&week=" + timestamp;		
	},
	
	viewMonth: function(e)
	{
		Event.stop(e);
		
		if( ipb.calendar.currentView == 'day' )
		{
			window.location = ipb.vars['month_url'] + "&y=" + ipb.calendar.currentYear + "&m=" + ipb.calendar.currentMonth;
			return;
		}
		
		var active = ipb.calendar.findActive();
		if( !active ){ return; }
		
		var date = $(active).id.match(/(\d+)/gi);		
		window.location = ipb.vars['month_url'] + "&y=" + date[0] + "&m=" + date[1];
	},
	
	findActive: function()
	{
		// Find active cell (Selected > Today > First of the month)
		var active = ["td.selected","td.today","td:not(.blank):not(.col_cal_date)"].find( function(n){
			return $("calendar_table").select( n ).size();
		});
		if( !active ){ return; }
		active = $("calendar_table").select( active )[0];
		
		return active;
	},
	
	/* ------------------------------ */
	/**
	 * Set up page events
	*/
	initEvents: function()
	{
		ipb.delegate.register('.post_id a[rel~="bookmark"]', ipb.calendar.showLinkToEvent );
		
		if( $('rsvp-button') )
		{
			$('rsvp-button').observe( 'click', ipb.calendar.rsvpEvent );
		}
		
		if( ipb.calendar.inSection == 'form' )
		{
			if( $('all_day') )
			{
				if( $F('all_day') == 1 )
				{
					$$('.time_setting').invoke('hide');
				}

				$('all_day').observe( 'click', function(e)
				{
					$$('.time_setting').invoke('toggle');
				});
			}
			
			if( $('set_enddate') )
			{
				if( $F('set_enddate') != 1 )
				{
					$('end_date_fields').hide();
				}

				$('set_enddate').observe( 'click', function(e)
				{
					$('end_date_fields').toggle();
					
					if( $F('set_recurfields') == 1 )
					{
						$('set_recurfields').checked	= false;
						$('recur_fields').toggle();
					}
				});
			}

			if( $('set_recurfields') )
			{
				if( $F('set_recurfields') != 1 )
				{
					$('recur_fields').hide();
				}

				$('set_recurfields').observe( 'click', function(e)
				{
					$('recur_fields').toggle();
					
					if( $F('set_enddate') != 1 && $F('set_recurfields') == 1 )
					{
						$('set_enddate').checked	= true;
						$('end_date_fields').toggle();
					}
				});
			}
			
			var yearRange	= new Array();
			
			if( !ipb.calendar.startYear )
			{
				var _d	= new Date();
				ipb.calendar.startYear	= _d.getFullYear();
			}
			
			yearRange.push( ipb.calendar.startYear );
			
			if( ipb.calendar.yearLimit )
			{
				yearRange.push( ipb.calendar.startYear + ipb.calendar.yearLimit );
			}
			else
			{
				yearRange.push( ipb.calendar.startYear + 6 );
			}
			
			if( $('start_date') && $('start_date_icon') )
			{
				$('start_date_icon').observe('click', function(e){
					ipb.calendar.calendar_start = new CalendarDateSelect( $('start_date'), { year_range: yearRange, time: false, close_on_click: true, format: 'american' } );
				});
			}
			
			if( $('end_date') && $('end_date_icon') )
			{
				$('end_date_icon').observe('click', function(e){
					ipb.calendar.calendar_start = new CalendarDateSelect( $('end_date'), { year_range: yearRange, time: false, close_on_click: true, format: 'american' } );
				});
			}
		
			if( $('e_groups') )
			{
				$('e_type').observe( 'change', ipb.calendar.hideAdminOptions );
			}
		
			if( $('all_groups') )
			{
				$('all_groups').observe( 'click', ipb.calendar.checkAllGroups );
			}
			
			ipb.calendar.hideAdminOptions();
		}
	},
	
	/* ------------------------------ */
	/**
	 * RSVP for event
	 * 
	 * @var		{event}		e	The event
	*/
	rsvpEvent: function(e)
	{
		Event.stop(e);
		
		var eventid	= Event.findElement(e, 'a').rel.replace( /event_id_/, '' );
		var url		= ipb.vars['base_url'] + 'app=calendar&module=ajax&section=rsvp&do=add&event_id=' + eventid;

		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: {
									md5check: 	ipb.vars['secure_hash']
								},
								onSuccess: function(t)
								{
									if( t.responseJSON['error'] )
									{
										alert(t.responseJSON['error']);
									}
									else
									{
										if( $('no_attendees') ){
											$('no_attendees').remove();
										}
										
										$('rsvp_button').hide();
										//$('rsvp-button').each( function(elem) { $(elem).hide(); } );

										$('attendee_list').insert( t.responseJSON['html'] );
									}
								}
							}
						);
		
		return false;
	},
		
	/* ------------------------------ */
	/**
	 * Checks all groups on the add event form
	 * 
	 * @var		{event}		e	The event
	*/
	checkAllGroups: function(e)
	{
		if( $F('all_groups') == '1' )
		{
			for( var i=0; i< $('e_groups').options.length; i++){
				$('e_groups').options[i].selected = true;
			}
			
			$('e_groups').disable();
		}
		else
		{
			$('e_groups').enable();
			
			for( var i=0; i< $('e_groups').options.length; i++){
				$('e_groups').options[i].selected = false;
			}
		}
	},

	/* ------------------------------ */
	/**
	 * Hides unused options on the add event form
	 * 
	 * @var		{event}		e	The event
	*/	
	hideAdminOptions: function(e)
	{
		if( $('e_type') && $F('e_type') == 'public' )
		{
			$$('.type_setting').invoke('show');
		}
		else
		{
			$$('.type_setting').invoke('hide');
		}
	},
	
	/* ------------------------------ */
	/**
	 * Shows a prompt allowing user to copy the URL
	 * 
	 * @var		{event}		e		The event
	*/
	showLinkToEvent: function(e, elem)
	{
		_t = prompt( ipb.lang['copy_topic_link'], $( elem ).readAttribute('href') );
		Event.stop(e);
	}
};

ipb.calendar.init();
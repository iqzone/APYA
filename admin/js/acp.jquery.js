var ips = (function($){
	
	function init(){
		// Hide menus
		$(".acp-menu").hide();
		
		// Set up modal
		$("#modal").css( { height: ( $(window).height() - parseInt( $("#header").height() ) ) } );
		$(window).resize( function(e){
			$("#modal").css( { height: ( $(window).height() - parseInt( $("#header").height() ) ) } );
		});
	}
	
	return {
		init: init
	};
})(jQuery);

jQ(document).ready(function() {
 	ips.init();
	jQ("#app_menu").ipsMultiMenu( { keyboardTrigger: 77 } );
	sidebar.init();
	livesearch.init();
});


// ************************************ //
// Extend jQuery with custom methods	//
// ************************************ //	
jQ.undefined = function( obj ){
	return typeof obj === "undefined";
};

// ************************************ //
// Basic IPS plugins					//
// ************************************ //
var livesearch = {}, sidebar = {};

(function($){
	
	sidebar = function($){
		// Properties
		
		// Private Methods
		var init = function(){
			$("#toggle_sidebar").click( function(e){
				e.preventDefault();
				
				if( $("#page_body").hasClass("open_menu") )
				{
					$("#toggle_sidebar").fadeOut();
					$("#section_navigation").fadeOut('slow', function(){
						$("#main_content").animate( { marginLeft: 0 }, function(){
							$("#page_body").removeClass("open_menu").addClass("close_menu");
							$("#toggle_sidebar").fadeIn();
							$( window ).trigger('resize'); // Some of our components use window size, so fire this now
							ipb.Cookie.set("acp_sidebar", "closed", 1);
						});
					});
				}
				else
				{
					$("#toggle_sidebar").fadeOut();
					$("#main_content").animate( { marginLeft: 195 }, function(){
						$("#section_navigation").hide().fadeIn( 'slow', function(){
							$("#page_body").removeClass("close_menu").addClass("open_menu");
							$("#toggle_sidebar").slideDown();
							$( window ).trigger('resize'); // Some of our components use window size, so fire this now
							ipb.Cookie.set("acp_sidebar", "open", 1);
						} );
					} );					
				}
				
				return false;
			} );
		};
		
		// Public methods
		return {
			init: init
		}
	}($);
	
	livesearch = function($){
		// Options object
		var options = {
			showDelay: 400,
			hideDelay: 800,
			minChars: 3,
			lastPress: 0
		};
		
		// Properties
		var	$elem = {}, $results = {}, $modal = {},
			defaultText = '',
			lastValue = '',
			timeouts = {},
			documentClickHandler = false,
			cache = {},
			self = this;
					
		// Templates
		var	templates = {
			wrap: "<ul>[items]</ul>",
			item: "<li><a href='[url]'><strong>[name]</strong></a></li>",
			none: "<p class='ls_none'>" + ipb.lang['livesearchnoresults'] + "</p>",
			locations: {
				members: "<li><a href='[url]'><img src='[img]' class='photo left' /> <strong>[name]</strong></a><br /><span class='desctext'>[extra]</span></li>",
				forums: "<li><a href='[url]'><strong>[name]</strong></a><br /><span class='desctext'>[extra]</span></li>",
				nexus: "<li><img src='[img]' style='height: 16px; width: 16px; border: 0px' /> <a href='[url]'><strong>[name]</strong></a></li>"
			}
		};
		
		// Private methods
		var init = function(){
			$elem = $("#acpSearchKeyword");
			$results = $("#live_search_results");
			$modal = $("#modal");
			
			defaultText = $elem.val();
			
			$elem.focus( handleFocus ).blur( handleBlur ).attr("autocomplete", "off");
			
			$elem.bind( 'keydown', function() { options.lastPress = new Date().getTime(); } );
			
			// Attach live event to sections that are NOT disabled and NOT active; make Overview active
			if ( ipb.vars['app_url'].substring( ipb.vars['app_url'].length - 10, ipb.vars['app_url'].length - 5 ) == 'nexus' )
			{
				$("#ls_sections li:not(.disabled):not(.active)", $results).live('click', switchResultsTab).siblings("#ls_nexus").addClass('active');
				$("#ls_results > div", $results).hide().siblings("#ls_nexus_panel").show();
				$(".count", $results).hide();
			}
			else
			{		
				$("#ls_sections li:not(.disabled):not(.active)", $results).live('click', switchResultsTab).siblings("#ls_overview").addClass('active');
				$("#ls_results > div", $results).hide().siblings("#ls_overview_panel").show();
				$(".count", $results).hide();
			}
		},
		
		runSearch = function(){
			// Keep it going
			timeouts['show'] = setTimeout( runSearch, options.showDelay );

			/* trim() is not chainable, throws error in IE8
				@link http://stackoverflow.com/questions/3439316/ie8-and-jquerys-trim */
			var curVal  = $.trim( $elem.val() );
			var timeNow = new Date().getTime();
			
			if ( options.lastPress && timeNow && ( ( timeNow - options.lastPress ) < 800 ) )
			{
				return;
			}
			
			if( curVal.length < options.minChars ){
				hideResultsPanel();
				documentClick(false);
				return;
			}
			
			showResultsPanel(); 		// Display the modal and results panel
			documentClick(true); 		// Set the document click event
			if( curVal == lastValue ){	return; } // If it's the same as last time, skip
			fetchResults( curVal ); 	// Grab the results and process
			lastValue = curVal; 		// Update last value to be current value
		},
		
		fetchResults = function( curVal ){
			
			Debug.write( '--' + curVal );
			
			if( cache[ curVal ] ){
				Debug.write("Loading from cache");
				parseResults( cache[ curVal ] );
			}
			else
			{
				$('#acp_loading').show();
								
				$.ajax( {
					url: ipb.vars['app_url'].replace( /&amp;/g, '&' ) + "app=core&module=ajax&section=livesearch&do=search&secure_key=" + ipb.vars['md5_hash'],
					type: 'POST',
					data: { 
						'search_term': curVal
					},
					success: function(data){
						if( data['error'] )
						{
							alert( data['error'] );
							if( data["__session__expired__log__out__"] ){
								window.location.reload();
							}
						}
						else
						{
							cache[ curVal ] = data;
							parseResults( data );
						}
						$('#acp_loading').hide();
					},
					error: function(){
						alert( ipb.lang['session_timed_out'] );
						window.location.reload();
					}
				});	
			}
		},
		
		parseResults = function( data ){
			
			if( $.undefined( data ) ){
				//Debug.error( "Invalid JSON" );
				return;
			}
			
			// Disable all sections to start with
			$("#ls_sections li").not("#ls_marketplace").addClass("disabled").find(".count").hide();
			
			// If there are per-group settings, add to settings tab
			if( !$.undefined( data['groupLangs'] ) && data['groupLangs'] )
			{
				var _langs	= new Array();
				_langs['name']	= ipb.lang['group_settings_match'];
				_langs['url']	= ipb.vars['base_url'] + 'app=members&module=groups';
				
				data['settings'].unshift( _langs );
			}
			
			var total = 0;
			
			$.each( data, function( location, results ){
				if( $.isArray( results ) && results.size() )
				{
					var size = results.size();
					var html = '';
										
					// Any special template to use?
					_template = ( !$.undefined( templates.locations[ location ] ) ) ? templates.locations[ location ] : templates.item;
					
					// Update results panel
					$.each( results, function( k, v ){
						html += _template.replace("[name]", v['name']).replace("[url]", v['url']).replace("[img]", v['img']).replace("[extra]", v['extra']);
						total++;
					});
				
					// Build wrapper
					html = templates.wrap.replace("[items]", html);
					$("#ls_" + location + "_panel").html( html );
					
					// Make members a double-column
					if( location == 'members' ){
						$("#ls_" + location + "_panel").find("li:even").addClass("even").end().find("li:odd").addClass("odd");
					}
					
					// Update section list count
					$("#ls_" + location ).removeClass("disabled").find(".count").html( size ).fadeIn('fast');
				}
				else {
					$("#ls_" + location + "_panel").html(''); //templates.none
				}
			});
			
			if( total ){
				$("#ls_no_results").hide();
			} else {
				$("#ls_no_results").show();
			}
		},
		
		switchResultsTab = function( e ){
			// Switch highlighted tab
			$(e.target).addClass("active").siblings("li").not(e.target).removeClass("active");
			$("#ls_results > div", $results)
				.not( "#" + e.target.id + "_panel" ).fadeOut()
				.siblings( "#" + e.target.id + "_panel" ).fadeIn();
		},
		
		handleDocumentClick = function(e){
			if( $(e.target).closest("#live_search_results, #header").size() == 0 )
			{
				clearTimeout( timeouts['show'] );
				documentClick( false );
				hideResultsPanel();
			}
		},
		
		documentClick = function( type ){
			if( type == true ){
				// If the user clicks elsewhere in the document, cancel search
				if( !documentClickHandler ){
					$("body").bind( "click.livesearch", handleDocumentClick );
					documentClickHandler = true;
				}
			} else {
				if( documentClickHandler ){
					$("body").unbind( "click.livesearch" );
					documentClickHandler = false;
				}
			}
		},
		
		showResultsPanel = function(){
			$modal.not(":visible").fadeIn('fast');
			$results.not(":visible").fadeIn('slow');
		},
		hideResultsPanel = function(){
			$modal.fadeOut('slow');
			$results.fadeOut('fast');
		},
		
		handleFocus = function(e){
			Debug.write("focus!");
			if( $elem.hasClass("inactive") ){
				$elem.val('').removeClass('inactive');
			}
			
			clearTimeout( timeouts['show'] );
			timeouts['show'] = setTimeout( runSearch, options.showDelay );
		},
		
		handleBlur = function(e){
			if( $elem.val() == '' ){
				clearTimeout( timeouts['show'] );
				$elem.val( defaultText ).addClass('inactive');
			}
		};
		
		// Public methods
		return {
			init: init
		}
	}(jQuery);
	
	/****************************************/
	/* Changes checkboxes into button toggles */
	/****************************************/
	$.fn.ipsToggler = function(options) {
		var $$ = $(this);

		//----------------------------------------------
		// Prepares this checkbox
		//----------------------------------------------
		var setup = function( elem ){
			var newElem = $('<span/>')
					.attr('id', elem.id + '_toggler')
					.addClass( options.baseClass )
					.html('<span>Toggle</span>')
					.data('original', elem );

			if( elem.checked ){
				newElem
					.addClass( options.on.cssClass )
					.attr('title', options.on.title );
			} else {
				newElem
					.addClass( options.off.cssClass )
					.attr('title', options.off.title );
			}

			$( elem )
				.hide()
				.before( newElem );

			$( newElem )
				.click( function(e){
					if( $( this ).hasClass('on') ){
						$( elem ).removeAttr('checked');
						$( this )
							.removeClass('on')
							.addClass('off')
							.attr('title', options.off.title);
					} else {
						$( elem ).attr('checked', 'checked');
						$( this )
							.removeClass('off')
							.addClass('on')
							.attr('title', options.off.title);
					}
				});	
		},

		//----------------------------------------------
		// Event handler for the new toggle element
		//----------------------------------------------
		toggle = function(e){

		};

		return this.each(function(){
			setup( this );
		});
	};

	/****************************************/
	/* Wizard bar (step by step)			*/
	/****************************************/
	$.fn.ipsWizard = function(options) {
		var $$ = $(this);
		var defaults = {
			nextSelector: "#next",
			prevSelector: "#prev",
			wrapSelector: "#ipsSteps_wrapper",
			finishSelector: "#finish",
			allowJumping: false,
			allowGoBack: false
		},
		options = $.extend( defaults, options );
		
		var setJumps = function( elem ){
			$$.find("li").removeClass("clickable");
			
			if( options['allowJumping'] ){
				$$.find("li").not(".steps_disabled, .steps_active").addClass("clickable");
			}
						
			if( options['allowGoBack'] ){
				$$.find("li.steps_active").prevAll().not(".steps_disabled").addClass("clickable");
			}
			
			// Show/hide Next/Prev/Finish
			if( $$.find("li:last").hasClass("steps_active") ){
				$( options['nextSelector'] ).hide();
				$( options['prevSelector'] + "," + options['finishSelector'] ).show();
			} else if( $$.find("li:first").hasClass("steps_active") ) {
				$( options['nextSelector'] ).show();
				$( options['finishSelector'] + "," + options['prevSelector'] ).hide();
			} else {
				$( options['nextSelector'] + "," + options['prevSelector'] ).show()
				$( options['finishSelector'] ).hide();
			}
		},
		nextItem = function(e){
			// Find active item and get next item
			var next = $$.find("li.steps_active").nextAll(":not(.steps_disabled)")[0];
			if( $.undefined( next ) ){ return; }
			showItem( next.id );
		},
		prevItem = function(e){
			var prev = $$.find("li.steps_active").prevAll(":not(.steps_disabled)")[0];
			if( $.undefined( prev ) ){ return; }
			showItem( prev.id );
		},
		clickStep = function(e){
			showItem( $(e.target).closest('li')[0].id );
		},
		backToTop = function(e){
			$('html,body').animate( {'scrollTop': ( $(options['wrapSelector']).offset().top - 80 ) } );
		},
		focusFirst = function(activeID){
			try {
				$('#'+activeID+'_content :input:first').focus();
			} catch(err){ Debug.write( err ); }
		},
		showItem = function( id ){
			if( id === false ){ return false; }
			// Find active
			var activeID = $$.find("li.steps_active:first")[0].id;
			if( activeID == id ){ return; }
			
			var oldH = $('#'+activeID+'_content').height();
			var newH = $('#'+id+'_content').height();
			
			// Set up some CSS
			$( options['wrapSelector'] ).height( oldH );
			
			$('#'+activeID+'_content').addClass("step_content_animating");
			$('#'+id+'_content').addClass("step_content_animating");
			
			// Switch item
			$( "#" + activeID + '_content' ).fadeOut('slow', function(){
				$( options['wrapSelector'] ).animate( { height: newH }, 'fast', function(){
					$( "#" + id + '_content' ).fadeIn('slow', function(){
						$( options['wrapSelector'] ).css("height", "auto");
						$('#'+activeID+'_content').removeClass("step_content_animating");
						$('#'+id+'_content').removeClass("step_content_animating");
					});
				});
			});
			
			// Update step bar
			$("#"+id).siblings()
				.removeClass("steps_active")
				.removeClass("steps_done")
				.end()
				.addClass("steps_active")
				.prevAll(":not(.steps_active")
				.addClass("steps_done");
			
			backToTop();
			focusFirst( activeID );
			setJumps();
		};
		
		return this.each(function(){
			if( $.undefined( options['currentStep'] ) ){ }
			
			if( !options['allowJumping'] && !options['allowGoBack'] ){
				$( options['prevSelector'] ).remove();
			}
			
			// Set initial clickable status
			setJumps();
			
			// Set event handler
			$(".clickable", this).live('click', clickStep);
			$( options['nextSelector'] ).click( nextItem );
			$( options['prevSelector'] ).click( prevItem );	
		});
	};
	
	/****************************************/
	/* Multi-level drop down menu, with 	*/
	/* keyboard navigation 					*/
	/****************************************/
	$.fn.ipsMultiMenu = function(options) {
		var defaults = {
			useHoverIntent: true,
		},
		options = $.extend( defaults, options ),
		arrow = $("<span class='menu_arrow'>&raquo;</span>"),
		timeouts = {};

		// Private methods
		//----------------------------------------------
		// Mouse enter event
		//----------------------------------------------
		var mOver = function(e){
			clearTimeout( timeouts['menu'] );
			
			if( !$(this).parent("ul").hasClass('root') && $(this).children('ul').length ){
				positionSubmenu( $(this) );
			}

			// Hide any submenus belonging to siblings, then show the submenu for this item
			$(this).children('ul:first').fadeIn('fast').end().siblings().find('ul').fadeOut('fast');
		},

		//----------------------------------------------
		// Mouse leave event
		//----------------------------------------------
		mOut = function(e){
			var self = this;
			timeouts['menu'] = setTimeout( 
				function(){ 
					$(self).children('ul:first').fadeOut('fast');
				}, 800
			);
		},

		//----------------------------------------------
		// Positions a menu according to its parent
		//----------------------------------------------
		positionSubmenu = function( menu ){
			var w = ( $(menu).outerWidth() - 5 ),
					children = $(menu).children('ul:first'),
					docSize = $(window).width();

			children.css('left', w).css('top', -5);
					
			// jQuery can't get offset from hidden elements, so show, measure, then hide.
			children.show();
			var childW = children.outerWidth();
			var realLeft = children.offset().left + childW + 5;
			children.hide();
			
			if( realLeft >= docSize ){
				children.css('left', 5 - childW );
			}
		},

		//----------------------------------------------
		// Main entry point for keydown event
		//----------------------------------------------
		kPress = function( e, menu ){
			e.preventDefault();

			var active = findActiveItem( menu );

			switch( e.keyCode ){
				case 37: // Left
					return move.left( $(active) );
				break;
				case 39: // Right
					return move.right( $(active) );
				break;
				case 38: // Up
					return move.up( $(active) );
				break;
				case 40: // Down
					return move.down( $(active) );
				break;
				case 32: // Space
				case 13: // Enter
					return move.select( $(active) );
				break;
			}
		},

		move = {
			//----------------------------------------------
			// Handles selecting an item
			//----------------------------------------------
			select: function( active )
			{
				if( active.children('a').length ){
					window.location = active.children('a:first').attr('href');
				}
				else if( active.children('ul') ){
					window.location = active.children('ul').find('a:first').attr('href');
				}
				return false;
			},

			//----------------------------------------------
			// Handles LEFT key
			//----------------------------------------------
			left: function( active ){
				
				// If we're in a sub menu, pressing left should close the sub menu
				if( !active.parent('ul').is('.root') && !active.parent('ul').is('.root > li > ul') ){
					active
						.parent('ul')
							.find('.current')
								.removeClass('current')
								.end()
							.hide();
				}
				// If we're in an app menu, and there's a previous app, we'll show that one
				else if( active.parent('ul').is('.root > li > ul') && active.parent('ul').parent('li').prev().length ) {
					active
						.children('ul')
							.hide()
							.end()
						.removeClass('current')
						.prev()
							.addClass('current')
							.children('ul:first')
								.show();
										
				}
				return false;

			},

			//----------------------------------------------
			// Handles RIGHT key
			//----------------------------------------------
			right: function( active ){
				
				// If what we have highlighed has a sub menu, show that submenu 
				if( !active.parent().is('.root') && active.has('ul:hidden').length ){
					positionSubmenu( active );
					active.children('ul').show().find('li:first').addClass('current');
				} 
				else {
					// If we've got something highlighted, and it's in a root app menu, we
					// close the menu and show the next root app menu instead
					if( active.parent('ul').is('.root > li > ul') && active.parent('ul').parent('li').next().length ){		
						active
							.parent('ul')
								.find('.current')
									.removeClass('current')
									.hide()
								.end()
								.hide()
							.parent('li')
								.removeClass('current')
								.next()
									.addClass('current')
									.children('ul:first')
										.show();
					}
					// If no menu is open but a root app menu is highlighted,
					// we'll show the next root app
					else if( active.parent('ul').is('.root') && active.next().length ){
						active
							.children('ul')
								.hide()
								.end()
							.removeClass('current')
							.next()
								.addClass('current')
								.children('ul:first')
									.show();
					}
				}
				return false;
			},

			//----------------------------------------------
			// Handles UP key
			//----------------------------------------------
			up: function( active ){
				// Go up an item, if we can
				if( active.prev().length ){
					active.removeClass('current').prev().addClass('current');
				} else {
					// If not, and this is a root app menu, hide the menu and
					// make the root item active
					if( active.parent().is('.root > li > ul') ){
						active
							.removeClass('current')
							.parent()
								.hide();
					}
				}
				return false;
			},

			//----------------------------------------------
			// Handles DOWN key
			//----------------------------------------------
			down: function( active ){
				// If this is a root app menu...
				if( active.is('.root > li') ){
					// ...check whether the menu is already shown, and select the first li
					if( active.children('ul:first').is(':visible') ){
						active.find('ul:first > li:first').addClass('current');
					} else {
						// ...if it isn't visible, show it
						active.find('>ul').show();
					}
				} else {
					if( active.next().length ){
						active.removeClass('current').next().addClass('current');
					}
				}
				return false;
			}
		},

		//----------------------------------------------
		// Finds the deepest currently active menu item
		//----------------------------------------------
		findActiveItem = function( menu ){
			return $('.current:visible', menu).last();
		},

		//----------------------------------------------
		// Hover event to highlight an item
		//----------------------------------------------
		highlight = function( e ){
			$(this)	
				.addClass('current')
				.siblings()
					.removeClass('current')
					.parents('li')
						.addClass('current')
						.siblings()
							.find('li')
							.removeClass('current');
		},

		//----------------------------------------------
		// Unhighlights an item
		//----------------------------------------------
		unhighlight = function( e ){
			$(this).removeClass('current');
		};

		//----------------------------------------------
		// Main item loop
		//----------------------------------------------
		return this.each( function(elem){
			var self = this;

			$(self).addClass('root');
			$("ul", self).hide();

			$("li", self).hover( highlight, unhighlight );

			$("li", self)[ ( $.fn.hoverIntent && options.useHoverIntent ) ? 'hoverIntent' : 'hover' ]( mOver, mOut )
				.filter(':has(ul)')
				.not('.root > li')
					.addClass('has_sub')
					.children("a,span")
						.append( arrow.clone() );

			// Prepare event for keyboard navigation
			$(document).keydown( function(e){
				if( options.keyboardTrigger ){
					// If the user presses the trigger key & ctrl & alt
					if( e.keyCode == options.keyboardTrigger && e.ctrlKey && e.altKey ){
						// Show the first menu if nothing is open
						if( !$('.current', self).length ){
							$('.root > li:first')
								.addClass('current')
								.children('ul:first')
									.show()
									.children('li:first')
										.addClass('current');
						} 
						// or close everything if something was open
						else {
							$( self )
								.find('.current')
									.removeClass('current')
									.end()
								.find('ul')
									.hide();
						}
					}	
				}

				// Handle navigational keypresses
				if( $('.current', self).length ){
					kPress( e, self );
				}
			});
		})
	};
	
	/****************************************/
	/* Sortable								*/
	/* Piggybacks on jQuery ui sortable, 	*/
	/* but sets up some IPS defaults for it	*/
	/****************************************/
	$.fn.ipsSortable = function(type, options) {
		var defaults = {
			'table': {
				handle: 'td .draghandle',
				items: 'tr.isDraggable',
				opacity: 0.6,
				axis: 'y',
				revert: true,
				forceHelperSize: true,
				helper: 'clone',
				sendType: 'get',
				pluralize: true
			},
			'multidimensional': {
				handle: '.draghandle',
				items: 'div.isDraggable',
				opacity: 0.6,
				axis: 'y',
				revert: true,
				forceHelperSize: true,
				helper: 'clone',
				sendType: 'get',
				pluralize: true
			},
			'list': {
				// todo
			},
			'custom': {  } //todo
		};
			
		return this.each(function(){
			var x = ( $.undefined( defaults[type] ) ) ? defaults['custom'] : defaults[type];
			var o = $.extend( x, options );
			
			// If there's no function defined already, and a URL is specified, set up
			// an ajax call
			if( $.undefined( o['update'] ) && !$.undefined( o['url'] ) )
			{ 
				o['update'] = function( e, ui )
				{
					if ( ! Object.isUndefined( o['callBackUrlProcess'] ) )
					{
						o['url'] = o['callBackUrlProcess']( o['url'] );
					}
				
					var serial = $(this).sortable("serialize", ( o['serializeOptions'] || {} ) );
					
					$.ajax( {
						url: o['url'].replace( /&amp;/g, '&' ),
						type: o['sendType'],
						data: serial,
						processData: false,
						success: function(data){
							if( data['error'] )
							{
								alert( data['error'] );
								if( data["__session__expired__log__out__"] ){
									window.location.reload();
								}
							}
							else
							{
								Debug.write("Sortable update successfully posted");
							}

							if ( ! Object.isUndefined( o['postUpdateProcess'] ) )
							{
								o['postUpdateProcess']( data );
							}
						},
						error: function(){
							alert( ipb.lang['session_timed_out'] );
							window.location.reload();
						}
					});
				}
			}
			
			$( this ).sortable( o );
		});
		
	};
	
	/****************************************/
	/* Tab bar								*/
	/****************************************/
	$.fn.ipsTabBar = function(options) {
		var defaults = {
				tabWrap: "#tabContent",
				scrollStep: 130
		 	},
			options = $.extend( defaults, options ),
			getTabID = function(tab){
				return tab.replace("tab_", "");
			},
			switchTab = function(e){
				var $container = $(this).parent("ul");
				var curID = getTabID( $container.find("li.active")[0].id );
				var newID = getTabID( this.id );
				
				if( $(this).hasClass("active") ){
					return;
				}
				
				// Switch highlighted tab
				$(this).addClass("active")
					.siblings("li")
					.not(this)
					.removeClass("active");

				// Animate tab content
				/*$("#tab_" + curID + "_content").fadeOut();
				$( options['tabWrap'] ).animate();
				$("#tab_" + newID + "_content").fadeIn();*/

				$("#tab_" + curID + "_content").hide();
				$( options['tabWrap'] ).animate();
				$("#tab_" + newID + "_content").show();
			},
			scrollTabs = function(e){
				var tar = $(e.target);
				var ul = tar.siblings("ul");
				
				// For both scrollers, check the maximum distance we can scroll
				// before we get to the end of the bar. Use that distance if it's
				// less than options.scrollStep.
				if( $(tar).hasClass('tab_right') )
				{
					var l = parseInt( $(ul).css('left') ) || 0;
					var w = $(ul).width();
					var wrap = $(ul).parent(".ipsTabBar").width();
					var diff = ( l + w ) - wrap;
					var move = ( diff > options.scrollStep ) ? options.scrollStep : diff;
									
					$( ul ).animate({ left: "-=" + move}, function(){
						checkTabToggle( tar.parent(".ipsTabBar") );
					});
				}
				else if( $(tar).hasClass('tab_left') )
				{
					var l = parseInt( $(ul).css('left') ) || 0;
					var move = ( (l*-1) > options.scrollStep ) ? options.scrollStep : (l*-1);
					
					$( ul ).animate({ left: "+=" + move}, function(){
						checkTabToggle( tar.parent(".ipsTabBar") );
					});
				}
			},
			checkTabToggle = function( $this ){
				var	ul = $("ul", $this),
					w = ul.outerWidth(),
					left = parseInt( ul.css('left') ) || 0,
					pos = parseInt( $($this).css('left') ) || 0,
					wrap = $($this).width();
				
				if( ( left + w ) > wrap ){
					$($this).addClass("with_right").find(".tab_right").fadeIn('fast');
				} else {
					$($this).removeClass("with_right").find(".tab_right").fadeOut('fast');
				}
				
				if( left < 0 ){
					$($this).addClass("with_left").find(".tab_left").fadeIn('fast');
				} else {
					$($this).removeClass("with_left").find(".tab_left").fadeOut('fast');
				}
			};
			
		return this.each(function(){
			
			// Hide the scrollers & set event
			$(".tab_left,.tab_right", this).hide().mousedown( scrollTabs );
			
			var tabSetup = function( tab, id ){
				$(tab).addClass('active');
				$( options['tabWrap'] + " > div").not("#tab_" + id + "_content").hide(); // Hide all except active
				//$( options['tabWrap'] ).height( $("#tab_" + id + "_content").innerHeight() ); // Set tab wrap height to active pane height
			}
			
			// Which default tab?
			if( $.undefined( options['defaultTab'] ) ){
				tabSetup( $("li:first", this), getTabID( $("li:first", this).attr('id') ) );
			} else {
				tabSetup( "#" + options['defaultTab'], getTabID( options['defaultTab'] ) );
			}
			
			// Event for tab switching
			$('li', this).click( switchTab );
			
			var $this = this;
			
			// Initial check for showing toggles
			checkTabToggle( $this );
						
			// Check on window resize too
			$(window).resize( function(){
				checkTabToggle( $this );
			});
			
		});

	};
	
}(jQuery));

/**
* hoverIntent r6 // 2011.02.26 // jQuery 1.5.1+
* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
* 
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Brian Cherne brian(at)cherne(dot)net
*/
(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev])}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev])};var handleHover=function(e){var ev=jQuery.extend({},e);var ob=this;if(ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t)}if(e.type=="mouseenter"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if(ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob)},cfg.interval)}}else{$(ob).unbind("mousemove",track);if(ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob)},cfg.timeout)}}};return this.bind('mouseenter',handleHover).bind('mouseleave',handleHover)}})(jQuery);

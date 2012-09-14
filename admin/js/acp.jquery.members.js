// ************************************ //
// Member List							//
// ************************************ //
var memberlist = {};

(function($){
	
	memberlist = function($){
		//-------------------------------------
		// PROPERTIES
		var currentOptions = {
			st: 0,
			type: 'all',
			string: '',
			order_by: 'joined',
			order_direction: 'desc'
		},
		coreOptions = ['st', 'type', 'string', 'order_by', 'order_direction'],
		request_url = '',
		table = null,
		isAdvSearching = false,
		searchTimeout = null,
		lastVal = '',
		resultsCache = {};
		
		//-------------------------------------
		// PUBLIC METHODS	
		var init = function( url, defaultType ){
			request_url	= url;
			table		= $("#member_list");
			defaultType	= defaultType ? defaultType : 'all';
		
			// Set up events
			$("#member_types a").click( toggleType ); 								// Toggle bar
			$("#member_search").focus( focusSearch ).blur( blurSearch );			// Search box
			$(".sortable", table).click( sortTable );								// Column headers
			$(".member_action", table).live('click', doAction );					// Row action
			$(".mass_action", table).live('click', doMassAction );					// Mass action
			$("#mfooter_spam .spam_action").live('click', doSpamAction);			// Spam action
			$(".check_all", table).live('click', checkAll );						// "Check All"
			$("#member_results input:checkbox").live("click", checkOne);			// Check one
			$(".pagination a").live('click', hijackPagination ); 					// Hijack navigation
			$("#advanced_member_search").click( showAdvancedSearch );				// Advanced search
			$("#memberListForm").submit( function(e){ e.preventDefault(); } );		// Stop form submission
			
			prepareAdvancedSearch();
			preparePruneAndMove();
			
			if( window.location.hash ){
				parseHash();
			} else {
				//loadDefaults();
			}
			
			// Default type
			if( defaultType )
			{
				currentOptions['type']	= defaultType;
				
				$("#member_types li").removeClass("active");
				$('li[data-type|="' + defaultType + '"]').addClass("active");

				if( $("#mheader_" + defaultType + ":hidden").size() )
				{
					$(".member_column_titles").hide();
					$(".sortable", table).removeClass("active");
					$("#mheader_" + defaultType )
						.show()
						.find('th[data-key="' + currentOptions['order_by'] + '"]')
							.addClass("active")
							.addClass( currentOptions['order_direction'] );
					
				}

				if( !$("#mfooter_" + defaultType + ":visible").size() )
				{
					$("tfoot tr", table).hide();
					$("#mfooter_" + defaultType ).show();
				}
			}
			
			$("#members_loading").ajaxStart( function(){
				$(this).fadeIn('fast');
			}).ajaxStop( function(){
				$(this).fadeOut('fast');
			});
		},
		getOption = function(option){
			return currentOptions[ option ];
		},
		setOption = function( option, value ){
			currentOptions[ option ] = value;
		};
		
		//-------------------------------------
		// PRIVATE METHODS
		// Toggles the type (member, validating, spammer etc.)
		var toggleType = function(e)
		{
			e.preventDefault();
			// Ignore if already active
			if( $( e.target ).closest(".ipsActionButton.active").size() ){
				return;
			}
			// Make active
			$("#member_types li").removeClass("active");
			var key = $( e.target ).closest("li").addClass("active").data('type');
			
			// Send request
			fetchResults( { type: key } );
		},
		
		// Handler for sorting the table by column
		sortTable = function(e)
		{
			e.preventDefault();
			var td = $( e.target ).closest('.sortable');
			
			if( td.hasClass('active') ){
				if( td.hasClass('desc') ){
					td.removeClass('desc').addClass('asc');
					fetchResults( { order_direction: 'asc' } );
				} else {
					td.removeClass('asc').addClass('desc');
					fetchResults( { order_direction: 'desc' } );
				}
				return;
			}
			
			$(".sortable", table).removeClass("active");
			var key = td.addClass("active").addClass("desc").data('key');
			
			// Send request
			fetchResults( { order_by: key, order_direction: 'desc' } );
		},
		
		// Perform an action on a member
		doAction = function(e)
		{
			e.preventDefault();
			
			// Get the type and action
			var _do = currentOptions['type'];
			var _action = $( e.target ).data("action");
			if( $.undefined( _do ) || !_do || $.undefined( _action ) || !_action ){
				Debug.write( "No type, or no action."); return;
			}
			
			if( _action == 'delete' ){
				if( !confirm( ipb.lang['confirm_delete_single_mem'] ) ){
					return false;
				}
			}
			
			// Get the relevant member ID
			var mid = $( e.target ).closest("tr").data("mid");
			if( $.undefined( mid ) || !mid ){
				Debug.write( "No member ID found."); return;
			}
			
			if( _do == 'all' ){
				var url = request_url + "&do=do_" + _action + "&type=" + _do + "&mid_" + mid + "=1";
			} else {
				var url = request_url + "&do=do_" + _do + "&type=" + _action + "&mid_" + mid + "=1";
			}
			
			// Send the request
			$.ajax( {
				url: url.replace(/&amp;/g, '&'),
				type: 'get',
				success: function(data){
					if( data['ok'] ){
						if( _action == 'resend' )
						{
							alert( data['msg'] );
						}
						else
						{
							$( e.target ).closest(".member_controls")
								.addClass("success")
								.closest("tr")
									.fadeOut('slow', function(){
										$( this ).remove();
										checkForEmptyTable();
									});
									
							emptyCache();
						}				
					}
					else if( data['error'] ) {
						alert( data['error'] );
					}
					else {
						alert( ipb.lang['generic_ajax_error'] );
					}						
				}
			});			
		},
		
		doMassAction = function(e)
		{
			e.preventDefault();
			
			// Get the type and action
			var _do = currentOptions['type'];
			var _action = $( e.target ).data("action");
			if( $.undefined( _do ) || !_do || $.undefined( _action ) || !_action ){
				Debug.write( "No type, or no action."); return;
			}
			
			if( _action == 'delete' ){
				if( !confirm( ipb.lang['confirm_delete_multiple_mem'] ) ){
					return false;
				}
			}
			
			// Get all MIDs
			var mids = $("#member_results input:checked").map( function(){
				return "mid_" + $(this).closest("tr").data("mid") + "=1";
			}).get();
			
			if( mids == "" ){ return; }
			mids = mids.join("&");
						
			var url = request_url + "&do=do_" + _do + "&type=" + _action + "&" + mids;
			
			//Debug.write( url );
			$.ajax( {
				url: url.replace(/&amp;/g, '&'),
				type: 'get',
				success: function(data){
					if( data['ok'] ){
						$("#member_results input:checked").parents(".member_controls")
							.addClass("success")
							.closest("tr")
								.fadeOut('slow', function(){
									$( this ).remove();
									checkForEmptyTable();
								});	
								
						emptyCache();
						
						if ( _action == 'resend' )
						{
							ipb.global.showInlineNotification( ipb.lang['members_validation_resent'] );
						}
					}
					else if( data['error'] ) {
						alert( data['error'] );
					}
					else {
						alert( ipb.lang['generic_ajax_error'] );
					}
				}
			});
		},
		
		doSpamAction = function(e)
		{
			e.preventDefault();
			var action = $( e.target ).data("action");
			
			if( action == 'unspam' )
			{
				$("#s_initial").hide();
				$("#s_unspam_confirm").show();
				$("#s_unspam_yes, #s_unspam_no").click( function(e){
					doMassAction(e);
					$( this ).unbind();
					$("#s_unspam_confirm").hide();
					$("#s_initial").show();
				});
			}
			else if( action == 'ban' )
			{
				$("#s_initial").hide();
				$("#s_ban_confirm").show();
				$("#s_ban_yes, #s_ban_no").click( function(e){
					doMassAction(e);
					$( this ).unbind();
					$("#s_ban_confirm").hide();
					$("#s_initial").show();
				});
			}
			else
			{
				doMassAction(e);
			}
		},
		
		// Checks whether the table is empty after performing
		// member row actions
		checkForEmptyTable = function()
		{
			if( $("#member_results tr").size() == 0 )
			{
				$(".check_all").attr("checked", false);
				$("tfoot tr:visible", table).addClass("disabled");
				fetchResults( {}, true ); // force cache update
			}
		},
		
		// Handler for checking all checkboxes in the table
		checkAll = function(e)
		{
			$("#member_results input:checkbox").attr("checked", e.target.checked );
			
			// Enable the mass-action bar if necessary
			if( e.target.checked && $("#member_results tr").size() ){
				$("tfoot tr:visible").removeClass("disabled");
			}
			else {
				$("tfoot tr:visible").addClass("disabled");
			}
		},
		
		// Handler when a checkbox is clicked. Removes 'check all' check if necessary.
		checkOne = function(e)
		{
			if( !e.target.checked ){
				$(".check_all").attr("checked", false);
			}
			
			
			if( !$("#member_results input:not(:checked)").size() ){
				$(".check_all").attr("checked", true);
			} else {
				$(".check_all").attr("checked", false);
			}
			
			// Enable the mass-action bar if necessary
			if( $("#member_results tr").size() && $("#member_results input:checked").size() ){
				$("tfoot tr:visible").removeClass("disabled");
			}
			else {
				$("tfoot tr:visible").addClass("disabled");
			}
		},
		
		// Handler for pagination links
		hijackPagination = function(e)
		{
			e.preventDefault();
			
			// Find the st value
			try {
				var st = e.target.href.match(/\&st=([0-9]+)/)[1];
			} catch(err) {
				Debug.write( "No st value found" );
				return;
			}
			
			fetchResults( { st: st } );
		},
		
		// if there's a url hash, parse it and load results
		parseHash = function()
		{
			var hash = window.location.hash;
			var params = $H( getHashParams( hash ) );
			var options = {};
			
			if( !params.size() ){
				return;
			}
			
			params.each( function(h){
				options[ h.key ] = h.value;
			});
			
			if( !options['do_results'] ){
				return;
			}
			
			var o = Object.toQueryString( currentOptions );
			currentOptions = $.extend( currentOptions, options );

			// do stuff
			$.ajax( {
				url: ( request_url + "&" + o ).replace(/&amp;/g, '&'),
				type: 'post',
				data: options,
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
						$("#member_types").hide();
						$("#m_search_info").show().find("span").html( data['count'] );
						isAdvSearching = true;
						buildResults( data );
						Debug.write("Done");
					}
				}
			});	
		},
		
		getHashParams = function( hash )
		{
		    var hashParams = {};
		    var e,
		        a = /\+/g,  // Regex for replacing addition symbol with a space
		        r = /([^&;=]+)=?([^&;]*)/g,
		        d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
		        q = window.location.hash.substring(1);

		    while (e = r.exec(q))
		       hashParams[d(e[1])] = d(e[2]);

		    return hashParams;
		},
		
		// Empties the cache
		emptyCache = function()
		{
			resultsCache = {};			
		},
		
		// Fetches results from the server or cache using our params object
		fetchResults = function( options, force )
		{
			var newOpts = {};
			$.each( coreOptions, function( i, v ){
				newOpts[ v ] = currentOptions[ v ];
			});
			currentOptions = newOpts;
			
			var o = $.extend( {}, currentOptions, options );
			var hash = Object.toQueryString( o );
			
			//o['__update']	= 1;
			
			// Wipe cache?
			if( force ){
				resultsCache = {};
			}
			
			if( resultsCache[ hash ] )
			{
				currentOptions = o;
				buildResults( resultsCache[hash] );
			}
			else
			{			
				// do stuff
				$.ajax( {
					url: request_url.replace(/&amp;/g, '&'),
					type: 'get',
					data: o,
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
							currentOptions = o;
							buildResults( data );
							resultsCache[ hash ] = data;
							Debug.write("Done");
						}
					}
				});
			}
			
			// Set anchor for back button functionality
			window.location.hash = hash; /*.replace(/&/g, '/').replace(/\/amp;/g, '&amp;');*/
		},
		buildResults = function( results ){
			
			// Show headers
			if( $("#mheader_" + currentOptions['type'] + ":hidden").size() )
			{
				$(".member_column_titles").hide();
				$(".sortable", table).removeClass("active");
				$("#mheader_" + currentOptions['type'] )
					.show()
					.find('th[data-key="' + currentOptions['order_by'] + '"]')
						.addClass("active")
						.addClass( currentOptions['order_direction'] );
				
			}
			// Show footers
			if( !$("#mfooter_" + currentOptions['type'] + ":visible").size() )
			{
				$("tfoot tr", table).hide();
				$("#mfooter_" + currentOptions['type'] ).show();
			}
			
			// Build results and pagination	
			$("tbody#member_results", table).html( results['members'] );
			$(".pagination").replaceWith( results['pages'] );
			
			// Reset check all checkbox
			$(".check_all").attr("checked", false);
			
			// Check prune/move
			checkPruneAndMove( currentOptions['type'] );
		},
		focusSearch = function(e){
			clearTimeout( searchTimeout );
			searchTimeout = setTimeout( runSearch, 300 );
		},
		blurSearch = function(e){
			clearTimeout( searchTimeout );
		},
		runSearch = function(){
			searchTimeout = setTimeout( runSearch, 300 );
			var curVal = $("#member_search").val();
			
			if( curVal == lastVal || ( curVal.length > 0 && curVal.length < 3 ) ){
				return;
			}
			
			fetchResults( { string: curVal, st: 0 } );
			lastVal = curVal;
		},
		
		//-------------------------------------------
		// MASS PRUNE/MOVE
		//-------------------------------------------
		
		preparePruneAndMove = function(){
			$("#memberList__prune").click( {doType: 'prune'}, doPruneMove );
			$("#memberList__move").click( {doType: 'move'}, doPruneMove );
		},
			
		doPruneMove = function(e){
			e.preventDefault();
			
			if( $("#memberList__" + e.data.doType).hasClass("disabled") ){
				return;
			}
			
			var type = ( e.data.doType == 'prune' ) ? 'delete' : 'move';
			
			var o = currentOptions;
			delete o['type'];
			delete o['st'];
			delete o['order_by'];
			delete o['order_direction'];
			var hash = Object.toQueryString( o );
			
			window.location = $("#memberPruneMoveForm").attr('action') + '&' + hash + '&f_search_type=' + type;
		},
		checkPruneAndMove = function( type ){
			if( type != 'all' ){
				disablePruneMove();
				return;
			} 
			
			// Only enable if we're doing some kind of filtering
			if( currentOptions['string'] || isAdvSearching ){
				enablePruneMove();
			} else {
				disablePruneMove();
			}
		},		
		disablePruneMove = function(){
			$("#memberList__prune, #memberList__move").addClass("disabled");
		},
		enablePruneMove = function(){
			$("#memberList__prune, #memberList__move").removeClass("disabled");
		},
		
		//-------------------------------------------
		// ADVANCED SEARCH
		//-------------------------------------------
		
		prepareAdvancedSearch = function(e){
			if( !$("#modal").size() ){
				$("body").append( $("<div />", { id: "modal" } ).hide() );
			}			
			$("#modal").css( { height: $(document).height() } );
			// Submit event
			$("#do_advanced_search").click( doAdvancedSearch );
			$("#m_search_cancel").click( cancelAdvancedSearch );
			$("#close_adv_search, #modal").click( hideAdvancedSearch );
			
			// Resize event
			$(window).resize( function(){
				$("#modal:visible").css( { height: $(document).height() } );
			});
		},
		showAdvancedSearch = function(e){
			if(e){ e.preventDefault(); }
			$("#modal").fadeIn();
			$("#m_search_pane").fadeIn();
		},
		hideAdvancedSearch = function(e){
			if(e){ e.preventDefault(); }
			$("#m_search_pane").fadeOut();
			$("#modal").fadeOut();
		},
		cancelAdvancedSearch = function(e){
			if(e){ e.preventDefault(); }
			// Remove advanced search fields
			var newOpts = {};
			$.each( coreOptions, function( i, v ){
				newOpts[ v ] = currentOptions[ v ];
			});
			currentOptions = newOpts;
			
			currentOptions['reset_filters']	= 1;
			
			$("#m_search_info").hide();
			$('.information-box').hide();
			$("#member_types").show();
			isAdvSearching = false;
			fetchResults( currentOptions );			
		},
		doAdvancedSearch = function(e){
			e.preventDefault();
			// Get form values
			var vals = {};			
			$.map( $("#m_search_form").serializeArray(), function( k, i ){
				vals[ k['name'] ] = k['value'];
			});
			
			vals['st']			= 0;
			vals['__update']	= 1;
			
			// Remove quick search values
			$("#member_search").val('');
			currentOptions['string'] = '';
			
			var o = Object.toQueryString( currentOptions );
			currentOptions = $.extend( currentOptions, vals );
			
			// do stuff
			$.ajax( {
				url: ( request_url + "&" + o ).replace(/&amp;/g, '&'),
				type: 'post',
				data: vals,
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
						$("#member_types").hide();
						$("#m_search_info").show().find("span").html( data['count'] );
						isAdvSearching = true;
						buildResults( data );
						Debug.write("Done");
					}
				}
			});
			
			hideAdvancedSearch();
		};
		
		//-------------------------------------
		// Make public methods public
		return {
			init: init,
			getOption: getOption,
			setOption: setOption
		};		
	}($);
	
}(jQuery));
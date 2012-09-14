/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.mobile.nexus.js - IP.Nexus mobile code	*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/
	
var nexus = {
	
	guestCheckout: {
		
		init: function(){
			
			document.observe("dom:loaded", function(){
				if( $('choose_path') ){
					$('choose_path').show();
					$('checkout_register_form').hide();
					
					$('checkout_register').observe('click', function(e){
						$('choose_path').hide();
						$('checkout_register_form').show();						
					});
				}
			});
		}
	},
	
	/********************************************/
	/* General clientarea JS	 				*/
	/********************************************/
	clientArea: {
		
		init: function(){
			document.observe("dom:loaded", function(){
				if( $('nexus_nav_toggle') && $('nexus_nav_menu') )
				{
					$('nexus_nav_toggle').observe('click', function(e){
						Event.stop(e);
						if( $('nexus_nav_menu').visible() ){
							nexus.clientArea.hideNav(e);
						} else {
							nexus.clientArea.showNav(e);
						}						
					});
				}
			});			
		},
		
		showNav: function(e){
			var dims = $('nexus_nav_toggle').getDimensions();
			var pos = $('nexus_nav_toggle').cumulativeOffset();
			
			$('nexus_nav_menu').setStyle('width: ' + (dims['width']-2) + 'px; left: ' + pos['left'] + 'px; top: ' + ( pos['top'] + dims['height'] ) + 'px').show();
			$('nexus_nav_toggle').addClassName('active');
		},
		
		hideNav: function(e){
			$('nexus_nav_menu').hide();
			$('nexus_nav_toggle').removeClassName('active');
		}
	},
	
	/********************************************/
	/* Checkout process			 				*/
	/********************************************/
	checkout: {
		pay_url: '',
		totalMethods: 0,
		
		//----------------------------------------
		// Init the checkout page
		//----------------------------------------
		init: function( pay_url ){
			document.observe("dom:loaded", function(){
				if( pay_url ){
					nexus.checkout.pay_url = pay_url;
				}
				
				if( nexus.checkout.totalMethods == 1 ){
					$('payment_toggle').value = nexus.checkout.firstMethod;
					$('payment_toggle_wrap').hide();
					nexus.checkout.togglePayment(null);
				}
				
				if( $('payment_toggle') ){
					$('payment_toggle').observe('change', nexus.checkout.togglePayment);
				}
				
				$('do_pay').observe('submit', nexusCheckout.submitForm);
			});
		},
				
		//----------------------------------------
		// Init the credit card entry form
		//----------------------------------------
		initCreditCard: function(){
			if( $('cardonfile') ){				
				$('cardonfile').observe('click', function(e){
					if( this.checked ){
						$('full_options').hide();
						if( $('renew_options') ){
							$('renew_options').hide();
						}
					} else {
						$('full_options').show();
						if( $('renew_options') ){
							$('renew_options').show();
						}
					}					
				});
			}
			
			if( $('whats_this_cvv') ){
				$('whats_this_cvv').observe('click', function(e){
					this.hide();
					$('about_cvv').show();
				});
			}
		},
		
		//----------------------------------------
		// Fired when the payment method select is changed
		//----------------------------------------
		togglePayment: function(e){

			var val = $('payment_toggle').value;
			if( !val ){ return; }
			
			$('methods').addClassName('loading').select('.method').invoke('hide');
			
			new Ajax.Request( nexus.checkout.pay_url + "&method=" + val,
			{
				onSuccess: function(t){
					$('methods').removeClassName('loading');
					
					$('transid').value = t.responseJSON['transid'];
					
					if ( t.responseJSON['error'] ){
						alert( t.responseJSON['error'] );
						return;
					}
					
					// Check for payment form HTML
					if( t.responseJSON['html'] ){
						
						if( !$('method_pane_' + val) ){
							$('methods').insert( new Element('div', { id: 'method_pane_' + val }).addClassName('row').addClassName('method') );							
						}
						
						$('method_pane_' + val).update( t.responseJSON['html'] ).show();
					}
					else
					{
						if( !$('method_pane_none') ){
							$('methods').insert( new Element('div', { id: 'method_pane_none' }).addClassName('method').addClassName('no_messages') );
							//$('method_pane_none').update( ipb.lang['offline_payment'] );
						}
						
						$('method_pane_none').show();
					}
					
					// Check for URL
					if( t.responseJSON['formUrl'] )
					{
						$('do_pay').action = t.responseJSON['formUrl'];
					}
					
					// Check for JSON
					if( t.responseJSON['js'] )
					{
						eval( t.responseJSON['js'] );
					}
					
					// Does this method have a custom submit button?
					if( t.responseJSON['button'] ){
						if( !$('pay_button') ){
							$('confirm').insert( new Element('div', { id: 'pay_button'}) );
						}
						
						$('pay_button').update( t.responseJSON['button'] ).show();
						$('pay_submit').hide();
					}
					else
					{
						if( $('pay_button') ){
							$('pay_button').hide();
							$('pay_submit').show();
						}
					}					
				}
			});
			
		}		
	},
	
	/********************************************/
	/* Product information page 				*/
	/********************************************/
	viewItem: {
		
		packageID: 0,
		inStock: 0,
		productOptions: [],
		
		//----------------------------------------
		// Initialize product view
		//----------------------------------------
		init: function(){
			document.observe("dom:loaded", function(){
			
				if ( $('product_gallery') !== null )
				{
					$('product_gallery').on('click', 'span.thumb', nexus.viewItem.selectImage);
				}
				
				if( $('add_configure') )
				{
					$('add_configure').on('click', 'input:not(.disabled)', function(e, element){
						$(element).addClassName('disabled');
						mobileFilter(e, element);
					});
					
					$('add_configure_pane').down('.ipsFilterPane_close').observe('click', function(e){
						$('add_configure').removeClassName('disabled');
						closePane( e, $('add_configure_pane').down('.ipsFilterPane_close') );
					});
				}
				
				nexus.viewItem.optionCheck();
			});
		},
		
		//----------------------------------------
		// Handles stock/price check when an option is changed
		//----------------------------------------
		optionCheck: function()
		{
			var saveOptions = [];
			
			// Build an array of key/value pairs of options
			nexus.viewItem.productOptions.each( function(item){
				if( $('f_' + item) ){
					saveOptions.push( item + ':' + $('f_' + item).value );
				}
			});
			
			new Ajax.Request( ipb.vars['base_url'] + "app=nexus&module=ajax&section=optioncheck&secure_key=" + ipb.vars['secure_hash'],
			{
				evalJSON: 'force',
				parameters: {
		    		package: nexus.viewItem.packageID,
		    		options: saveOptions.join(',')
		    	},
				onSuccess: nexus.viewItem.handleOptionCheck
			});			
		},
		
		//----------------------------------------
		// Callback for option check
		//----------------------------------------
		handleOptionCheck: function(t)
		{
			if ( t.responseJSON['error'] ){
				alert( t.responseJSON['error'] );
				return;
			}
			
			if ( t.responseJSON['stock'] == 0 )
			{
				if ( nexus.viewItem.inStock ){
					nexus.viewItem.setOutOfStock( true );
				}				
				nexus.viewItem.inStock = false;
			}
			else
			{
				if( !nexus.viewItem.inStock ){
					nexus.viewItem.setOutOfStock( false );
				}
				nexus.viewItem.inStock = true;
			}
			
			// Update both price displays and renewal terms
			[ $('main_price'), $('secondary_price') ].invoke('update', t.responseJSON['base_price']);
			
			if( $('renew_terms') ){
				$('renew_terms').update( t.responseJSON['renew_terms'] );
			}
		},
		
		//----------------------------------------
		// Enables or disables the Cart button
		//----------------------------------------
		setOutOfStock: function( toSet )
		{
			if( toSet ){
				$('add_to_cart').addClassName('disabled').writeAttribute('disabled', 'disabled').value = ipb.lang['out_of_stock'];
			} else {
				$('add_to_cart').removeClassName('disabled').writeAttribute('disabled', null).value = ipb.lang['add_to_cart'];
			}			
		},
		
		//----------------------------------------
		// Product image toggle
		//----------------------------------------
		selectImage: function( e, element )
		{
			var imageid = $(element).readAttribute('data-imageid');
			var location = $(element).readAttribute('data-location');
			
			$('main_image').update( "<img src='" + ipb.vars['upload_url'] + "/" + location + "' alt='' />" );
			$$('.thumb').invoke('removeClassName', 'active');
			$('thumb_' + imageid).addClassName('active');
		}
				
	},
};

Element.addMethods( {
	
	expandify: function(element, options){
		
		options = Object.extend( {
			defaultTrigger: false
		}, options);
		
		var mainElem = element;
		var toggle = function(e, element){
			
			if( $(element).hasClassName('open') ){
				expanderClose( $(element) );
			}
			else
			{
				closeAll();
				expanderOpen( $(element) );
			}			
		},
		expanderClose = function( element ){
			$(element).removeClassName('open').addClassName('closed');
			$('expander_pane_' + $(element).readAttribute('data-pane')).hide();
		},
		expanderOpen = function( element ){
			$(element).removeClassName('closed').addClassName('open');
			$('expander_pane_' + $(element).readAttribute('data-pane')).show();			
		},
		closeAll = function(){
			$( mainElem ).select('.ipsExpanderList_trigger').each(function(elem){
				expanderClose( elem );
			});
		}
		
		// Close all triggers & panes
		closeAll();
		
		// Set event
		$( element ).on('click', '.ipsExpanderList_trigger', toggle);	
		
		if( options['defaultTrigger'] ){
			toggle( null, $$("[data-pane=" + options['defaultTrigger'] + "]").first() );
		}
	}
	
});
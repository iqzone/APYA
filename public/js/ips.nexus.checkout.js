/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.nexus.checkout.js						*/
/* (c) IPS, Inc 2012							*/
/* -------------------------------------------- */
/* Author: Mark Wade							*/
/************************************************/

var nexusCheckout = {
	
	submitForm: function( transid, submit )
	{	
		// Check we have selected a method
		if( $('payment_toggle').value == '--' )
		{
			alert( ipb.lang['checkout_nomethod'] );
			return false;
		}
		
		// Check we agreed to the terms
		if ( $('tac-checkbox') != null && $('tac-checkbox').checked == false )
		{
			alert("{$this->lang->words['checkout_accept_terms']}");
			return false;
		}
		
		// Disable the button
		if ( $('pay_submit') != null )
		{
			$('pay_submit').disabled = 'disabled';
		}
		
		// Call MaxMind
		if ( $('card_number') == null )
		{
			var bin = '';
		}
		else
		{
			var bin = $('card_number').value.replace( ' ', '' ).replace( '-', '' ).substr( 0, 6 );
		}
				
		new Ajax.Request( ipb.vars['base_url'] + "app=nexus&module=ajax&section=store&do=fraud&secure_key="+ipb.vars['secure_hash'],
		{
			asynchronous: false,
			method: 'post',
			parameters: {
				'transid': transid,
				'bin': bin,
			},
			onSuccess: function(t)
			{
				if ( t.responseJSON['status'] == 'fail' )
				{
					window.location = ipb.vars['base_url'] + "app=nexus&module=payments&section=receive&do=check&id=" + transid;
					return false;
				}
				else
				{
					if ( submit !== false )
					{
						$('do_pay').submit();
					}
					return true;
				}
			}
		});
	},
	
	stripeButtonClick: function( data, transid )
	{
		// Do normal checks
		if ( transid )
		{
			nexusCheckout.submitForm( transid, false );
		}
										
		// Only send to Stripe if we've not ticked the "Use Card on File" box and we're not doing something else
		if ( ( $('cardonfile') == null || $('cardonfile').value != 'on' ) && $('card_number').value.substr( 0, 4 ) != 'XXXX' && $('card_number').value.substr( 0, 4 ) != '' )
		{	
			// Fetch card data
			data.number = $('card_number').value;
			data.cvc = $('code').value;
			data.exp_month = $('exp_month').value;
			data.exp_year = $('exp_year').value;
						
			// Send to Stripe
			Stripe.createToken( data, nexusCheckout.stripeResponseHandler );
			
			// Don't allow the form to submit
			return false;
			
		}
		else
		{
			return false;
			$('do_pay').submit();
		}
	},
	
	stripeResponseHandler: function( status, response )
	{
		if ( response.error )
		{
			$('error_message_holder').innerHTML = response.error.message;
			$("pay_submit").disabled = "";
		}
		else
		{
			$('hidden_field').value = response['id'];
			$('do_pay').submit();
		}
	},
	
	changeCardMethod: function()
	{
		var key = $( 'card_method_' + $('cc_method').value ).readAttribute('data-extra');
		if ( key )
		{
			Stripe.setPublishableKey( key );
		}
		else
		{
			$('info_form').onsubmit = null;
		}
	},
};

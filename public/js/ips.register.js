/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.regiser.js - Registration code			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _register = window.IPBoard;

_register.prototype.register = {
	inSection: '',
	memberPartial: 0,
	allowed_chars: [ ],
	
	
	init: function()
	{
		Debug.write("Initializing ips.register.js");
		
		document.observe("dom:loaded", function(){
			if( ipb.register.inSection == 'mainform' ){
				ipb.register.initFormEvents();
			}
			
			if( ipb.register.inSection == 'completeReg' ){
				ipb.register.initCompleteRegFormEvents();
			}
			
			if( $('auto_time_offset') ){
				ipb.register.detectTimezone();
			}
		});
	},
	
	detectTimezone: function()
	{
		var rightNow = new Date();
		var jan1 = new Date(rightNow.getFullYear(), 0, 1, 0, 0, 0, 0);
		var temp = jan1.toGMTString();
		var jan2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
		var std_time_offset = (jan1 - jan2) / (1000 * 60 * 60);
		
		if( $('auto_time_offset') ){
			$('auto_time_offset').value = std_time_offset;
		}
		
		if( $('auto_dst') ){
			$('auto_dst').value = new Date().getDST();
		}
	},
	
	initCompleteRegFormEvents: function()
	{
		if( $('display_name') ){
			$('display_name').observe('blur', ipb.register.checkLoginName);
		}
		if( $('email_1') )
		{
			$('email_1').observe('blur', ipb.register.checkEmailValid);
		}
	},
	
	initFormEvents: function()
	{
		if( $('tou') && $('tou_link') )
		{
			ipb.register.touPopup = new ipb.Popup( 'reg_tou', { type: 'pane',
																initial: ipb.templates['registration_terms'].evaluate( { 'content': $('tou').value } ),
																stem: false,
																hideAtStart: true,
																modal: true,
																w: '600px' 
															});
			$('tou').remove();
			$('tou_link').observe('click', function(e){
				Event.stop(e);
				ipb.register.touPopup.show();
			});
		}
		
		if( $('login_name') ){
			$('login_name').observe('blur', ipb.register.checkLoginName);
		}
		if( $('display_name') ){
			$('display_name').observe('blur', ipb.register.checkLoginName);
		}
		if( $('email_1')  )
		{
			$('email_1').observe('blur', ipb.register.checkEmailValid);
		}
		if( $('password_1') && $('password_2') )
		{
			$('password_1').observe('blur', ipb.register.checkPasswordValid);
			$('password_2').observe('blur', ipb.register.checkPasswordMatch);
		}
	},
	
	checkPasswordValid: function(e)
	{
		if( $F('password_1').length < 3 )
		{
			ipb.register.showMessage( $('password_1'), ipb.lang['pass_too_short'], 1 );
			return;
		}
		if( $F('password_1').length > 32 )
		{
			ipb.register.showMessage( $('password_1'), ipb.lang['pass_too_long'], 1 );
			return;
		}
		
		ipb.register.removeMessage( $('password_1') );
	},
	
	checkPasswordMatch: function(e)
	{
		if( $F('password_1') != $F('password_2') )
		{
			ipb.register.showMessage( $('password_2'), ipb.lang['pass_doesnt_match'], 1 );
			return;
		}
		
		ipb.register.removeMessage( $('password_2') );
	},
	
	checkEmailValid: function(e)
	{
		if( !ipb.validate.email( $('email_1') ) )
		{
			ipb.register.showMessage( $( 'email_1' ), ipb.lang['invalid_email'], 1 );
			return;
		}
		
		ipb.register.removeMessage( $('email_1') );
		
		// Ajax to validate
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=register&amp;do=check-email-address";
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'post',
							parameters: {
								email: $F('email_1').encodeParam(),
								secure_key: ipb.vars['secure_hash']
							},
							onSuccess: function( t )
							{
								var _t = t.responseText.replace( /\s/, '', 'g' );
							
								switch( _t )
								{ 
									case 'found':
										ipb.register.showMessage( $('email_1'), ipb.lang['email_in_use'], 1 );
										break;
									case 'banned':
										ipb.register.showMessage( $('email_1'), ipb.lang['email_banned'], 1 );
										break;
									case 'notfound':
										ipb.register.showMessage( $('email_1'), ipb.lang['available'], 0 );
										break;
								}
							}
						});
								
	},
	
	checkLoginName: function(e)
	{
		elem = $( Event.element(e) );
		name = $F( elem );
		ajax = ( $( elem ).id == 'login_name' ) ? 'check-user-name' : 'check-display-name';
		
		// Check the simple stuff first
		if( name.blank() ){
			ipb.register.showMessage( $(elem), ipb.lang['is_required'], 1 );
			return;
		}
		if( name.length < 3 ){
			ipb.register.showMessage( $(elem), ipb.lang['too_short'], 1 );
			return;
		}
		if( name.length > ipb.register.nameMaxLength ){
			ipb.register.showMessage( $(elem), ipb.lang['too_long'], 1 );
			return;
		}
		
		if( ipb.register.allowed_chars != "" )
		{
			test = "^[" + RegExp.escape(allowed_chars) + "]+$";

			if ( name.match( test ) )
			{
				ipb.register.showMessage( $(elem), ipb.lang['invalid_chars'], 1 );
				return;
			}
		}
		
		// Check for availability
		name = name.replace("+", "&#43;");
		
		new Ajax.Request( 	ipb.vars['base_url'] + "app=core&module=ajax&section=register&do=" + ajax + '&mpid=' + ipb.register.memberPartial,
							{
								method: 'post',
								parameters: {
									name: escape( name ),
									secure_key: ipb.vars['secure_hash']
								},
								onSuccess: function(t)
								{
									var _t = t.responseText.replace( /\s/, '', 'g' );
									
									if( _t == 'found' )
									{
										ipb.register.showMessage( $(elem), ipb.lang['not_available'], 1 );
										return;
									}
									else if( _t == 'notfound' )
									{
										ipb.register.showMessage( $(elem), ipb.lang['available'], 0 );
										return;
									}
									else if( _t )
									{
										ipb.register.showMessage( $(elem), t.responseText, 1 );
										return;										
									}
									else
									{
										ipb.register.removeMessage( $(elem) );
									}
								}
							}
						);
						
		
	},
	
	showMessage: function( elem, msg, error )
	{
		if( !$( elem ) ){ return; }
		
		// Is there already an error?
		if( $( elem.id + "_msg" ) )
		{
			$( elem.id + "_msg" ).remove();
		}
		
		temp = ( error == 1 ) ? ipb.templates['error'] : ipb.templates['accept'];
		
		temp = temp.gsub( /\[msg\]/, msg ).gsub( /\[id\]/, elem.id );
		
		// Add after control
		$( elem ).insert( { after: temp } );
		try {
			new Effect.Appear( $( elem.id + "_msg" ), { duration: 0.4 } );
			
			if( error ){
				$( elem ).addClassName( 'error' ).removeClassName('accept');
			} else {
				$( elem ).addClassName( 'accept' ).removeClassName('error');
			}
		}
		catch(err){ Debug.write( err ); }
	},
	
	removeMessage: function( elem )
	{
		if( !$( elem ) ){ return; }
		
		if( $( elem.id + "_msg" ) )
		{
			$( elem.id + "_msg" ).remove();
		}
		
		$(elem).removeClassName( 'error' ).removeClassName('accept');
	}	
};

ipb.register.init();
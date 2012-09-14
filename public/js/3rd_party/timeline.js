/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


//No conflict jQuery
if (jQuery) {
    jQuery.noConflict();
}else {
    console.log('jQuery not loaded');
}

var tl = {};

tl = (function (){

    // options: an object containing configuration options for the singleton
    // e.g var options = { name: "test", pointX: 5};  
    function Status( options )  {

        // set options to the options supplied or an empty object if none are 
        // provided
        options = options || {};


        var timeout = options.timeout || 1000;
        var longMaxTimeout = options.longtimeout || 60000;
		function isValidURL(url)
		{
			var RegExp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
			
			if(RegExp.test(url)){
				return true;
			}else{
				return false;
			}
		}
        return {
            __timeout: timeout,
            __longMaxTimeout: longMaxTimeout,
            getDown: function($footer){
                $footer = jQuery('#pages li.next a[rel="next"]'),
                opts = {
                    offset: '100%'
                };

                $footer.waypoint(function(event, direction) {
                    $footer.waypoint('remove');
                    new Ajax.Request( jQuery('#pages li.next a[rel="next"]').attr('href') + '&user='+ipb.vars['showuserid'],
                    {
                                method: 'get',
                                onSuccess: function(t)
                                {
                                    
                                    jQuery('#container').delay(2000).append(tl.getInstance().base64_decode(t.responseJSON.html));
                                    jQuery('#pages li.next a[rel="next"]').attr('href', t.responseJSON.pages);
                                    if(t.responseJSON.html == 'CiAgICAgICAg') {
                                    	jQuery('#pages li.next a[rel="next"]').remove();
                                    }
                                    else {
	                                    $footer.waypoint(opts);
                                    }
                                    tl.getInstance().like();
                                    photos.getInstance().showphoto();
                                    jQuery('.___submit').unbind().click( function(e)
    				                {
    				                        id = jQuery(this).attr('id');
    				
    				                        $(id).observe( 'click', tl.getInstance().addReply.bindAsEventListener( this, id.replace( 'statusSubmit-', '' ) ) );
    				                        e.preventDefault();
    				                });
                                }

                    });
                }, opts);
            },
            post: function(){
                if ( $('status_update' ).value.length < 2 || $('status_update').value == ipb.lang['prof_update_default'] )
                {
                	alert("Debe escribir un mensaje");
                    return false;
                }

                var su_Twitter  = $('su_TwitterGlobal') && $('su_TwitterGlobal').checked ? 1  : 0;
                var su_Facebook = $('su_FacebookGlobal') && $('su_FacebookGlobal').checked ? 1 : 0;
                //Get Mentions
                var jsonMentions = '';
                jQuery('#status_update').mentionsInput('getMentions', function(data) {
                    var isLabel = false;
                	for(x in data) {
	                	isLabel = (parseInt(data[x]['id']) == parseInt(ipb.vars['showuserid']));
	                	if(isLabel)
	                		break;
                	}
                	if(!isLabel && ipb.vars['showuserid']){
	                	var lobject = parseInt(data.length);
	                	data[lobject] = {};
    	            	data[lobject]['id'] = ipb.vars['showuserid'];
	                	data[lobject]['type'] = ipb.vars['type_user'];
	                	data[lobject]['value'] = ipb.vars['username'];
                	}
                    jsonMentions = JSON.stringify(data);
                });
                attachlink = '';
                if(jQuery('#attach_content')){
	                attachlink = tl.getInstance().base64_encode(jQuery('#attach_content').text());
                }
                new Ajax.Request( ipb.vars['base_url'] + "app=portal&section=status&module=ajax&do=new&md5check=" + ipb.vars['secure_hash'] + "&skin_group=boards&return=json&smallSpace=1",
                {
                    method: 'post',
                    evalJSON: 'force',
                    parameters: {
                        content: $('status_update' ).value.encodeParam(),
                        su_Twitter: su_Twitter,
                        su_Facebook: su_Facebook,
                        mentions: jsonMentions,
                        sessionKey: jQuery('#sessionKey').val(),
                        postHTML: attachlink
                    },
                    onSuccess: function(t)
                    {
                        if( Object.isUndefined( t.responseJSON ) )
                        {
                            alert( ipb.lang['action_failed'] );
                            return;
                        }

                        if ( t.responseJSON['error'] )
                        {
                            alert( t.responseJSON['error'] );
                        }
                        else
                        {
                            try {
                                //jQuery('#statuses div:eq(0)').html(t.responseJSON['htmlStatus']);
                                jQuery('#status_update').val('');
                                jQuery('div.mentions-input-box div strong').html('');
                                
                                if( $( 'attachments' ) ) 
                                {
                                    jQuery( '#attachments li.complete' ).remove();
                                    jQuery( '#uploadBoxWrapParent' ).hide();
                                }
                                
                                if ( $('statuses') )
                                {
                                    jQuery('#statuses div:eq(0)').before(tl.getInstance().base64_decode(t.responseJSON['html']));

                                    // Showing latest only? 
                                    if ( ipb.status.myLatest )
                                    {
                                        if ( $('statusWrap-' + ipb.status.myLatest ) )
                                        {
                                            $('statusWrap-' + ipb.status.myLatest ).hide();
                                        }
                                    }
                                }
                                photos.getInstance().showphoto();
                                tl.getInstance().like();

                                ipb.menus.closeAll(e,true);
                                ipb.global.showInlineNotification( ipb.lang['status_updated'] );

                            }
                            catch(err)
                            {
                                Debug.error( 'Logging error: ' + err );
                            }
                        }
                    }
                });
                return this;
            },
            read: function(){
                var lastId = parseInt(jQuery('#statuses > div:eq(0)').data('id'));

                //Evitar que lastId se NaN
                if(isNaN(lastId))
                    lastId = 0;
                var countId = parseInt(jQuery('#news-bar-item').data('count-id'));
                jQuery.getJSON( ipb.vars['base_url'] + "app=portal&section=news&module=ajax&md5check=" + ipb.vars['secure_hash'], {
                    last: lastId,
                    countItems: countId,
                    user: ipb.vars['showuserid']
                }, function(data, textStatus, jqXHR){
                    if(data.news !== null)
                    {
                        if(data.news !== null && data.news > 0 && data.change) {
                            jQuery('#news-bar-item').html(data.news).data('count-id', data.news);
                            jQuery('.stream-item').show();
                            this.__timeout = timeout;
                            setTimeout('tl.getInstance().read()', this.__timeout);
                        }
                    }
                });
                if(this.__timeout >= this.__longMaxTimeout)
                    this.__timeout = timeout;
                else
                    this.__timeout *= 2;
                setTimeout('tl.getInstance().read()', this.__timeout);
            },
            load: function(){
                var lastId = parseInt(jQuery('#statuses  div:eq(0)').data('id'));
                //Evitar NaN
                if(isNaN(lastId))
                    lastId = 0;

                var countId = parseInt(jQuery('#news-bar-item').data('count-id'));

                jQuery.getJSON( ipb.vars['base_url'] + "app=portal&section=news&module=ajax&do=load&md5check=" + ipb.vars['secure_hash'], {
                    last: lastId,
                    countItems: countId
                }, function(data, textStatus, jqXHR){
                    jQuery('#news-bar-item').html('');
                    jQuery('#stream-load').hide();
                    var $statuses = jQuery('#statuses div:eq(0)');
                    if($statuses.length)
                    {
                        jQuery('#statuses div:eq(0)').before(data.html);
                        
		                $$('.___submit').each( function(suBo)
		                {
		                		jQuery('.___submit').unbind();
		                        id = suBo.identify();
		                        
		                        $(id).observe( 'click', tl.getInstance().addReply.bindAsEventListener( this, id.replace( 'statusSubmit-', '' ) ) );
		                });
		                tl.getInstance().like();
                    }
                    else {
                        jQuery('#statuses').append(data.html);
                    }
                });
            },
            getFriends: function(){
                jQuery('textarea.mention').mentionsInput({
                    onDataRequest: function (mode, query, callback){
                        jQuery.getJSON(ipb.vars['base_url'] + 'app=portal&module=ajax&section=friends&member_id='+ipb.vars['member_id']+'&do=get&secure_key=' + ipb.vars['secure_hash'], function(responseData) {
                            responseData = _.filter(responseData, function(item) {
                                return item.name.toLowerCase().indexOf(query.toLowerCase()) > -1;
                            });
                            callback.call(this, responseData);
                        });
                    }
                });
            },
            linkAttach: function(){
                jQuery('#attach_link').click(function(e){
                    //TODO: Cambiar por traducción
                    jQuery('#media_attachments').html('<form action="' + ipb.vars['base_url'] + 'app=portal&module=ajax&section=metauris&md5check=' + ipb.vars['secure_hash'] + '" id="sbmtlink"><input type="text" placeholder="Inserte enlace" name="linkAttach" /></form>');
                    jQuery('form#sbmtlink').submit(function(e) {
                        jQuery.post(jQuery(this).attr('action'), 
                            jQuery(this).serialize(),
                            function(responseData) {
                                var jSonReponse = responseData.response;
                                jQuery('#media_attachments').html('<h1>'+jSonReponse.title+'</h1><img src="'+jSonReponse.images+'" /><a href="'+jSonReponse.url+'">'+jSonReponse.description+'</a>')
                            },
                            "json"
                            );
                        e.preventDefault();
                    });
                    e.preventDefault();
                });
            },
            like: function(){
                jQuery('.__ipsLikeButton').unbind();
                jQuery('.__ipsLikeButton').click(function(e){
                      $this = jQuery(this);
                      new Ajax.Request($this.attr('href'),
                      {
                          method: 'get',
                          onSuccess: function(t) {
                                if( Object.isUndefined( t.responseJSON ) )
                                {
                                    alert( ipb.lang['action_failed'] );
                                    return;
                                }
                                
                                if ( t.responseJSON['error'] )
                                {
                                    alert( t.responseJSON['error'] );
                                }
                                else
                                {
                                    try {
                                        if(t.responseJSON.likeData.formatted != '') {
                                            jQuery('.likes-'+$this.data('rep-id')+', ._likes-'+$this.data('rep-id')).show();
                                        }else{
                                            jQuery('.likes-'+$this.data('rep-id')+', ._likes-'+$this.data('rep-id')).hide();
                                        }

                                        jQuery('.likes-'+$this.data('rep-id')+' span.data-likes, ._likes-'+$this.data('rep-id')+' span.data-likes').html(t.responseJSON.likeData.formatted);
                                        jQuery('#likes-count-'+$this.data('repreply-id')+', #_likes-count-'+$this.data('repreply-id')).html((parseInt(t.responseJSON.likeData.totalCount) > 100 ? '+100' : t.responseJSON.likeData.totalCount)).parent().show();
                                        
                                        
                                        if(t.responseJSON.likeData.iLike){
                                            jQuery('.notlike-'+$this.data('rep-id')+', .notlikereply-'+$this.data('repreply-id')).show();
                                            jQuery('.yeslike-'+$this.data('rep-id')+', .yeslikereply-'+$this.data('repreply-id')).hide();
                                        }
                                        else{
                                            jQuery('.yeslike-'+$this.data('rep-id')+', .yeslikereply-'+$this.data('repreply-id')).show();
                                            jQuery('.notlike-'+$this.data('rep-id')+', .notlikereply-'+$this.data('repreply-id')).hide();
                                        }
                                        if(parseInt(t.responseJSON.likeData.totalCount) == 0)
                                        	jQuery('#likes-count-'+$this.data('repreply-id')+', '+'#_likes-count-'+$this.data('repreply-id')).parent().hide();
                                    }
                                    catch(err)
                                    {
                                        Debug.error( err );
                                    }
                                }
                          }
                      });
                      e.preventDefault();
                });
            },
    /*!! showAllComments */
    /* result of clicking "show all X comments" */
    showAllComments: function(e, elem)
    {
        Event.stop(e);
        
        var status = $( elem ).className.match('__x([0-9]+)');
        if( status == null || Object.isUndefined( status[1] ) ){ Debug.error("Error showing all comments"); return; }
        var status_id = status[1];
        
        new Ajax.Request( ipb.vars['base_url'] + "app=portal&section=status&module=ajax&do=showall&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash']+ '&skin_group=' + ipb.status.skin_group,
                        {
                            method: 'get',
                            onSuccess: function(t)
                            {
                                if( Object.isUndefined( t.responseJSON ) )
                                {
                                    alert( ipb.lang['action_failed'] );
                                    return;
                                }
                                
                                if ( t.responseJSON['error'] )
                                {
                                    alert( t.responseJSON['error'] );
                                }
                                else
                                {
                                    try {
                                        $('statusMoreWrap-' + status_id ).hide();
                                        
                                        $('statusReplies-' + status_id ).update( t.responseJSON['html'] );
                                        
                                        if($('_statusReplies-' + status_id )){
	                                        $('_statusMoreWrap-' + status_id ).hide();
	                                        $('_statusReplies-' + status_id ).update( t.responseJSON['html'] );
	                                    }
                                        
		                                tl.getInstance().like();
                                        
                                        if ( t.responseJSON['status_replies'] > 20 )
                                        {
                                            $('statusReplies-' + status_id ).addClassName('status_replies_many');
                                        }
                                    }
                                    catch(err)
                                    {
                                        Debug.error( err );
                                    }
                                }
                            }
                        });
    },
            /* Add a sexy ajax reply" */
            addReply: function(e, status_id)
            {
                    Event.stop(e);
	                //Get Mentions
	                var jsonMentions = '';
	                var content;
	                if(jQuery('#_statusText-' + status_id).length > 0) {
		                jQuery('#_statusText-' + status_id).mentionsInput('getMentions', function(data) {
		                    jsonMentions = JSON.stringify(data);
		                });
	                    if ( $('_statusText-' + status_id ).value.length < 2 )
	                    {
	                    		alert("No haz insertado un comentario");
	                            return false;
	                    }
	                    content = $('_statusText-' + status_id ).value.encodeParam();
	                }else {
		                jQuery('#statusText-' + status_id).mentionsInput('getMentions', function(data) {
		                    jsonMentions = JSON.stringify(data);
		                });
		                
	                    if ( $('statusText-' + status_id ).value.length < 2 )
	                    {
	                            return false;
	                    }
	                    content = $('statusText-' + status_id ).value.encodeParam();
                    }

                    new Ajax.Request( ipb.vars['base_url'] + "app=portal&section=status&module=ajax&do=reply&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash']+ '&skin_group=' + ipb.status.skin_group,
                                                    {
                                                            method: 'post',
                                                            evalJSON: 'force',
                                                            parameters: {
                                                                    content: content,
                                                                    mentions: jsonMentions                                                                    
                                                            },
                                                            onSuccess: function(t)
                                                            {
                                                                    if( Object.isUndefined( t.responseJSON ) )
                                                                    {
                                                                            alert( ipb.lang['action_failed'] );
                                                                            return;
                                                                    }

                                                                    if ( t.responseJSON['error'] )
                                                                    {
                                                                            alert( t.responseJSON['error'] );
                                                                    }
                                                                    else
                                                                    {
                                                                            try {
                                                                                    $( 'statusReplyBlank-' + status_id ).show().innerHTML += t.responseJSON['html'];
                                                                                    if($( '_statusReplyBlank' + status_id ))
	                                                                                    $( '_statusReplyBlank' + status_id ).show().innerHTML += t.responseJSON['html'];
                                                                                    jQuery('#statusText-' + status_id + ', _statusText-' + status_id ).val('');
                                                                                    jQuery('#statusText-' + status_id+', #_statusText-' + status_id).parent().parent().find('div.mentions-input-box > div.mentions').html('');
                                                                                    tl.getInstance().like();
                                                                            }
                                                                            catch(err)
                                                                            {
		                                                                            console.log(err);
                                                                                    Debug.write( err );
                                                                            }
                                                                    }
                                                            }
                                                    });
            },
            /*!! deleteStatus */
            /* result of clicking "delete" on a status */
            deleteStatus: function(e, elem)
            {
                    Event.stop(e);
                    if ( ! confirm( ipb.lang['delete_confirm'] ) )
                    {
                            return false;
                    }

                    var status = $( elem ).className.match('__d([0-9]+)');
                    if( status == null || Object.isUndefined( status[1] ) ){Debug.error("Error showing all comments");return;}
                    var status_id = status[1];
                    new Ajax.Request( ipb.vars['base_url'] + "app=portal&section=status&module=ajax&do=deleteStatus&status_id=" + status_id + "&md5check=" + ipb.vars['secure_hash'],
                                                    {
                                                            method: 'get',
                                                            onSuccess: function(t)
                                                            {
                                                                    if( Object.isUndefined( t.responseJSON ) || t.responseJSON.error )
                                                                    {
                                                                            alert( ipb.lang['action_failed'] );
                                                                            return;
                                                                    }
                                                                    else
                                                                    {
                                                                            $('statusWrap-' + status_id ).remove();
                                                                    }
                                                            }
                                                    });
            },
            parseLink: function()
            {
				if(!isValidURL(jQuery('#status_update').val()))
				{
					alert('Please enter a valid url.');
					return false;
				}
				else
				{
					jQuery('#ajax_loading').show();
					jQuery('#atc_url').html(jQuery('#url').val());
					jQuery.post(ipb.vars['base_url'] + "app=portal&section=metauris&module=ajax&md5check=" + ipb.vars['secure_hash']+"&url="+escape(jQuery('#status_update').val()), {}, function(response){
						
						//Set Content
						jQuery('#atc_title').html(tl.getInstance().base64_decode(response.response.title));
						jQuery('#atc_desc').html(tl.getInstance().base64_decode(response.response.description));
						jQuery('#atc_total_images').html(response.response.total_images);
						
						jQuery('#atc_images').html(' ');
						
						jQuery.each(response.response.images, function (a, b)
						{
							jQuery('#atc_images').append('<img src="'+b.img+'" width="100" id="'+(jQuery('#atc_images img').length + 1)+'">');
						});
						jQuery('#atc_images img').hide();
						
						//Flip Viewable Content 
						jQuery('#attach_content').fadeIn('slow');
						jQuery('#ajax_loading').hide();
						
						//Show first image
						jQuery('img#1').fadeIn();
						jQuery('#cur_image').val(1);
						jQuery('#cur_image_num').html(1);
						
						// next image
						jQuery('#next').unbind('click');
						jQuery('#next').bind("click", function(){
						 
							var total_images = parseInt(jQuery('#atc_total_images').html());			 
							if (total_images > 0)
							{
								var index = jQuery('#cur_image').val();
								jQuery('img#'+index).hide();
								if(index < total_images)
								{
									new_index = parseInt(index)+parseInt(1);
								}
								else
								{
									new_index = 1;
								}
								
								jQuery('#cur_image').val(new_index);
								jQuery('#cur_image_num').html(new_index);
								jQuery('img#'+new_index).show();
							}
						});	
						
						// prev image
						jQuery('#prev').unbind('click');
						jQuery('#prev').bind("click", function(){
						 
							var total_images = parseInt(jQuery('#atc_total_images').html());				 
							if (total_images > 0)
							{
								var index = jQuery('#cur_image').val();
								jQuery('img#'+index).hide();
								if(index > 1)
								{
									new_index = parseInt(index)-parseInt(1);;
								}
								else
								{
									new_index = total_images;
								}
								
								jQuery('#cur_image').val(new_index);
								jQuery('#cur_image_num').html(new_index);
								jQuery('img#'+new_index).show();
						 	}
						});	
					});
				}
            },
            toggleFriendStatus: function(e) 
            {
                        Event.stop(e);
                        // Are they a friend?
                        if( ipb.profile.isFriend ){
                            urlBit = "add";
                        } else {
                            urlBit = "add";
                        }
                        
                        $this = jQuery(this);

                        new Ajax.Request( ipb.vars['base_url'] + "app=members&section=friends&module=ajax&do=" + urlBit + "&member_id=" + ipb.profile.viewingProfile + "&md5check=" + ipb.vars['secure_hash'],
                        {
                            method: 'post',
                            onSuccess: function(t)
                            {
                                switch( t.responseText )
                                {
                                    case 'pp_friend_timeflood':
                                        alert(ipb.lang['cannot_readd_friend']);
                                        Event.stop(e);
                                        break;
                                    case "pp_friend_already":
                                        alert(ipb.lang['friend_already']);
                                        break;
                                    case "error":
                                        alert(ipb.lang['action_failed']);
                                        break;
                                    default:
                                        if ( ipb.profile.isFriend ) { 
                                            ipb.profile.isFriend = false;
                                            newShow = ipb.templates['add_friend'];
                                        } else {
                                            ipb.profile.isFriend = true;
                                            newShow = ipb.templates['remove_friend'];
                                        }
                                        
                                    break;
                                }
                                if(parseInt($this.parent().parent('ul').find('li').length) > 1)
                                    $this.parent('li').remove();
                                else
                                    jQuery('#suggestfriends').remove();
                            }
                        });
            },
			base64_decode: function (data) {
			    // http://kevin.vanzonneveld.net
			    // +   original by: Tyler Akins (http://rumkin.com)
			    // +   improved by: Thunder.m
			    // +      input by: Aman Gupta
			    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			    // +   bugfixed by: Onno Marsman
			    // +   bugfixed by: Pellentesque Malesuada
			    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			    // +      input by: Brett Zamir (http://brett-zamir.me)
			    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			    // *     example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
			    // *     returns 1: 'Kevin van Zonneveld'
			    // mozilla has this native
			    // - but breaks in 2.0.0.12!
			    //if (typeof this.window['btoa'] == 'function') {
			    //    return btoa(data);
			    //}
			    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
			    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
			        ac = 0,
			        dec = "",
			        tmp_arr = [];
			
			    if (!data) {
			        return data;
			    }
			
			    data += '';
			
			    do { // unpack four hexets into three octets using index points in b64
			        h1 = b64.indexOf(data.charAt(i++));
			        h2 = b64.indexOf(data.charAt(i++));
			        h3 = b64.indexOf(data.charAt(i++));
			        h4 = b64.indexOf(data.charAt(i++));
			
			        bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;
			
			        o1 = bits >> 16 & 0xff;
			        o2 = bits >> 8 & 0xff;
			        o3 = bits & 0xff;
			
			        if (h3 == 64) {
			            tmp_arr[ac++] = String.fromCharCode(o1);
			        } else if (h4 == 64) {
			            tmp_arr[ac++] = String.fromCharCode(o1, o2);
			        } else {
			            tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
			        }
			    } while (i < data.length);
			
			    dec = tmp_arr.join('');
			
			    return dec;
			},
			base64_encode: function (data) {
			    // http://kevin.vanzonneveld.net
			    // +   original by: Tyler Akins (http://rumkin.com)
			    // +   improved by: Bayron Guevara
			    // +   improved by: Thunder.m
			    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			    // +   bugfixed by: Pellentesque Malesuada
			    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
			    // +   improved by: Rafał Kukawski (http://kukawski.pl)
			    // *     example 1: base64_encode('Kevin van Zonneveld');
			    // *     returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
			    // mozilla has this native
			    // - but breaks in 2.0.0.12!
			    //if (typeof this.window['atob'] == 'function') {
			    //    return atob(data);
			    //}
			    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
			    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
			        ac = 0,
			        enc = "",
			        tmp_arr = [];
			
			    if (!data) {
			        return data;
			    }
			
			    do { // pack three octets into four hexets
			        o1 = data.charCodeAt(i++);
			        o2 = data.charCodeAt(i++);
			        o3 = data.charCodeAt(i++);
			
			        bits = o1 << 16 | o2 << 8 | o3;
			
			        h1 = bits >> 18 & 0x3f;
			        h2 = bits >> 12 & 0x3f;
			        h3 = bits >> 6 & 0x3f;
			        h4 = bits & 0x3f;
			
			        // use hexets to index into b64, and append result to encoded string
			        tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
			    } while (i < data.length);
			
			    enc = tmp_arr.join('');
			    
			    var r = data.length % 3;
			    
			    return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
			
			}

        }
    } 



    // this is our instance holder  
    var instance;

    // this is an emulation of static variables and methods
    var _static  = {   

        // This is a method for getting an instance
        // It returns a singleton instance of a singleton object
        getInstance:  function( options ) {    
            if( instance  ===  undefined )  {     
                instance = new Status( options );    
                /* Submit button for replies */
                $$('.___submit').each( function(suBo)
                {
                        id = suBo.identify();

                        $(id).observe( 'click', instance.addReply.bindAsEventListener( this, id.replace( 'statusSubmit-', '' ) ) );
                });
                ipb.delegate.register(".___sD", instance.deleteStatus);
                ipb.delegate.register(".___showAll", instance.showAllComments);
                ipb.delegate.register(".____showAll", instance.showAllComments);
                jQuery('.friend_toggle_suggest').click(instance.toggleFriendStatus);
            }    
            return  instance;  

        }
    };  
    return  _static;
})();


jQuery().ready(function(){

    //Read status
    if( $( 'status_update' ) ) 
    {
        var status = tl.getInstance({
            timeout:  3000
        });
        status.read();
        //Event detect scroll bottom
        status.getDown();
    
        status.getFriends();
        status.linkAttach();
        status.like();
        jQuery('#status_submit_global, #_status_submit_global').click(function(e) {
            status.post();
            e.preventDefault();
        });
        
        jQuery('#submitUrl').click(status.parseLink);
    
        jQuery('#stream-load').click(function(){
            status.load();
        });
    
    	jQuery('#attach-file').click(function(){
    	      jQuery('#uploadBoxWrapParent').css({'height': 'auto',	'padding': '9px !important'}).show();
    	});
    }
});

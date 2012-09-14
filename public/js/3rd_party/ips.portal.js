jQuery().ready(function() {
    jQuery('#email, #status_update').val('');
    jQuery('form#send_invite').submit(function(e) {
        jQuery('#showErrors span.error').html('');
        new Ajax.Request( jQuery(this).attr('action'),
                        {
                            method: jQuery(this).attr('method'),
                            parameters: jQuery(this).serialize(),
                            jsonp: false,
                            onSuccess: function(data)
                            {
                                 if(data.invitations!==null)
                                    jQuery('#ninvite').html(data.responseJSON.invitations);
                                if(data.responseJSON.errorMessages!==null && data.responseJSON.errorMessages != undefined){
                                    for(error in data.responseJSON.errorMessages.general) {
                                        jQuery('#showErrors span.error').html(data.responseJSON.errorMessages.general[error]);
                                        break;
                                    }
                                }
                                jQuery('#email').val('');


                                if(parseInt(data.responseJSON.invitations) == 0)
                                    jQuery('#sendInvitations').css('display', 'none');
                            }
                        });
        e.preventDefault();
    });
    jQuery('#account-settings a').click(function(){
        jQuery(this).parent('div').children('.setting-box').toggle();
    });
});


/*if (!window.console || !console.firebug)
{
    var names = ["log", "debug", "info", "warn", "error", "assert", "dir", "dirxml",
    "group", "groupEnd", "time", "timeEnd", "count", "trace", "profile", "profileEnd"];

    window.console = {};
    for (var i = 0; i < names.length; ++i)
        window.console[names[i]] = function() {}
}*/
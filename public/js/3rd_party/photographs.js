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

var photos = (function (){

    // options: an object containing configuration options for the singleton
    // e.g var options = { name: "test", pointX: 5};  
    function Photos( options )  {

        // set options to the options supplied or an empty object if none are 
        // provided
        options = options || {};


        var timeout = options.timeout || 1000;
        var longMaxTimeout = options.longtimeout || 60000;
        
        
        return {
            showphoto: function(e){
	            jQuery('a[rel="photo_tl"]').unbind().click(function(e){
		            status_id = jQuery(this).parents('div[data-id]').attr('data-id');
		            if(jQuery('#overlay_photos')) {
			            jQuery('#overlay_photos').remove();
		            }
		            jQuery('body').append('<div id="overlay_photos"></div>');
		            jQuery.ajax({
			            url: ipb.vars['base_url'] + "app=portal&section=photos&module=ajax&do=showphoto&md5check=" + ipb.vars['secure_hash'] + "&return=json",
			            type: 'get',
			            dataType: 'json',
			            data: {status_id: status_id},
			            success: function(data) {
			            	if(data.status == 'success')
			            	{
					            jQuery('#overlay_photos').html(data.html);
					            photos.getInstance().closephoto();
                                tl.getInstance().like();
                                ipb.delegate.register(".____showAll", tl.getInstance().showAllComments);
				                $$('.____submit').each( function(suBo)
				                {
				                        id = suBo.identify();
				                        $(id).observe( 'click', tl.getInstance().addReply.bindAsEventListener( this, id.replace( '_statusSubmit-', '' ) ) );
				                });
					        }
			            }
		            });
	            	e.preventDefault();
	            });
            },
            closephoto: function() { 
	            jQuery('#closeimage').click(function(e) {
		            jQuery('#overlay_photos').fadeOut('slow');
	            	e.preventDefault();
	            });
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
                instance = new Photos( options );
                instance.showphoto();
            }    
            return  instance;  

        }
    };  
    return  _static;
})();


jQuery().ready(function(){
    var instance = photos.getInstance({
        timeout:  1000
    });
    
});

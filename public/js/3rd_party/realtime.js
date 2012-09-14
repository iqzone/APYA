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

var realTime = (function (){

    // options: an object containing configuration options for the singleton
    // e.g var options = { name: "test", pointX: 5};  
    function RealTime( options )  {

        // set options to the options supplied or an empty object if none are 
        // provided
        options = options || {};


        var timeout = options.timeout || 1000;
        var longMaxTimeout = options.longtimeout || 60000;
        var urlCheck = "app=portal&section=realtime&module=ajax&md5check=" + ipb.vars['secure_hash'] + "&return=json&smallSpace=1&do=";
        
        function setTimeLapsed(setInitTimeout){
                if(setInitTimeout >= longMaxTimeout)
                    return timeout;
                else
                    return (setInitTimeout *= 2);
        };
        
        return {
            read: function(){
            
                return {
                	init: function(){
                		realTime.getInstance().read().likes();
                		realTime.getInstance().read().comments();
	            	},
		            likes: function(){
			            jQuery.ajax({
				            url: ipb.vars['base_url'] + urlCheck + 'likes',
				            type: 'GET',
				            success: function(t){
					            //timeout = setTimeLapsed(timeout);
					            setTimeout('realTime.getInstance().read().likes()', 3000);
					            if(jQuery('#BeeperBox'))
					            	jQuery('#BeeperBox').remove();
					            
					            if(t.html != ''){					            
						            jQuery('body').append(t.html);
						            setTimeout(function() {jQuery('#BeeperBox').fadeOut(4000)}, 2000);
						        }
				            }
			            });
		            },
		            comments: function(){
			            jQuery.ajax({
				            url: ipb.vars['base_url'] + urlCheck + 'comments',
				            type: 'GET',
				            success: function(t){
				                //timeout = setTimeLapsed(timeout);
					            setTimeout('realTime.getInstance().read().comments()', 3000);
					            if(jQuery('#BeeperBoxComment'))
					            	jQuery('#BeeperBoxComment').remove();
					            if(t.html != ''){					            
						            jQuery('body').append(t.html);
						            setTimeout(function() {jQuery('#BeeperBoxComment').fadeOut(4000)}, 2000);
						        }
				            }
			            });
		            }
	            }
	            
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
                instance = new RealTime( options );    
//                ipb.delegate.register(".___showAll", instance.showAllComments);
            }    
            return  instance;  

        }
    };  
    return  _static;
})();


jQuery().ready(function(){
    var instance = realTime.getInstance({
        timeout:  1000
    });

    //Read status
    instance.read().init();
    
});

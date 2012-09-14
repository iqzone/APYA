/**
 * Simple wrapper for IPBs use
 * options
 * 
 */
IPBProgressBar = Class.create( {
	initialize: function( options )
	{
		this.controlbar = null;
		this.counter = 0;
		this.lastId  = 0;
		this.options = {
               title: "Processing",
               total: null,
               pergo: null,
               ajaxUrl: ''
            }
        
        Object.extend(this.options,options || {});
		
		this.current = 0;
		this.done    = 0;
		
		/* inc css */
		curUrl = $('progressbarScript').readAttribute('src').replace(/progressbar.js/, '' );
		
		$$('head')[0].insert( new Element( 'link', { type: 'text/css', rel: 'stylesheet', media: 'screen', href: curUrl + 'progressbar.css' } ) )
	},
	
	show: function()
	{
		if ( $('showProgressBar_popup') )
		{
			$('showProgressBar_popup').remove();
		}
		
		new ipb.Popup( 'showProgressBar', {  type: 'pane',
											 initial: "<h3>" + this.options.title + "</h3><div id='progresswrapper'><div id='progress_bar'></div><div id='progressmsg'>Ready...</div></div>",
											 stem: false,
											 hideAtStart: false,
											 hideClose: false,
											 defer: false,
											 w: 200 } );
		
		/* Give it a chance to appear */
		setTimeout( this._show.bind(this), 150 );
	},
	
	_show: function()
	{
		this.controlbar = new Control.ProgressBar('progress_bar' );
		
		if ( this.options.total === null || this.options.pergo === null )
		{
			this.getOptionsViaAjax();
		}
		else
		{
			this.runAjax();
		}
	},
	
	runAjax: function()
	{
		/* Pings ajax, expects json back:
		 * status: (processing, done, error)
		 * msg: (Optional message to show)
		 * lastId: (last ID processed)
		 *  */
		Debug.write( this.options.ajaxUrl + '&pb_act=fire&pb_pergo=' + this.options.pergo + '&pb_lastId=' + this.lastId + '&pb_done=' + this.done );
		
		new Ajax.Request( 	this.options.ajaxUrl + '&pb_act=fire&pb_pergo=' + this.options.pergo + '&pb_lastId=' + this.lastId + '&pb_done=' + this.done,
			 				{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									json = t.responseJSON;
									
									if ( t.responseJSON['error'] )
									{
										alert( t.responseJSON['error'] );
									}
									else
									{
										if ( json['status'] == 'done' )
										{
											Debug.write( "Done");
											this.finish();
										}
										else if ( json['status'] == 'error' )
										{
											this.finish( "An error occured " + json['msg'] );
										}
										else
										{
										
											/* We're still processing, so.. */
											this.lastId = json['lastId'];
											this.done  += this.options.pergo;
											
											num = 0;
											
											if ( this.done >= this.options.total )
											{
												num = 100;
											}
											else
											{
												num = Math.round( ( 100 / this.options.total ) * this.done );
											}
											
											/* Increment bar */
											this.controlbar.setProgress( num );
											
											/* Update message */
											this.updateStatus( ( json['msg'] ) ? json['msg'] : "Processed " + this.done + " of " + this.options.total + '...' );
		
											/* Go again! */
											this.runAjax();
										}
									}
								}.bind(this)
							}
						);
	},
	
	/**
	 * fetch data via ajax. Expects json['pergo'] json['total']
	 */
	getOptionsViaAjax: function()
	{
		this.updateStatus( "Fetching details..." );
		
		Debug.write( this.options.ajaxUrl + '&pb_act=getOptions' );
		
		// Send AJAX request
		new Ajax.Request( 	this.options.ajaxUrl + '&pb_act=getOptions',
			 				{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									json = t.responseJSON;
									
									Debug.dir( json );
									
									if ( t.responseJSON['error'] )
									{
										alert( t.responseJSON['error'] );
									}
									else
									{
										if ( json['total'] && json['pergo'] )
										{
											this.options.total = parseInt( json['total'] );
											this.options.pergo = parseInt( json['pergo'] );
											
											this.runAjax();
										}
										else
										{
											Debug.write( "No data found, so assume nothing to rebuild");
											this.finish();
										}
									}
								}.bind(this)
							}
						);
	},
	
	/**
	 * Updates status innit
	 */
	updateStatus: function( msg )
	{
		$('progressmsg').update( msg );
	},
	
	/**
	 * Finish
	 */
	finish: function( msg )
	{
		msg = msg ? msg : "Finished";
		
		this.updateStatus( msg );
		
		this.controlbar.setProgress(0);
	}


} );

/**
 * @author Ryan Johnson <http://syntacticx.com/>
 * @copyright 2008 PersonalGrid Corporation <http://personalgrid.com/>
 * @package LivePipe UI
 * @license MIT
 * @url http://livepipe.net/core
 * @require prototype.js
 */

if(typeof(Control) == 'undefined')
    Control = {};
    
var $proc = function(proc){
    return typeof(proc) == 'function' ? proc : function(){return proc};
};

var $value = function(value){
    return typeof(value) == 'function' ? value() : value;
};

Object.Event = {
    extend: function(object){
        object._objectEventSetup = function(event_name){
            this._observers = this._observers || {};
            this._observers[event_name] = this._observers[event_name] || [];
        };
        object.observe = function(event_name,observer){
            if(typeof(event_name) == 'string' && typeof(observer) != 'undefined'){
                this._objectEventSetup(event_name);
                if(!this._observers[event_name].include(observer))
                    this._observers[event_name].push(observer);
            }else
                for(var e in event_name)
                    this.observe(e,event_name[e]);
        };
        object.stopObserving = function(event_name,observer){
            this._objectEventSetup(event_name);
            if(event_name && observer)
                this._observers[event_name] = this._observers[event_name].without(observer);
            else if(event_name)
                this._observers[event_name] = [];
            else
                this._observers = {};
        };
        object.observeOnce = function(event_name,outer_observer){
            var inner_observer = function(){
                outer_observer.apply(this,arguments);
                this.stopObserving(event_name,inner_observer);
            }.bind(this);
            this._objectEventSetup(event_name);
            this._observers[event_name].push(inner_observer);
        };
        object.notify = function(event_name){
            this._objectEventSetup(event_name);
            var collected_return_values = [];
            var args = $A(arguments).slice(1);
            try{
                for(var i = 0; i < this._observers[event_name].length; ++i)
                    collected_return_values.push(this._observers[event_name][i].apply(this._observers[event_name][i],args) || null);
            }catch(e){
                if(e == $break)
                    return false;
                else
                    throw e;
            }
            return collected_return_values;
        };
        if(object.prototype){
            object.prototype._objectEventSetup = object._objectEventSetup;
            object.prototype.observe = object.observe;
            object.prototype.stopObserving = object.stopObserving;
            object.prototype.observeOnce = object.observeOnce;
            object.prototype.notify = function(event_name){
                if(object.notify){
                    var args = $A(arguments).slice(1);
                    args.unshift(this);
                    args.unshift(event_name);
                    object.notify.apply(object,args);
                }
                this._objectEventSetup(event_name);
                var args = $A(arguments).slice(1);
                var collected_return_values = [];
                try{
                    if(this.options && this.options[event_name] && typeof(this.options[event_name]) == 'function')
                        collected_return_values.push(this.options[event_name].apply(this,args) || null);
                    for(var i = 0; i < this._observers[event_name].length; ++i)
                        collected_return_values.push(this._observers[event_name][i].apply(this._observers[event_name][i],args) || null);
                }catch(e){
                    if(e == $break)
                        return false;
                    else
                        throw e;
                }
                return collected_return_values;
            };
        }
    }
};

/* Begin Core Extensions */

//Element.observeOnce
Element.addMethods({
    observeOnce: function(element,event_name,outer_callback){
        var inner_callback = function(){
            outer_callback.apply(this,arguments);
            Element.stopObserving(element,event_name,inner_callback);
        };
        Element.observe(element,event_name,inner_callback);
    }
});

//mouse:wheel
(function(){
    function wheel(event){
        var delta, element, custom_event;
        // normalize the delta
        if (event.wheelDelta) { // IE & Opera
            delta = event.wheelDelta / 120;
        } else if (event.detail) { // W3C
            delta =- event.detail / 3;
        }
        if (!delta) { return; }
        element = Event.extend(event).target;
        element = Element.extend(element.nodeType === Node.TEXT_NODE ? element.parentNode : element);
        custom_event = element.fire('mouse:wheel',{ delta: delta });
        if (custom_event.stopped) {
            Event.stop(event);
            return false;
        }
    }
    document.observe('mousewheel',wheel);
    document.observe('DOMMouseScroll',wheel);
})();

/* End Core Extensions */

//from PrototypeUI
var IframeShim = Class.create({
    initialize: function() {
        this.element = new Element('iframe',{
            style: 'position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);display:none',
            src: 'javascript:void(0);',
            frameborder: 0 
        });
        $(document.body).insert(this.element);
    },
    hide: function() {
        this.element.hide();
        return this;
    },
    show: function() {
        this.element.show();
        return this;
    },
    positionUnder: function(element) {
        var element = $(element);
        var offset = element.cumulativeOffset();
        var dimensions = element.getDimensions();
        this.element.setStyle({
            left: offset[0] + 'px',
            top: offset[1] + 'px',
            width: dimensions.width + 'px',
            height: dimensions.height + 'px',
            zIndex: element.getStyle('zIndex') - 1
        }).show();
        return this;
    },
    setBounds: function(bounds) {
        for(prop in bounds)
            bounds[prop] += 'px';
        this.element.setStyle(bounds);
        return this;
    },
    destroy: function() {
        if(this.element)
            this.element.remove();
        return this;
    }
});

/**
 * @author Ryan Johnson <http://syntacticx.com/>
 * @copyright 2008 PersonalGrid Corporation <http://personalgrid.com/>
 * @package LivePipe UI
 * @license MIT
 * @url http://livepipe.net/control/progressbar
 * @require prototype.js, livepipe.js
 */

/*global document, Prototype, Ajax, Class, PeriodicalExecuter, $, $A, Control */

if(typeof(Prototype) == "undefined") {
    throw "Control.ProgressBar requires Prototype to be loaded."; }
if(typeof(Object.Event) == "undefined") {
    throw "Control.ProgressBar requires Object.Event to be loaded."; }

Control.ProgressBar = Class.create({
    initialize: function(container,options){
        this.progress = 0;
        this.executer = false;
        this.active = false;
        this.poller = false;
        this.container = $(container);
        this.containerWidth = this.container.getDimensions().width - (parseInt(this.container.getStyle('border-right-width').replace(/px/,''), 10) + parseInt(this.container.getStyle('border-left-width').replace(/px/,''), 10));
        this.progressContainer = $(document.createElement('div'));
        this.progressContainer.setStyle({
            width: this.containerWidth + 'px',
            height: '100%',
            position: 'absolute',
            top: '0px',
            right: '0px'
        });
       
        this.container.appendChild(this.progressContainer);
        this.options = {
            afterChange: Prototype.emptyFunction,
            interval: 0.25,
            step: 1,
            classNames: {
                active: 'progress_bar_active',
                inactive: 'progress_bar_inactive'
            }
        };
        Object.extend(this.options,options || {});
        this.container.addClassName(this.options.classNames.inactive);
        this.active = false;
    },
    setProgress: function(value){
        this.progress = value;
        this.draw();
        if(this.progress >= 100) {
            this.stop(false); }
        this.notify('afterChange',this.progress,this.active);
    },
    poll: function (url, interval, ajaxOptions){
        // Extend the passed ajax options and success callback with our own.
        ajaxOptions = ajaxOptions || {};
        var success = ajaxOptions.onSuccess || Prototype.emptyFunction;
        ajaxOptions.onSuccess = success.wrap(function (callOriginal, request) {
            this.setProgress(parseInt(request.responseText, 10));
            if(!this.active) { this.poller.stop(); }
            callOriginal(request);
        }).bind(this);

        this.active = true;
        this.poller = new PeriodicalExecuter(function(){
            var a = new Ajax.Request(url, ajaxOptions);
        }.bind(this),interval || 3);
    },
    start: function(){
        this.active = true;
        this.container.removeClassName(this.options.classNames.inactive);
        this.container.addClassName(this.options.classNames.active);
        this.executer = new PeriodicalExecuter(this.step.bind(this,this.options.step),this.options.interval);
    },
    stop: function(reset){
        this.active = false;
        if(this.executer) {
            this.executer.stop(); }
        this.container.removeClassName(this.options.classNames.active);
        this.container.addClassName(this.options.classNames.inactive);
        if (typeof reset  === 'undefined' || reset === true) {
            this.reset(); }
    },
    step: function(amount){
        this.active = true;
        this.setProgress(Math.min(100,this.progress + amount));
    },
    reset: function(){
        this.active = false;
        this.setProgress(0);
    },
    draw: function(){
        this.progressContainer.setStyle({
            width: (this.containerWidth - Math.floor((parseInt(this.progress, 10) / 100) * this.containerWidth)) + 'px'
        });
    },
    notify: function(event_name){
        if(this.options[event_name]) {
            return [this.options[event_name].apply(this.options[event_name],$A(arguments).slice(1))]; }
    }
});
Object.Event.extend(Control.ProgressBar);
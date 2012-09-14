(function($){
	$.ccs = {
		//----------------------------------------------
		// $.ccs.register method; based on jQuery UI
		//----------------------------------------------
		register: function( widget, base, obj, short){	
			var bits = widget.split('.');
			var namespace = bits[0];
			var name = bits[1];

			if( obj && !short ){
				short = obj;
				obj = base;
				base = $.ccsBase;
			} else if( !obj && !short ){
				obj = base;
				base = $.ccsBase;
			}

			$[ namespace ] = $[ namespace ] || {};
			
			if( $[ namespace ][ name ] ){
				return;
			}

			$[ namespace ][ name ] = function( options, element){
				if( arguments.length ){
					this._instantiate( options, element );
				}
			};

			var newBase = new base();
			newBase.options = $.extend( true, {}, newBase.options );
			$[ namespace ][ name ].prototype = $.extend( true, newBase, obj );

			if( !short ){
				short = namespace + name;
			}

			$.ccs.bridge( short, $[ namespace ][ name ] );
		},

		//----------------------------------------------
		// $.ccs.bridge method; taken from jQuery UI
		//
		// jQuery UI Widget 1.8.16
		//
		// Copyright 2011, AUTHORS.txt (http://jqueryui.com/about)
		// Dual licensed under the MIT or GPL Version 2 licenses.
		// http://jquery.org/license
		//
		// http://docs.jquery.com/UI/Widget
		//----------------------------------------------
		bridge: function( name, object ) {
			$.fn[ name ] = function( options ) {
				var isMethodCall = typeof options === "string",
					args = Array.prototype.slice.call( arguments, 1 ),
					returnValue = this;

				// allow multiple hashes to be passed on init
				options = !isMethodCall && args.length ?
					$.extend.apply( null, [ true, options ].concat(args) ) :
					options;

				// prevent calls to internal methods
				if ( isMethodCall && options.charAt( 0 ) === "_" ) {
					return returnValue;
				}

				if ( isMethodCall ) {
					this.each(function() {
						var instance = $.data( this, name ),
							methodValue = instance && $.isFunction( instance[options] ) ?
								instance[ options ].apply( instance, args ) :
								instance;

						if ( methodValue !== instance && methodValue !== undefined ) {
							returnValue = methodValue;
							return false;
						}
					});
				} else {
					this.each(function() {
						var instance = $.data( this, name );
						if ( instance ) {
							instance.option( options || {} )._init();
						} else {
							$.data( this, name, new object( options, this ) );
						}
					});
				}

				return returnValue;
			};
		},

		util: {
			//----------------------------------------------
			// Fits an image (or other box) inside a bigger box
			//----------------------------------------------
			fitImage: function( imageDims, boxDims ){
				
				var newSize = {},
					aspect = imageDims['w'] / imageDims['h'];

				if( (imageDims['w'] <= boxDims['w']) || (imageDims['h'] <= boxDims['h']) ){
					return {'w': imageDims['w'], 'h': imageDims['h']};
				}

				newSize['w'] = boxDims['w'];
				newSize['h'] = newSize['w'] / aspect;

				if( newSize['h'] < boxDims['h'] ){
					newSize['h'] = boxDims['h'];
					newSize['w'] = newSize['h'] * aspect;
				}

				return { 
					'w': newSize['w'],
					'h': newSize['h'],
					'l': Math.ceil( ( boxDims['w'] - newSize['w'] ) / 2 ),
					't': Math.ceil( ( boxDims['h'] - newSize['h'] ) / 2 )
				};
			}
		}
	};

	//----------------------------------------------
	// Base object for ccs widgets
	//----------------------------------------------
	$.ccsBase = function( options, element ){
		if( arguments.length ){
			this._instantiate( options, element );
		}
	};

	$.ccsBase.prototype = {
		name: 'ccsWidget',
		options: { },

		//----------------------------------------------
		// Called when widget is instantiated
		//----------------------------------------------
		_instantiate: function( options, element ){
			$.data( element, this.name, this );
			this.element = $(element);
			this.options = $.extend( true, {}, this.options, options );
			this._init();
		},

		//----------------------------------------------
		// Called after initial setup
		//----------------------------------------------
		_init: function(){},

		//----------------------------------------------
		// Getter/setter for options
		//----------------------------------------------
		option: function( key, value )
		{
			if( $.isPlainObject( key ) ){
			 	this.options = $.extend(true, this.options, key);
            } else if( key && typeof value === 'undefined' ) {
				return this.options[ key ];
			} else {
				this.options[ key ] = value;
			}

			return this;
		}
	};
})(_ccsjQ);

var Debug = {
	write: function( text ){
		if( window.console ){
			console.log( text );
		}
	},
	dir: function( values ){
		if( window.console ){
			console.dir( values );
		}
	}
};
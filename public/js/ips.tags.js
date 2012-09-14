/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.tags.js - Adds tokenizing tags to a textbox */
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var Tags = {};

Element.addMethods( {
	tagify: function( element, options )
	{
		options = Object.extend( {
			existingTags: []
		}, options);
		
		if( !options.isEnabled ){ return; }
		
		if( options.isOpenSystem ){
			new Tags.Open( element, options );
		} else {
			new Tags.Closed( element, options );
		}		
	}
});

//==================================================
// Base class for tag operations
//==================================================
Tags.Base = {
	parentInit: function(){
		// Set up prefixes
		if( this.options.prefixesEnabled && $( this.id + '_prefix' ) ){
			this.setUpPrefixes();
		}
		
		// Store a reference to this on the tag box
		this.element.store('obj', this);
	},
	
	setUpPrefixes: function(){
		var prefixCheck = $( this.id + '_prefix' );
		
		var prefixFunc = function(e){
			if( $(prefixCheck).checked ){
				$( this.tagWrapper ).addClassName('with_prefixes');
			} else {
				$( this.tagWrapper ).removeClassName('with_prefixes');
			}
		}.bind(this);
		
		prefixFunc();
		
		$( prefixCheck ).observe('click', prefixFunc);
	},
	
	importTags: function(){
		
		if( this.options.existingTags && this.options.existingTags.size() ){
			$A(this.options.existingTags).each( function(item){
				if( !item.blank() )
				{
					this.addTag( item );
				}
			}.bind(this));
		}
	},
	
	buildWrapper: function(){
		// The original textbox, which we'll remember
		this.oldInput = $(this.element).observe('focus', function(e){ this.newInput.focus() }.bind(this))
										.observe('blur', function(e){ this.newInput.blur() }.bind(this));

		// The wrapper which holds our tags
		this.tagWrapper = new Element("ul", { 'id': this.id + '_wrapper' })
									.addClassName("ipsTagBox_wrapper")
									.observe('click', this.eventClickWrapper.bindAsEventListener(this));

		this.oldInput.insert({ 'before': this.tagWrapper }).hide();

		// Create an item for the invisible textbox
		this.inputItem = new Element("li", { 'id': this.id + '_inputItem' }).insert( this.newInput );
		this.tagWrapper.insert({ 'bottom': this.inputItem });
	},
	
	addTag: function( item ){
		item = item.strip();
		
		if( parseInt(this.options.forceLowercase) ){
			item = item.toLowerCase();
		}
		
		var closeTag = new Element("span")
							.addClassName('ipsTagBox_closetag')
							.update("&times;")
							.observe('click', function(e){
								this.deleteTag( Event.findElement(e, 'li') );
							}.bind(this));
		var newTag = new Element("li")
						.writeAttribute("tValue", item)
						.addClassName('ipsTagBox_tag')
						.update( item )
						.insert({'bottom': closeTag});
						
		this.inputItem.insert( { 'before': newTag } );
		
		// Add to hidden input
		this.oldInput.value += item+',';
		
		if( Object.isFunction( this._afterAdd ) ){
			this._afterAdd(item);
		}
	},
	
	selectTag: function( tag ){
		this.deselectAll();
		$( tag ).addClassName('selected');
		this.selectedTag = $( tag );
		
		if( Object.isFunction( this._afterSelect ) ){
			this._afterSelect(tag);
		}
	},
	
	deleteTag: function( tag ){
		
		if( this.selectedTag == $(tag) ){
			this.selectedTag = null;
		}
		
		var value = $(tag).readAttribute('tvalue');
		$(tag).remove();
		
		// Add to hidden input
		this.oldInput.value = this.oldInput.value.replace(value+',', '');
		
		if( Object.isFunction( this._afterDelete ) ){
			this._afterDelete( value );
		}
	},
	
	deselectAll: function(){
		$( this.tagWrapper ).select("li").invoke("removeClassName", 'selected');
		this.selectedTag = null;
	},
	
	calculateTotal: function(){
		return $( this.tagWrapper ).select("li:not(#"+this.id+"_inputItem)").size();
	},
	
	eventClickWrapper: function(e){
		var elem = Event.findElement(e, 'li');
		if( elem && $(elem) != $(this.id + '_inputItem') && $(elem).hasClassName('ipsTagBox_tag') ){
			this.selectTag( $(elem) );
		} else {
			$( this.newInput ).focus();
		}
		return false;
	},

	//--------------------------------------------------------------
	// Public Methods
	//--------------------------------------------------------------
	
	getTagCount: function(){
		return this.calculateTotal();
	},
	
	serializeTags: function(){
		var tags = $A();
		$( this.tagWrapper ).select("li:not(#"+this.id+"_inputItem)").each(function(elem){
			tags.push( $(elem).readAttribute('tvalue') );
		});
		return tags;
	}
}

//==================================================
// 'Closed' tagging (i.e. predefined tags)
//==================================================
Tags.Closed = Class.create( Object.extend( {
	
	initialize: function( element, options ){
		this.element = $( element );
		this.id = this.element.id;
		this.options = options;
		this.handlers = {};
		
		if( Object.isUndefined( this.options.predefinedTags ) || !this.options.predefinedTags || !this.options.predefinedTags.size() ){
			Debug.write( "No predefined tags available" );
			try {
				$( this.element ).up('.tag_field').hide();
			} catch(err){ }
			return;
		}
		
		this.newInput = new Element("a", { 'href': '#' })
									.update( this.options.lang['tag_add_link'] )
									.addClassName("ipsTagBox_addlink")
									.observe('click', this.eventAddNewTag.bindAsEventListener(this));
									
		// Finish building wrapper
		this.buildWrapper();
		
		// Build options dropdown
		this.tagDropdown = new Element("div", { 'id': this.id + '_tagdropdown' })
									.addClassName("ipsTagBox_dropdown")
									.setStyle({
										'width': $(this.tagWrapper).measure('padding-box-width') + 'px',
										'position': 'absolute'
									})
									.hide();
		
		this.tagDropdown.insert( {'bottom' : new Element('ul') } );
		this.options.predefinedTags.each( function(item){
			$( this.tagDropdown ).down('ul').insert({'bottom':	new Element('li')
														.update(item)
														.addClassName("ipsTagBox_dditem")
														.writeAttribute("tvalue", item)
														.observe('click', this.eventClickItem.bindAsEventListener(this))
										});
										
			if ( ! Object.isUndefined( this.options.existingTags ) && this.options.existingTags && this.options.existingTags.size() > 0 )
			{ 
				this.options.existingTags.each( function(existing)
				{
					if ( item == existing )
					{
						$( this.tagDropdown ).select('li[tvalue="'+item+'"]')[0].hide();
					}
				}.bind(this));
			}
		}.bind(this));
		$(this.tagWrapper).insert({'after': this.tagDropdown});
		
		this.importTags();
		
		// Do global init
		this.parentInit();
		
		/* Is this a touch device? */
		if ( ipb.vars['is_touch'] && ! Object.isUndefined( 'iScroll' ) )
		{
			this.myScroll = new iScroll( this.tagDropdown.id, { hideScrollbar: false, hScroll: true } );
		}
	},
	
	showDropdown: function(){
		this.handlers['doc_click'] = $(document.body).on('click', function(e){
			if( !$(e.target).descendantOf( $( this.tagDropdown ) ) && !$(e.target).descendantOf( $( this.tagWrapper ) ) ){
				this.hideDropdown();
			}
		}.bindAsEventListener(this));
		
		this.handlers['doc_key'] =	$(document).on('keypress', function(e){
			if( e.keyCode == Event.KEY_ESC ){
				this.hideDropdown();
			}
		}.bindAsEventListener(this));
		
		new Effect.Appear( $( this.tagDropdown ), { duration: 0.2 } );
		
		/* Is this a touch device? */
		if ( ipb.vars['is_touch'] && ! Object.isUndefined( 'iScroll' ) )
		{
			setTimeout(function () { this.myScroll.refresh(); }.bind(this), 300);
		}
	},
	
	hideDropdown: function(){
		try {
			this.handlers['doc_click'].stop();
			this.handlers['doc_key'].stop();
		} catch(err){ }
		
		new Effect.Fade( $( this.tagDropdown ), { duration: 0.3 } );
		
		if ( $( this.tagDropdown ).select('li').findAll(function(el) { return el.visible(); }).size() )
		{
			if ( ! this.options.maxTags )
			{
				this.newInput.show();
			}
			else if( this.options.maxTags && this.calculateTotal() < this.options.maxTags )
			{
				this.newInput.show();
			}
		}
	},
	
	//--------------------------------------------------------------
	// Event Handlers
	//--------------------------------------------------------------
	
	eventAddNewTag: function(e){
		this.newInput.hide();
		this.showDropdown();
		
		Event.stop(e);
		return false;
	},
	
	eventClickItem: function(e){
		var elem = Event.findElement(e, 'li');
		if( !elem.readAttribute('tvalue') ){ return; }
		var tagName = elem.readAttribute('tvalue');
		this.addTag( tagName );
		
		$(elem).hide();	
		
		// See if all options are used
		if( !$( this.tagDropdown ).select('li').findAll(function(el) { return el.visible(); }).size() ){
			this.hideDropdown();
		}
	},
	
	//--------------------------------------------------------------
	// Internal callbacks
	//--------------------------------------------------------------
	
	_afterSelect: function(tag){
		new Effect.Fade( $(this.tagDropdown), { duration: 0.3 } );
		
		if( this.options.maxTags && this.calculateTotal() < this.options.maxTags ){
			this.newInput.show();
		}
	},
	
	_afterAdd: function(item){
		if( this.options.maxTags && this.calculateTotal() >= this.options.maxTags ){
			this.tagDropdown.hide();
			this.newInput.hide();
		}
	},
	
	_afterDelete: function(value){
		if( ( ! this.options.maxTags ) || ( this.options.maxTags && this.calculateTotal() < this.options.maxTags ) ){
			$( this.tagDropdown ).select('li[tvalue="'+value+'"]')[0].show();
		}
	}
}
, Tags.Base));

//==================================================
// 'Open' tagging class (i.e. free choice)
//==================================================
Tags.Open = Class.create( Object.extend( {
	
	initialize: function( element, options ){
		this.element = $( element );
		this.id = this.element.id;
		this.options = options;
		this.handlers = {};
		
		// Create new textbox which will be our typable area
		this.newInput = new Element("input", { 'type': 'text', 'autocomplete': 'off'})
									.addClassName("ipsTagBox_hiddeninput")
									// Bug #34191 - IE9 would submit the page when enter is pressed. Changed to keydown event.
									.observe('keydown', this.eventKeyPress.bindAsEventListener(this))
									.observe('keyup', this.eventCommaPress.bindAsEventListener(this))
									.observe('blur', this.eventBlurTextbox.bindAsEventListener(this));
									
									/*.observe('focus', this.eventFocusTextbox.bindAsEventListener(this))*/

		this.buildWrapper();
		this.newInput.clear();
		this.importTags();
		
		// Do global init
		this.parentInit();
		
		// Set instructional text if there's no entries
		if( !this.calculateTotal() && !$F( this.newInput )){
			$( this.newInput ).addClassName('inactive').value = this.options.lang['tip_text'];
			
			this.handlers['text_focus'] = $( this.newInput ).on('focus', function(e){
				$( this.newInput ).removeClassName('inactive').value = "";
				if( this.options.maxLen ){
					this.newInput.writeAttribute("maxlength", this.options.maxLen - 1); // -1 to leave room for comma
				}
				this.handlers['text_focus'].stop();
			}.bindAsEventListener(this));
		}
	},
	
	//--------------------------------------------------------------
	// Event Handlers
	//--------------------------------------------------------------
	
	eventKeyPress: function(e){	
		//Debug.write( e.keyCode );
		switch( e.keyCode ){
			case Event.KEY_BACKSPACE:
				//alert( this.inputItem.previous().tagName );
				if( !$F(this.newInput) )
				{
					if( this.selectedTag ){
						this.deleteTag( this.selectedTag );
					}
					else
					{
						if( this.inputItem.previous() ){
							this.selectTag( this.inputItem.previous() );
						}
					}
				}				
			break;
			case Event.KEY_DELETE:
				if( this.selectedTag ){
					this.deleteTag( this.selectedTag );
				}
			break;	
			case Event.KEY_RETURN:
			case Event.KEY_TAB:			
				// Bug #30286 - arabic/hebrew keyboards have a different character on
				// key 188. We have to check that the last character is really a latin
				// comma, and ignore it if not.
				//---------------
				// Bug #33967 - typing tags fast caused them to mess up. Instead of checking
				// if the last char is a comma, we now just check whether a comma exists in
				// the string at all.				
				if( (e.keyCode == Event.KEY_TAB) && this.newInput.value == '' )
				{
					return false;
				}
				
				Event.stop(e);
								
				var value = this._stripHtml( this.newInput.value.replace(/\,/, '') );
				if( !value ){
					this.newInput.value = "";
					return false;
				}
								
				this.addTag( value );
				this.newInput.value = "";
			break;
		}
	},
	
	// Separate event for comma press - event is keyup, not keydown, so that
	// international keyboard that share the comma key still work.
	eventCommaPress: function(e){
		// 188 = comma
		if( e.keyCode != 188 ){
			return;
		}
		
		if( !this.newInput.value.indexOf(",") ){
			return;
		}
		
		Event.stop(e);
						
		var value = this._stripHtml( this.newInput.value.replace(/\,/, '') );
		if( !value ){
			this.newInput.value = "";
			return false;
		}
						
		this.addTag( value );
		this.newInput.value = "";
	},
	 
	eventBlurTextbox: function(e){
		if( this.newInput.value != '' && this.newInput.value != ',' )
		{
			var value = this.newInput.value.replace(/\,/, '');
			this.addTag( value );
			this.newInput.value = "";
		}
	},
	
	_stripHtml: function( value )
	{
		var remove = [ '<', '>', '"', "'" ];
		
		remove.each( function(item)
		{
			value = value.replace( new RegExp( item, 'g' ), '' );
		} );
		
		return value;
	},
	
	//--------------------------------------------------------------
	// Internal callbacks
	//--------------------------------------------------------------
	
	_afterSelect: function(tag){
		$( this.newInput ).setValue("").focus();
	},
	
	_afterAdd: function(item){
		if( this.options.maxTags && this.calculateTotal() >= this.options.maxTags ){
			this.newInput.hide();
		}
	},
	
	_afterDelete: function(tag){
		if( this.options.maxTags && this.calculateTotal() < this.options.maxTags ){
			this.newInput.show().focus();
		}
	}
}
, Tags.Base));

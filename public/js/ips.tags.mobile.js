var Tags = {};

Element.addMethods( {
	mobileTagify: function( element, options )
	{
		document.observe("dom:loaded", function() 
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
		});
	}
});

//==================================================
// Base class for tag operations
//==================================================
Tags.Base = {
	parentInit: function(){
		// Elements
		this.launchButton = $( this.id + '_tag_button' );
		this.tagPane = $(this.id + '_tag_pane');
		this.shade = $('shade');
		this.tagHolder = $(this.id + '_tags');
		
		// Events
		$( this.launchButton ).observe('click', this.toggleTagBox.bindAsEventListener(this) );
		$( this.id + '_close_pane').observe('click', this.toggleTagBox.bindAsEventListener(this) );
		$( this.tagHolder ).on('click', 'li', this.selectTag.bind(this) );
		$( this.id + '_tag_remove' ).observe('click', this.removeTags.bindAsEventListener(this) );
		
		// Store a reference to this on the tag box
		this.element.store('obj', this);
	},
	
	toggleTagBox: function(e){
		if( this.tagPane.visible() ){
			this.shade.hide();
			this.tagPane.hide();
		} else {
			this.shade.show();
			this.tagPane.show();
		}
	},
	
	buildTag: function( tag ){
		
		if( parseInt(this.options.forceLowercase) ){
			tag = tag.toLowerCase();
		}
		
		var newTag = new Element("li")
					.writeAttribute("tValue", tag)
					.update( tag );
					
		this.tagHolder.insert({bottom: newTag});
		
		// Add to hidden input
		this.element.value += tag+',';
		
		if( Object.isFunction( this._afterAdd ) ){
			this._afterAdd(tag);
		}
	},
	
	selectTag: function(e, elem){
		if( $(elem).hasClassName('selected') ){
			$(elem).removeClassName('selected');
		} else {
			$(elem).addClassName('selected');
		}
		
		this.checkSelectedTags();	
	},
	
	removeTags: function(e){
		Event.stop(e);
		
		// How many selected?
		var selected = $( this.tagHolder ).select('li.selected');
		
		if( !selected.size() ){
			this.checkSelectedTags(); // Refresh the display
			return;
		}
		
		selected.each( function(item){
			var value = $( item ).readAttribute('tValue');
			$(item).remove();
			this.element.value = this.element.value.replace(value+',', '');
			
			if( Object.isFunction( this._afterDelete ) ){
				this._afterDelete( value );
			}
		}.bind(this));
		this.checkSelectedTags(); // Refresh the display
	},
	
	checkSelectedTags: function(){
		var count = this.tagHolder.select("li.selected").size();
		
		if( count ){
			this.tagHolder.addClassName('with_selected');
			$( this.id + '_addwrap').hide();
			$( this.id + '_removewrap').show();
		} else {
			this.tagHolder.removeClassName('with_selected');
			$( this.id + '_addwrap').show();
			$( this.id + '_removewrap').hide();
		}
	},
	
	calculateTotal: function(){
		return this.tagHolder.select("li").size();		
	},
	
	getExisting: function(){
		if( this.options.existingTags && this.options.existingTags.size() ){
			$A(this.options.existingTags).each( function(item){
				if( !item.blank() ){
					this.buildTag( item );
				}
			}.bind(this));
		}
	},
	
	updateEditorControls: function(){
		if( this.options.maxTags && this.calculateTotal() >= this.options.maxTags ){
			$( this.id + '_addwrap').setStyle('opacity: 0.4');
			$( this.inputBox ).disable();
		} else {
			$( this.id + '_addwrap').setStyle('opacity: 1');
			$( this.inputBox ).enable();
		}
	},
};

//==================================================
// 'Open' tagging class (i.e. free choice)
//==================================================
Tags.Open = Class.create( Object.extend( {
	
	initialize: function( element, options ){
		this.element = $( element );
		this.id = this.element.id;
		this.options = options;
		this.handlers = {};
		
		this.inputBox = $( this.id + '_tag_input' );

		$( this.id + '_tag_add' ).observe('click', this.submitTag.bindAsEventListener(this) );
		
		if( this.options.maxLen ){
			this.inputBox.writeAttribute("maxlength", this.options.maxLen - 1); // -1 to leave room for comma
		}
		
		this.parentInit();
		this.getExisting();
	},
	
	submitTag: function(e){
		Event.stop(e);
		
		var value = this.inputBox.value.strip();
		if( !value ){ return; }
		
		this.buildTag( value );
		this.inputBox.value = '';		
	},
	
	//--------------------------------------------------------------
	// Internal callbacks
	//--------------------------------------------------------------
	
	_afterAdd: function(item){
		this.updateEditorControls();
	},
	
	_afterDelete: function(item){
		this.updateEditorControls();
	}
	
}, Tags.Base));


//==================================================
// 'Closed' tagging class
//==================================================
Tags.Closed = Class.create( Object.extend( {
	
	initialize: function( element, options ){
		this.element = $( element );
		this.id = this.element.id;
		this.options = options;
		this.handlers = {};
		
		this.inputBox = $( this.id + '_tag_select' );

		$( this.id + '_tag_add' ).observe('click', this.submitTag.bindAsEventListener(this) );
		
		this.parentInit();
		this.getExisting();
	},
	
	submitTag: function(e){
		Event.stop(e);
		var value = this.inputBox.value;
		this.buildTag( value );		
	},
	
	//--------------------------------------------------------------
	// Internal callbacks
	//--------------------------------------------------------------
	
	_afterAdd: function(item){
		var option = this.inputBox.select("option[value=" + item + "]")[0];
		
		if( option ){
			option.remove();
		}
		
		this.updateEditorControls();
	},
	
	_afterDelete: function(item){
		var elem = new Element("option", { value: item }).update(item);
		this.inputBox.insert({ bottom: elem });		
		this.updateEditorControls();
	}
	
}, Tags.Base));


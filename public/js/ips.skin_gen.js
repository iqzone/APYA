/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.skin_gen.js - VSE						*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier, Matt Mecham			*/
/************************************************/

var defaultTemplates = {
	css_wrapper: new Template("#{selector} {\n#{contents}\n}"),
	bg_color: new Template("\t\nbackground: #{value} !important;\tbackground-color: #{value} !important;\n"),
	bgcolonly_color: new Template("\t\n\tbackground-color: #{value} !important;\n"),
	font_color: new Template("\tcolor: #{value};\n"),
	border_color: new Template("\tborder-color: #{value} !important;\n"),
	box_shadow_color: new Template("\tbox-shadow: #{extra} #{value} !important;\n\t-moz-box-shadow: #{extra} #{value} !important;\n\n\t-webkit-box-shadow: #{extra} #{value} !important;")
}

var skingen = {
	
	currentClass: null,
	showingLocations: false,
	classList: [],
	classStored: {},
	updatedImages: $H(),
	classBackup: null,
	pickers: {},
	
	//------------------------------------------
	// Initialize
	//------------------------------------------
	boot: function(){
		if( !this.checkDB() ){
			alert("You need to use a browser that supports localStorage, until we add support for Gears");
			return;
		}
		
		/* Fix image */
		$('logo').down('img').writeAttribute('src', ipb.vars['img_url'] + "/logo_transparent.png" );
		
		this.dropper._parent = this.xray._parent = this.db._parent = this.cssManipulator._parent = this.imageEditor._parent = this.hueChanger._parent = this;
		
		this.loadStoredValues();
		
		if( this.db.load("storedImages") ){
			this.loadStoredImages();
		}
		
		this.buildEditor();
		this.buildMasterClassList();
		
		this.cssManipulator.createInjectedStylesheet();
	},
	
	//-----------------------------------------
	// Get hex from selector value
	//-----------------------------------------
	getHexFromSelector: function( val, type )
	{
		if ( type == 'background-color' )
		{
			type = 'background';
		}
		
		Debug.write( val + '-' + type );
		
		if ( ! Object.isUndefined( val ) )
		{
			if ( typeof( val ) == 'string' )
			{
				val = this.skinClasses.get( val )[3][ type ];
				
				if ( Object.isUndefined( val ) || Object.isUndefined( val[0] ) )
				{
					return false;
				}
				else
				{
					Debug.write( "Got " + val[0] + ' - ' + val[1] );
				}
			}
			
			if ( ! Object.isUndefined( val[0] ) )
			{
				return this._cleanHex( val[0] );
			}
		}
		
		return false;
	},
	
	//------------------------------------------
	// Builds the panel
	//------------------------------------------
	buildEditor: function(){
		if( $('skingen_editor') ){ return; }
		pushLater = new Array();
		
		this.body = $$('body')[0]; // quick reference to body
		// Build list
		var list = [];
		if( this.skinClasses.size() && this.skinGroups.size() ){
			this.skinGroups.each( function( group ){
				var items = [];
				if( group.value[1].length ){
					$H(group.value[1]).each( function(item){
						item = item[1];
						if( this.skinClasses.get( item ) )
						{
							var bg = this.getHexFromSelector( item, 'background' );
							var fc = this.getHexFromSelector( item, 'color' );
							
							items.push( this.templates.sectionItem.evaluate( { id: item, name: this.skinClasses.get( item )[1], style: 'background: ' + ( bg ? bg : fc ) } ) );
						}
					}.bind(this));
					
					if ( group.value[0].match( /^app: /i ) )
					{
						pushLater.push( this.templates.sectionGroup.evaluate( { id: group.key, title: group.value[0], content: items.join('') } ) );
					}
					else
					{
						list.push( this.templates.sectionGroup.evaluate( { id: group.key, title: group.value[0], content: items.join('') } ) );
					}
				}
			}.bind(this));
		}
		
		// Build HTML
		$(this.body).insert({bottom: this.templates.editor.evaluate( { sections: list.join('') + pushLater.join('') } )});
		
		// Existing coords?
		if( this.db.load('editorPos') ){
			var newPos     = this.db.load('editorPos');
			var screendims = document.viewport.getDimensions();
			var wrapSize   = $('skingen_editor').getDimensions();
			
			if ( newPos.left > ( screendims.width - wrapSize.width ) )
			{
				newPos.left = ( ( screendims.width - wrapSize.width ) / 2 );
			}
			
			if ( newPos.left < 1 )
			{
				newPos.left = ( ( screendims.width - wrapSize.width ) / 2 );
			}
			
			if ( newPos.top > ( screendims.height - wrapSize.height ) || newPos.top < 0)
  			{
       			newPos.top = ( ( screendims.height - wrapSize.height ) / 2 );
   			}
   
			$('skingen_editor').setStyle('left: ' + newPos.left + 'px; top: ' + newPos.top + 'px');
		}
		
		if( this.db.load('editorState') && this.db.load('editorState') == 'hide' ){
			$('skingen_content').hide();
		}
		
		// Any pane loaded?
		if( this.db.load('editorPane') ){
			var current = this.db.load('editorPane');
		} else {
			var current = 'body';
		}
		
		// Build the first pane
		this.showPane( current );
		
		// Make draggable
		new Draggable( 'skingen_editor', { handle: $('skingen_editor').down('h1'), onEnd: this.updateDragPos.bind(this) } );
		
		// Add Events
		this.setUpEditorEvents();
	},
	
	//------------------------------------------
	// Builds a simple array of classnames from our
	// customizable list. Used for matching.
	//------------------------------------------
	buildMasterClassList: function(){
		this.classList = [];
		this.skinClasses.each( function(item){
			this.classList.push( item.value[0] );
		}.bind(this));
	},
	
	
	//-----------------------------------------
	// Get the template for CSS we'll need
	//-----------------------------------------
	getClassTemplate: function( classArray )
	{
		template = defaultTemplates.css_wrapper;
		name     = classArray[0];
		contents = '';
		
		if ( ! Object.isUndefined( classArray[3] ) )
		{
			$H(classArray[3]).each( function( dec )
			{
				switch( dec.key )
				{
					case 'background':
						if ( classArray[1] == 'Search Button' )
						{
							contents += defaultTemplates.bgcolonly_color.evaluate( { value: dec.value[0], extra: dec.value[1] } );
						}
						else
						{
							contents += defaultTemplates.bg_color.evaluate( { value: dec.value[0], extra: dec.value[1] } );
						}
					break;
					case 'color':
						contents += defaultTemplates.font_color.evaluate( { value: dec.value[0], extra: dec.value[1] } );
					break;
					case 'border':
						contents += defaultTemplates.border_color.evaluate( { value: dec.value[0], extra: dec.value[1] } );
					break;
					case 'boxshadow':
						contents += defaultTemplates.box_shadow_color.evaluate( { value: dec.value[0], extra: dec.value[1] } );
					break;
				}
			} );
		}
		
		return template.evaluate( { 'selector': name, 'contents': contents } );
	},
	
	
	//------------------------------------------
	// Events that make the editor work
	//------------------------------------------
	setUpEditorEvents: function(){
		$('skingen_locate').on("click", this.showLocations.bind(this));
		$('skingen_select').on("click", this.selectByClick.bind(this));
		$('skingen_editor').on("dblclick", 'h1', this.toggleEditor.bind(this));
		$('skingen_sections').on('click', 'li', this.showPaneEv.bind(this));
		$('skingen_save').on('click', this.buildSkin.bind(this));
		$('skingen_revert').on('click', this.revertSkin.bind(this));
		$('skingen_sections').on('click', 'h3', this.toggleGroup.bind(this));
		$('skingen_colorize').on('click', this.toggleColorizer.bind(this));
		$('skingen_settings').on('click', this.toggleSettings.bind(this));
		//$('skingen_images').on('click', this.imageEditor.toggleImageEditor.bind( this.imageEditor ));
		
		ipb.delegate.register(".sg_dropper", this.showDropper.bind(this) );
	},
	
	//------------------------------------------
	// This would build the skin CSS,
	// but for now just we just pretend 
	//------------------------------------------
	buildSkin: function(e){
		
		Event.stop(e);
		
		if ( ! $('skingen_buildskin_editor') )
		{
			this.body.insert( { bottom: this.templates.buildSkin.evaluate() } );
		
			new Draggable( 'skingen_buildskin_editor', { handle: $("skingen_buildskin_editor").down('h1') } );
			
			$('skingen_buildskin_editor').hide();
		}
		
		if ( $('skingen_buildskin_editor').visible() )
		{
			new Effect.Appear( $('skingen_editor'), { duration: 0.3 } );
			new Effect.Fade( $('skingen_buildskin_editor'), { duration: 0.2 } );
			
			/* Click handlers */
			$('skingen_buildskin_cancel').stopObserving('click');
			$('skingen_buildskin_save').stopObserving('click');
		}
		else
		{
			/* Position correctly */
			$('skingen_buildskin_editor').setStyle( { 'top': $('skingen_editor').getStyle('top'), 'left': $('skingen_editor').getStyle('left') } );
		
			new Effect.Appear( $('skingen_buildskin_editor'), { duration: 0.3 } );
			new Effect.Fade( $('skingen_editor'), { duration: 0.2 } );
			
			/* Click handlers */
			$('skingen_buildskin_cancel').on( 'click', this.buildSkin.bindAsEventListener(this) );
			$('skingen_buildskin_save').on( 'click', this.buildSkinProcess.bindAsEventListener(this) );
		}

	},
	
	//------------------------------------------
	// Throw everything back to PHP
	//------------------------------------------
	buildSkinProcess: function(e)
	{
		/* Fix form */
		$('skingen_buildskin_save').writeAttribute('disabled', 'true' );
		$('skingen_buildskin_save').update( 'Saving...' );
		
		var css = [];
		
		this.skinClasses.each( function(item){
			css.push( this.buildRenderedRule( item.key ) );
		}.bind(this) )
		
		output = css.join("\n\n");
		
		var settings = this.db.load("storedSettings");
		var width    = ( Object.isUndefined( settings['width_value'] ) ) ? '87' : settings['width_value'];
		var px_pc    = ( Object.isUndefined( settings['width_type'] ) )  ? '%' : settings['width_type'];
		
		output += '#content, .main_width { width: ' + width + px_pc + ' !important; }';
		
		output += ".topic_buttons li.non_button a {\
		background: transparent !important;\
		background-color: transparent !important;\
		border: 0 !important;\
		box-shadow: none !important;\
		-moz-box-shadow: none !important;\
		-webkit-box-shadow: none !important;\
		text-shadow: none !important;\
		min-width: 0px;\
		color: #777777;\
		font-weight: normal;\
		}";

		Debug.write( $H( this.db.load("storedClasses") ).toJSON() );
		var params = { 'storedClasses' : JSON.stringify( this.db.load("storedClasses") ),
					   'storedSettings': JSON.stringify( this.db.load("storedSettings") ),
					   'css': output };
					   
		var url    = ipb.vars['base_url'] + 'app=core&section=skingen&module=ajax&do=save&md5check=' + ipb.vars['secure_hash'];
		Debug.write( url );
		new Ajax.Request( url,
							{
								method: 'post',
								parameters: params,
								onSuccess: function(t)
								{
									if ( t.responseJSON['status'] == 'ok' )
									{
										new Effect.Fade( $('skingen_buildskin_editor'), { duration: 0.1 } );
										
										/* Clear storage */
										this.dropStoredValues();
										
										/* Reload yo */
										document.location.reload();
									}
									else
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
								}.bind(this)
							});
	},
	
	//------------------------------------------
	// Reverts a skin to the default values
	// and drops the database
	//------------------------------------------
	revertSkin: function(e){
		if( confirm("Are you sure you want to scrap ALL of the changes you've made?") ){
			this.dropStoredValues();
			this.dropStoredImages();
			document.location.reload();
		}		
	},
	
	//------------------------------------------
	// Toggles a style group
	//------------------------------------------
	toggleGroup: function(e, elem){
		var group = $(elem).readAttribute('data-group');
		
		if( elem.hasClassName('open') && $('skingen_group_' + group).visible() ){
			new Effect.BlindUp( $('skingen_group_' + group), { duration: 0.3, afterFinish: function(){
				$(elem).removeClassName('open').addClassName('closed');
			} } );
		} else {
			new Effect.BlindDown( $('skingen_group_' + group), { duration: 0.3, afterFinish: function(){
				$(elem).addClassName('open').removeClassName('closed');
			} } );
		}
	},
	
	//------------------------------------------
	// Toggle the settings panel
	//------------------------------------------
	toggleSettings: function(e){
		Event.stop(e);
		
		if( !$('skingen_settings_editor') ){
			this.buildSettings();
		}
		
		if ( $('skingen_settings_editor').visible() )
		{
			new Effect.Appear( $('skingen_editor'), { duration: 0.3 } );
			new Effect.Fade( $('skingen_settings_editor'), { duration: 0.2 } );
		}
		else
		{
			new Effect.Appear( $('skingen_settings_editor'), { duration: 0.3 } );
			new Effect.Fade( $('skingen_editor'), { duration: 0.2 } );
		}
	},
	
	//------------------------------------------
	// Build the settings panel
	//------------------------------------------
	buildSettings: function(e){
		settings = this.db.load("storedSettings");
		
		var width = ( Object.isUndefined( settings['width_value'] ) ) ? '87' : settings['width_value'];
		var px_pc = ( Object.isUndefined( settings['width_type'] ) )  ? '%' : settings['width_type'];
		
		this.body.insert( { bottom: this.templates.settingsEditor.evaluate( { 'width': width } ) } );
		
		/* Set drop down up correctly */
		if ( px_pc == 'px' )
		{
			$('skingen_setting_widthunit').options[0].selected = true;
		}
		else
		{
			$('skingen_setting_widthunit').options[1].selected = true;
		}
		
		/* Position correctly */
		$('skingen_settings_editor').setStyle( { 'top': $('skingen_editor').getStyle('top'), 'left': $('skingen_editor').getStyle('left') } );
		
		/* Click handlers */
		$('skingen_settings_cancel').on( 'click', this.toggleSettings.bindAsEventListener(this) );
		$('skingen_settings_save').on( 'click', this.saveSettings.bindAsEventListener(this) );
		
		$('skingen_settings_editor').hide();
		
		new Draggable( 'skingen_settings_editor', { handle: $("skingen_settings_editor").down('h1') } );
	},
	
	//-----------------------------------------
	// Save settings
	//-----------------------------------------
	saveSettings: function(e)
	{
		Event.stop(e);
		
		var width = $F('skingen_setting_width');
		var px_pc = ( $F('skingen_setting_widthunit') == 'percent' ) ? '%' : 'px';
		
		if ( px_pc == 'px' && parseInt( width ) < 900 )
		{
			width = 900;
		}
		else if ( px_pc == 'pc' && parseInt( width ) < 60 )
		{
			width = 60;
		}
		
		/* write */
		this.db.write("storedSettings", { 'width_value': width, 'width_type': px_pc } );
		
		/* update stylesheet */
		this.cssManipulator.updateWidth();
		

		this.toggleSettings(e);
	},
	
	//------------------------------------------
	// Shows the colorizer
	//------------------------------------------
	toggleColorizer: function(e){
		Event.stop(e);
		
		if( !$('skingen_colorize_editor') ){
			this.buildColorizer();			
		}
		
		if( $('skingen_colorize_editor').visible() ){
			new Effect.Appear( $('skingen_editor'), { duration: 0.3 } );
			new Effect.Fade( $('skingen_colorize_editor'), { duration: 0.2 } );
			$('skingen_colorize').removeClassName('active');
			this.db.write("currentWindow", "main");
			return;
		} else {
			// Back up our class values temporarily, so that
			// we can cancel the change if necessary
			var backup = {};
			
			this.skinClasses.each( function(item){
				backup[ item.key ] = item.value[3];
			}.bind(this));
			
			this.db.write("classBackup", backup);
			
			/* Position correctly */
			$('skingen_colorize_editor').setStyle( { 'top': $('skingen_editor').getStyle('top'), 'left': $('skingen_editor').getStyle('left') } );
			
			// Fade main window/show colorizer
			new Effect.Fade( $('skingen_editor'), { duration: 0.3 } );
			new Effect.Appear( $('skingen_colorize_editor'), { duration: 0.2 } );
			$('skingen_colorize').addClassName('active');
			this.db.write("currentWindow", "colorize");
			return;
		}
	},
	
	//-----------------------------------------
	// Returns BG color of colourizer group
	//-----------------------------------------
	getFirstBackgroundColorOfColorizerGroup: function( group )
	{
		var hex 	   = this.getHexFromSelector( 'maintitle', 'background' );
		var background = false;
		var text	   = false;
		
		if ( ! Object.isUndefined(this.colorizeGroups.get( group )) )
		{
			this.colorizeGroups.get( group ).each( function(item)
			{
				if ( $H( this.skinClasses ).get( item ) )
				{
					$H(this.skinClasses.get( item )[3] ).each( function(property)
					{ 
						if ( background === false && property.key == 'background' )
						{
							background = this.getHexFromSelector( item, 'background' );
						}
						
						if ( text === false && property.key == 'color' )
						{
							text = this.getHexFromSelector( item, 'color' );
						}
						
					}.bind(this) );
				}
			}.bind(this) );
		}
		
		/* Failsafe */
		return ( background ) ? background : ( text ? text : hex );
	},
	
	//------------------------------------------
	// Builds the colorizer window
	//------------------------------------------
	buildColorizer: function(){
		this.body.insert( { bottom: this.templates.colorizeEditor.evaluate() } );
		$('skingen_colorize_editor').hide();
		new Draggable( 'skingen_colorize_editor', { handle: $("skingen_colorize_editor").down('h1') } );
		
		// Set up pickers
		['base', 'secondary', 'tertiary', 'text'].each( function(item){
			new colorPicker( 'skingen_colorize_' + item, {
					color: this.getFirstBackgroundColorOfColorizerGroup( item ),
	 				livePreview: true,
	 				previewElement: 'skingen_colorize_' + item,
	 				onHide: this.updateColorizer.bind(this, item),
					onSubmit: this.updateColorizer.bind(this, item)
			});
		}.bind(this));
		
		$('skingen_colorize_cancel').on('click', this.cancelColorizer.bind(this));
		$('skingen_colorize_save').on('click', this.saveColorizer.bind(this));
		return;
	},
	
	//------------------------------------------
	// Event handler for colorizer picker
	// Updates all of our classes with a new color
	//------------------------------------------
	updateColorizer: function( type, picker ){
		var newHue = picker.color['h'];
		var newSat = picker.color['s'];
		
		if ( ! Object.isUndefined( this.colorizeGroups.get( type ) ) )
		{
			this.colorizeGroups.get( type ).each( function(item){
				if( this.skinClasses.get( item )){
					$H(this.skinClasses.get( item )[3]).each( function(property){
						if( property.key == 'background' || property.key == 'color' || property.key == 'border'  || property.key == 'boxshadow'  ){
							val = this.getHexFromSelector( item, property.key );
							if ( val )
							{
								this.skinClasses.get( item )[3][ property.key ][0] = '#' + this.hueChanger.convertHex( val, newHue, newSat );
							}
						}
					}.bind(this));
					// Now manipulate the CSS for the full preview
					this.cssManipulator.updateSelector( item );			
				}
			}.bind(this));
		}
	},
	
	//------------------------------------------
	// Cancels a colorize change without saving
	//------------------------------------------
	cancelColorizer: function(e){
		var backup = $H( this.db.load('classBackup') );

		backup.each( function(item){
			this.skinClasses.get( item.key )[3] = item.value;
		}.bind(this));
		
		this.skinClasses.each( function(item){
			this.cssManipulator.updateSelector( item.key );
		}.bind(this));
		
		this.db.write('classBackup', false);
		this.toggleColorizer( e );
	},
	
	//------------------------------------------
	// Save the changes that have been made
	//------------------------------------------
	saveColorizer: function(e){
		this.updateStoredValues();
		this.resetTextboxValues();
		this.db.write('classBackup', false);
		this.toggleColorizer( e );
	},
	
	//------------------------------------------
	// Updates all of the textbox pickers with current values
	//------------------------------------------
	resetTextboxValues: function( id ){
		this.skinClasses.each( function(item){
			if( Object.isUndefined( this.pickers[ item.key ] ) ){
				return;
			}
			 
			$H( item.value[3] ).each( function(style){
				if( !Object.isUndefined( this.pickers[ item.key ][ style.key ] ) ){
					var val = this.getHexFromSelector( style.value, style.key );
					
					this.pickers[ item.key ][ style.key ].setColor( val.replace('#', '') );
					this.pickers[ item.key ][ style.key ].el.value = val.replace('#', '');
					$( this.pickers[ item.key ][ style.key ].options.previewElement ).setStyle( { 'background-color': val } );
				}
			}.bind(this));
		}.bind(this));			
	},
	
	//------------------------------------------
	// Shows an editor pane
	//------------------------------------------
	showPaneEv: function(e, elem){
		this.showPane( elem.id.replace("section_", '') );
	},	
	showPane: function( id, otherClasses ){		
		$$("#skingen_editor .skingen_pane").invoke("hide");
		
		if( $('skingen_pane_' + id ) ){
			$('skingen_pane_' + id ).show();
		} else {
			this.buildPane( id );
		}
		
		if ( ! Object.isUndefined( otherClasses ) )
		{
			$('skingen_pane_' + id + '_others').stopObserving('click');
			$('skingen_pane_' + id + '_others').update( this.buildOtherClassesForPane( otherClasses ) ).show();
			$('skingen_pane_' + id + '_others').on('click', 'li', this.showPaneEv.bind(this));
		}
		else
		{
			$('skingen_pane_' + id + '_others').hide();
		}
		
		this.currentClass = id;
		this.db.write("editorPane", id);
	},
	//-----------------------------------------
	// Show others
	//-----------------------------------------
	buildOtherClassesForPane: function( otherClasses )
	{
		var items = [];
		
		$H(otherClasses).each( function(item){
			item = item[1];
			
			if( this.skinClasses.get( item ) )
			{
				var bg = this.getHexFromSelector( item, 'background' );
				var fc = this.getHexFromSelector( item, 'color' );
				
				items.push( this.templates.sectionItem.evaluate( { id: item, name: this.skinClasses.get( item )[1], style: 'background: ' + ( bg ? bg : fc ) } ) );
			}
		}.bind(this));
		
		return this.templates.otherClassesGroup.evaluate( { content: items.join('') } );
	},
	
	//------------------------------------------
	// Builds an editor pane
	//------------------------------------------
	buildPane: function( id ){
		var content = [];
		var colors = [];
		var borders = [];
		var classInfo = this.skinClasses.get( id );
	
		$H(classInfo[3]).each( function(item){
			switch( item.key ){
				case 'background':
					colors.push( this.templates.widgetBackground.evaluate({ id: id, current: item.key }) );
				break;
				case 'color':
					colors.push( this.templates.widgetForeground.evaluate({ id: id, current: item.key }) );
				break;
				case 'border':
					borders.push( this.templates.widgetBorder.evaluate({ id: id, current: item.key }) );
				break;
				case 'boxshadow':
					borders.push( this.templates.widgetBoxShadow.evaluate({ id: id, current: item.key }) );
				break;
			}
		}.bind(this));
		
		if( colors.length ){
			content.push( this.templates.sectionColors.evaluate({ id: id, content: colors.join('') }) );
		}
		
		if( borders.length ){
			content.push( this.templates.sectionBorders.evaluate({ id: id, content: borders.join('') }) );
		}
		
		var pane = this.templates.paneWrap.evaluate({ id: id, selector: classInfo[0], name: classInfo[1], description: classInfo[2], content: content.join('') });
		$('skingen_panes').insert({bottom: pane});

		// Set up color pickers
		[ 'background', 'color', 'border', 'boxshadow' ].each( function(item){
			if( $( item + '_' + id ) ){
				if( !this.pickers[ id ] ){
					this.pickers[ id ] = {};
				}

				this.pickers[ id ][ item ] = this.activatePicker( id, item );
			}
		}.bind(this) );
	},
	
	//------------------------------------------
	// Creates a new color picker object
	//------------------------------------------
	activatePicker: function( id, type ){
		var val = this.getHexFromSelector( id, type );
		
		return new colorPicker( type + '_' + id, {
				color: val,
 				livePreview: true,
 				previewElement: type + '_preview_' + id,
 				onHide: this.updateColor.bind(this),
				onSubmit: this.updateColor.bind(this),
				onLoad: function(picker){
					picker.el.value = val.replace('#', '')
				}.bind(this)
		});		
	},
	//-----------------------------------------
	// Update picker from external source
	//-----------------------------------------
	updatePicker: function( id, type, color )
	{
		this.pickers[ id ][ type ].setColor( color );
		
		$( type + '_' + id ).value = color;
		
		this.updateColor( this.pickers[ id ][ type ] ); 
	},
	
	//------------------------------------------
	// Event handler for the picker
	//------------------------------------------
	updateColor: function(picker){
		// Get the element
		var elem = picker.el;
		if( !elem.id.match( this.currentClass ) ){ return; }
		
		// Type
		var type = $(elem).readAttribute('data-type');
		var obj = {};
		
		if( type == 'background' || type == 'color' || type == 'border' || type == 'boxshadow' ){
			elemValue = '#' + $(elem).value;
		} else {
			elemValue = $(elem).value;
		}
		
		obj[ type ] = elemValue;
		
		this.updateClassList( this.currentClass, obj );
		
		this.updatePreview( this.currentClass, obj );
		
		// Now manipulate the CSS for the full preview
		this.cssManipulator.updateSelector( this.currentClass );
		
		return true;
	},
	
	//------------------------------------------
	// Updates the preview boxes
	//------------------------------------------
	updatePreview: function( selector, values ){		
		values = $H(values);
		
		if( values.size() ){
			values.each( function(item){
				$('section_' + selector).down('span').setStyle( { 'backgroundColor' : item.value + ' !important' } );
			}.bind(this));
		}
	},
	
	//------------------------------------------
	// Updates the master class list with new values
	//------------------------------------------
	updateClassList: function( selector, values ){		
		values = $H(values);
		if( values.size() ){
			values.each( function(item){
				this.skinClasses.get( selector )[3][ item.key ][0] = item.value;
			}.bind(this));
		}
		
		this.updateStoredValues();				
	},
	
	//------------------------------------------
	// Builds an object we can write to the database
	// for cross-page styling
	//------------------------------------------
	updateStoredValues: function(){
		this.classStored = {};
		
		this.skinClasses.each( function(item){
			this.classStored[ item.key ] = item.value[3];
		}.bind(this));
		
		this.db.write("storedClasses", this.classStored);		
	},
	
	loadStoredValues: function(){
		var storedValues   = this.db.load("storedClasses");
		var storedSettings = this.db.load("storedSettings");
		
		if ( ! storedValues && typeof(IPS_SKIN_GEN_SAVED_DATA) != 'undefined' )
		{
			if ( ! Object.isUndefined( IPS_SKIN_GEN_SAVED_DATA['storedClasses'] ) )
			{
				storedValues = $H( IPS_SKIN_GEN_SAVED_DATA['storedClasses'] );
			}
		}
		
		if ( ! storedSettings && typeof(IPS_SKIN_GEN_SAVED_DATA) != 'undefined' )
		{
			if ( ! Object.isUndefined( IPS_SKIN_GEN_SAVED_DATA['storedSettings'] ) )
			{
				this.db.write("storedSettings", $H( IPS_SKIN_GEN_SAVED_DATA['storedSettings'] ) );
			}
		}
		
		$H(storedValues).each( function(item){
			this.skinClasses.get( item.key )[3] = item.value;
		}.bind(this));
	},
	
	dropStoredValues: function(){
		this.db.write("storedClasses", false);
		this.db.write("storedSettings", false );
	},
	
	updateStoredImages: function(){
		this.db.write("storedImages", this.updatedImages);
	},
	
	loadStoredImages: function(){
		/*var storedImages = this.db.load("storedImages");
		
		$H( storedImages ).each( function(item){
			this.imageEditor.updateImageReferences( item.value, item.key );
		}.bind(this));*/
	},
	
	dropStoredImages: function(){
		this.db.write("storedImages", false);
	},
	
	//------------------------------------------
	// Start the XRay
	//------------------------------------------
	selectByClick: function(e){
		Event.stop(e);
		this.xray.start();
	},
	
	//-----------------------------------------
	// Do the dropper background_dropper_ setColor 
	//-----------------------------------------
	showDropper: function( e, elem )
	{
		Event.stop(e);
		
		var item = elem.id.replace( /^(.+?)_.*$/, "$1" );
		var id   = elem.id.replace( /^(?:[^_]+?)_(?:[^_]+?)_(.+?)$/, "$1" );
		
		this.dropper.start( item, id );
	},
	
	//------------------------------------------
	// Highlight the current elements
	//------------------------------------------
	showLocations: function(e){
		Event.stop(e);
		
		if( this.showingLocations ){
			$('skingen_locate').removeClassName('active');
			$$('.skingen_highlight').invoke("remove");
			this.showingLocations = false;
			return;
		}
		
		// Find elements matching the selector
		var elements = $$( this.skinClasses.get( this.currentClass )[0] );
		
		if( !elements.size() ){
			alert("This item doesn't exist on this page. Try another page!");
			return;
		} 
		
		$('skingen_locate').addClassName('active');
		
		elements.each( function(item){
			var offset = item.cumulativeOffset();
			var dims = item.getLayout();
			var newElem = new Element('div').addClassName('skingen_highlight').setStyle({
							width: (dims.get('padding-box-width') + 2) + "px",
							height: (dims.get('padding-box-height') + 2) + "px",
							top: (offset.top - 2) + "px",
							left: (offset.left - 2) + "px"
						});
						
			this.body.insert({bottom: newElem});			
		}.bind(this));
		
		this.showingLocations = true;
	},
	
	//------------------------------------------
	// Check whether localStorage is supported
	//------------------------------------------
	checkDB: function(){		
		try {
			return'localStorage' in window && window['localStorage'] !== null;
		} catch (e) {
			return false;
		}
	},
	
	//------------------------------------------
	// Updates the position of the editor pane in the DB
	//------------------------------------------
	updateDragPos: function(e){
		var pos = $('skingen_editor').cumulativeOffset();
		this.db.write("editorPos", pos);
	},
	
	//------------------------------------------
	// Shows/hides the editor
	//------------------------------------------
	toggleEditor: function(e){
		if( $('skingen_content').visible() ){
			new Effect.BlindUp( $('skingen_content'), { duration: 0.3 } );
			this.db.write("editorState", 'hide');
		} else {
			new Effect.BlindDown( $('skingen_content'), { duration: 0.3 } );
			this.db.write("editorState", 'show');
		}		
	},
	
	//------------------------------------------
	// Builds a fully-formed CSS rule
	//------------------------------------------
	buildRenderedRule: function( selector ){
		return this.getClassTemplate( this.skinClasses.get( selector ) );
	},
	
	//-----------------------------------------
	// Clean up hex
	//-----------------------------------------
	_cleanHex: function( hex )
	{
		if ( hex.length == 4 )
		{
			hex = hex.replace( /#/, '' );
			rbg = hex.split('');
			
			hex = '#' + rbg[0] + rbg[0] + rbg[1] + rbg[1] + rbg[2] + rbg[2];
		}
		
		return hex;
	},
	
	//============================================================
	// HUE CHANGER
	// Changes the hue of a class
	//============================================================
	hueChanger: {
		_parent: null,
		
		//------------------------------------------
		// Main function for changing the hue of a hex
		//------------------------------------------
		convertHex: function( hex, toHue, toSat ){
			// Remove #
			hex = hex.replace(/#/, '');
			
			// Check for shorthand hex
			if( hex.length == 3 ){
				hex = hex.slice(0,1) + hex.slice(0,1) + hex.slice(1,2) + hex.slice(1,2) + hex.slice(2,3) + hex.slice(2,3);
			}
			
			if( hex.length != 6 ){
				Debug.write("Not a valid hex");
				return;
			}
			
			Debug.write("Converting " + hex + " to hue " + toHue );
			// Split the hex into pieces, convert to RGB, and create fraction
			var r = ( this.hexToRGB( hex.slice(0,2) ) / 255 );
			var g = ( this.hexToRGB( hex.slice(2,4) ) / 255 );
			var b = ( this.hexToRGB( hex.slice(4,6) ) / 255 );
			
			// Convert to HSL
			var hsl = this.RGBtoHSL( r, g, b );
			
			Debug.dir( hsl );
			
			// Change our hue to a fraction
			hsl[0] = (1 / 360) * toHue;			
			hsl[1] = (1 / 100) * toSat;
			// Back to RGB
			var rgb = this.HSLtoRGB( hsl[0], hsl[1], hsl[2] );
			
			return this.RGBtoHex( rgb[0], rgb[1], rgb[2] );
		},
		
		//------------------------------------------
		// Converts an HSL value to RGB
		//------------------------------------------
		HSLtoRGB: function( h, s, l ){			
			var red = 0;
			var green = 0;
			var blue = 0;
			
			if( s == 0 ){
				red = l * 255;
				green = l * 255;
				blue = l * 255;
			} else {
				if( l < 0.5 ){
					var v2 = l * ( 1 + s );
				} else {
					var v2 = ( l + s ) - ( s * l );
				}
				
				var v1 = 2 * l - v2;
				
				red = 255 * this.HueToRGB( v1, v2, (h + ( 1 / 3 ) ) );
				green = 255 * this.HueToRGB( v1, v2, h );
				blue = 255 * this.HueToRGB( v1, v2, (h - ( 1 / 3 ) ) );
			}
			
			return [ Math.round( red ), Math.round( green ), Math.round( blue ) ];
		},
		
		//------------------------------------------
		// Converts a hue to an RGB decimal value
		//------------------------------------------
		HueToRGB: function( v1, v2, h ){
			if( h < 0 ){ h += 1; }
			if( h > 1 ){ h -= 1; }
			
			if( ( 6 * h ) < 1 ){
				return ( v1 + ( v2 - v1 ) * 6 * h );
			}			
			if( ( 2 * h ) < 1 ){
				return v2;
			}			
			if( ( 3 * h ) < 2 ){
				return ( v1 + ( v2 - v1 ) * ( ( 2 / 3 ) - h ) * 6 );
			}	
			
			return v1;
		},
		
		//------------------------------------------
		// Converts an RGB to a HSL value
		//------------------------------------------
		RGBtoHSL: function( r, g, b ){			
			var lightness, hue, saturation = 0;
			
			var min = [ r, g, b ].min();
			var max = [ r, g, b ].max();
			var delta = max - min;
			
			lightness = ( max + min ) / 2;
			
			if( delta == 0 ){ 	// Grey
				hue = 0;
				saturation = 0;
			} else {
				if( lightness < 0.5 ){
					saturation = delta / ( max + min );
				} else {
					saturation = delta / ( 2 - max - min );
				}
				
				var delta_r = ( ( ( max - r ) / 6 ) + ( delta / 2 ) ) / delta;
				var delta_g = ( ( ( max - g ) / 6 ) + ( delta / 2 ) ) / delta;
				var delta_b = ( ( ( max - b ) / 6 ) + ( delta / 2 ) ) / delta;
				
				if( r == max ){
					hue = delta_b - delta_g;
				} else if( g == max ){
					hue = ( 1 / 3 ) + delta_r - delta_b;
				} else if( b == max ){
					hue = ( 2 / 3 ) + delta_g - delta_r;
				}
				
				if( hue < 0 ){
					hue += 1;
				}
				
				if( hue > 1 ){
					hue -= 1;
				}
			}
			
			return [ hue, saturation, lightness ];
		},
		
		//------------------------------------------
		// Converts a hex to an RGB value
		//------------------------------------------
		hexToRGB: function( hex ){
			return parseInt(hex,16);
		},
		
		RGBtoHex: function( r, g, b ){
			var hex = [ r.toString(16), g.toString(16), b.toString(16) ];
			hex.each(function(val,nr) {
				if(val.length == 1){
					hex[nr] = '0' + val;
				}
			});
			return hex.join('');
		}
	},
	
	//============================================================
	// IMAGE EDITOR
	// Accepts uploads of images
	//============================================================
	imageEditor: {
		_parent: null,
		
		//------------------------------------------
		// Shows/hides the image editor
		//------------------------------------------
		toggleImageEditor: function(e){
			if( typeof FileReader === 'undefined' ){
				alert("Sorry, your browser can't support the image editor");
				return;
			}
			
			if( !$('skingen_images_editor') ){
				this.buildImageEditor();
			}		
		},
		
		//------------------------------------------
		// Builds the image editor window
		//------------------------------------------
		buildImageEditor: function(e){
			var content = [];
			
			// Build image entries
			this._parent.imageReplacements.each( function(item){
				content.push( this._parent.templates.imageEditorItem.evaluate( { 
									id: item.key,
									img: this.buildImage( item ),
									upload: this.buildUploadControl( item ) } ) );				
			}.bind(this));
			
			var imageEditor = this._parent.templates.imageEditor.evaluate( { content: content.join('') } );
			$('skingen_editor').insert( { bottom: imageEditor } );
			
			$('skingen_images_editor').on('click', 'input[type=button][data-img]', this.uploadImage.bind(this));
			
			// Make draggable
			new Draggable( 'skingen_images_editor', { handle: "#skingen_images_editor h1" } );
		},
		
		//------------------------------------------
		// Event handler for uploading an image
		//------------------------------------------
		uploadImage: function( e, elem ){
			// Find upload control
			var img = elem.readAttribute('data-img');
			var thumb = $('img_' + img);
			var uploader = $('skingen_images_content').select("input[type=file][data-img=" + img + "]")[0];
			
			if( !thumb || !uploader || !uploader.files[0] ){ return; }
			
			var file = uploader.files[0];
			
			if( !(/image/i).test( file.type ) ){
				alert("This isn't a valid image file. Please select another!");
				return;
			}
			
			var reader = new FileReader();
			reader.onload = function(e) { 
				this.updateImageReferences( e.target.result, img );
			}.bind(this);
			
			reader.readAsDataURL( file );
		},
		
		//------------------------------------------
		// Updates all instances of an image on the page
		// to use the data url returned by FileReader
		//------------------------------------------
		updateImageReferences: function( dataURL, img ){
			// Update preview image first
			if( $('img_' + img) ){
				$('img_' + img).src = dataURL;
			}
			
			var itemObj = this._parent.imageReplacements.get( img );
			
			// find all places the image is used
			$$( itemObj[1] + " img" ).each( function( item ){
				if( item.src.match( itemObj[0] ) || item.readAttribute('data-img') == img ){
					item.src = dataURL;
					item.writeAttribute('data-img', img);
				}
			});
			
			// Update customized image in the object
			this._parent.updatedImages.set( img, dataURL );
			this._parent.updateStoredImages();
		},
		
		//------------------------------------------
		// Returns an image tag
		//------------------------------------------
		buildImage: function( item ){
			return "<img src='" + ipb.vars['img_url'] + item.value[2] + item.value[0] + "' id='img_" + item.key + "' />";
		},
		
		//------------------------------------------
		// Returns an upload control
		//------------------------------------------
		buildUploadControl: function(item){
			return "<input type='file' id='upload_img_" + item.key + "' data-img='" + item.key + "'  /> <input type='button' id='upload_img_btn_" + item.key + "' data-img='" + item.key + "' value='Save' />";			
		}
	},
	
	//============================================================
	// CSS MANIPULATOR
	// Manipulates CSS in the stylesheet in order to preview styles
	//============================================================
	cssManipulator: {
		_parent: null,
		styleSheet: null,
		
		//------------------------------------------
		// Update one selector in the injected stylesheet
		//------------------------------------------
		updateSelector: function( selector ){
			if( !$('injected_stylesheet') ){
				this.createInjectedStylesheet();
				return;
			}
			
			var getRules = '';
			var styleSheet = this.fetchStyleSheet();
			
			// Build our rule
			var newRule      = this._parent.buildRenderedRule( selector );
			var realSelector = this._unifyRule( this._parent.skinClasses.get( selector )[0] );
			
			Debug.write( "Updating - " + selector + ' - :' + realSelector + ':' );
			
			// Compatibility issue		
			getRules = ( styleSheet['cssRules'] ) ? 'cssRules' : 'rules';			

			// Loop through rules
			for ( rules = 0; rules < styleSheet[ getRules ].length; rules++ )
			{
				Debug.write( "Looking at... :" + styleSheet[ getRules ][ rules ].selectorText + ':' );
				
				if ( this._unifyRule( styleSheet[ getRules ][ rules ].selectorText ) == realSelector )
				{
					Debug.write( "Found it... :" + newRule  + ':');
					
					// Remove rule completely then readd it
					styleSheet.deleteRule( rules );
					styleSheet.insertRule( newRule, rules );
				}
			}
		},
		
		updateWidth: function( selector ){
			if( !$('injected_stylesheet') ){
				this.createInjectedStylesheet();
				return;
			}
			
			var getRules = '';
			var styleSheet = this.fetchStyleSheet();
			
			/* figure out width, etc */
			var settings = this._parent.db.load("storedSettings");
			var width    = ( Object.isUndefined( settings['width_value'] ) ) ? '87' : settings['width_value'];
			var px_pc    = ( Object.isUndefined( settings['width_type'] ) )  ? '%' : settings['width_type'];
			
			// Build our rule
			var newRule = '#content, .main_width { width: ' + width + px_pc + ' !important; }';
			var realSelector = '#content, .main_width';
			
			// Compatibility issue		
			getRules = ( styleSheet['cssRules'] ) ? 'cssRules' : 'rules';			

			// Loop through rules
			for( rules = 0; rules < styleSheet[ getRules ].length; rules++ ){
				if( styleSheet[ getRules ][ rules ].selectorText == realSelector ){
					// Remove rule completely then readd it
					styleSheet.deleteRule( rules );
					styleSheet.insertRule( newRule, rules );
				}
			}
		},
		
		//------------------------------------------
		// Finds and returns the injected stylesheet
		//------------------------------------------
		fetchStyleSheet: function(){
			if( this.styleSheet ){
				return this.styleSheet;
			}
			
			var styleSheets = document.styleSheets;
			
			for( sheet = 0; sheet < styleSheets.length; sheet++ ){
				if( styleSheets[ sheet ].ownerNode.id == 'injected_stylesheet' ){
					this.styleSheet = styleSheets[ sheet ];
					return this.styleSheet;
				}
			}
			
			return false;
		},
		
		//------------------------------------------
		// Build an injected stylesheet from scratch
		//------------------------------------------
		createInjectedStylesheet: function(){
			var output = '';
			
			$H( this._parent.skinClasses ).each( function(item){
				output += this._parent.getClassTemplate( this._parent.skinClasses.get( item.key ) );
			}.bind(this));
			
			/* figure out width, etc */
			var settings = this._parent.db.load("storedSettings");
			var width    = ( Object.isUndefined( settings['width_value'] ) ) ? '87' : settings['width_value'];
			var px_pc    = ( Object.isUndefined( settings['width_type'] ) )  ? '%' : settings['width_type'];
			
			output += '#content, .main_width { width: ' + width + px_pc + ' !important; }';
			
			output += ".topic_buttons li.non_button a {\
		background: transparent !important;\
		background-color: transparent !important;\
		border: 0 !important;\
		box-shadow: none !important;\
		-moz-box-shadow: none !important;\
		-webkit-box-shadow: none !important;\
		text-shadow: none !important;\
		min-width: 0px;\
		color: #777777;\
		font-weight: normal;\
	}";
	
			Debug.write(output);
			var stylesheet = new Element("style", { id: 'injected_stylesheet' }).update( output );
			
						
			
			$$('head')[0].insert( { bottom: stylesheet } );
			
		},
		//-----------------------------------------
		// Cleans rule to make sure spacing isn't an issue
		//-----------------------------------------
		_unifyRule: function( rule )
		{
			rule = rule.replace( /,\s{1,}/g, ',' );
			
			return rule;
		}
	},
	
	//============================================================
	// DB OBJECT
	// Simple DB wrapper with a few standard methods
	//============================================================
	db: {
		_parent: null,
		
		write: function( saveTo, saveValue ){			
			localStorage.setItem("skingen." + saveTo, JSON.stringify( saveValue ) );
		},
		
		load: function( loadKey ){
			if( localStorage.getItem("skingen." + loadKey) ){
				return JSON.parse( localStorage.getItem("skingen." + loadKey) );
			}
			
			return false;
		}
	},
	/* ! xray */
	//============================================================
	// XRAY INSPECTOR
	// Click to select elements, finds IP.Board classes that apply
	//============================================================
	xray: {
		_parent: null,
		matched: null,
		e: {},
		
		//------------------------------------------
		// Find eligible elements, and set events
		//------------------------------------------
		start: function(){
			// All elements except the skingen_editor
			this.exclude = $$("#skingen_editor, #skingen_editor *");
			
			$('skingen_select').addClassName('active');
			
			this.e['md'] = $(document).on('mousedown', this.doXray.bind(this));
			this.e['mm'] = $(document).on('mousemove', this.doFalse.bind(this));
			this.e['mo'] = $(document).on('mouseover', this.doFalse.bind(this));
		},
		
		//------------------------------------------
		// Unset events
		//------------------------------------------
		finish: function(){
			$('skingen_select').removeClassName('active');
			this.exclude = null;
			this.e['md'].stop();
			this.e['mm'].stop();
			this.e['mo'].stop();
			this._parent.body.removeClassName('selecting');
		},
		
		//------------------------------------------
		// mousedown handler; shows the xray flash box
		//------------------------------------------
		doXray: function(e){
			Event.stop(e);
			
			var item = Event.findElement(e);
			
			if( this.exclude.include( item ) ){
				return;
			}
						
			var offset = item.cumulativeOffset();
			var dims = item.getLayout();
			var newElem = new Element('div', { id: 'skingen_xray' }).addClassName('skingen_xray').setStyle({
							width: dims.get('padding-box-width') + "px",
							height: dims.get('padding-box-height') + "px",
							top: (offset.top ) + "px",
							left: (offset.left) + "px"
						}).writeAttribute('elem', id);
						
			this._parent.body.insert({bottom: newElem});
			new Effect.Fade( newElem, { duration: 0.7, afterFinish: function(){ newElem.remove() } } );
			
			this.finish();
			this.findMatchingSelectors( item );
		},
		
		//------------------------------------------
		// Simply return false and set the default cursor
		//------------------------------------------
		doFalse: function(e){
			this._parent.body.addClassName('selecting');
			Event.stop(e);
		},
		
		//------------------------------------------
		// Finds the nearest customizable selector
		// to the clicked element
		//------------------------------------------
		findMatchingSelectors: function( item ){
			// Get ancestors and add item itself to it
			var ancestors = $(item).ancestors();
			ancestors.unshift( item );
			
			var matchedClass = false;
			var className	 = false;
			var others       = new Array();
			
			// Double loop - go through ancestors, checking each class for a match
			var matching = ancestors.find( function(n){
				return this._parent.classList.find( function(m){
					matchedClass = m;
					return $(n).match( m );
				}.bind(this));
			}.bind(this));
			
			if ( ! matchedClass ) {
				alert("Couldn't find any matching styles for this item.");
			}
			
			/* Look for any parents */
			var matching = ancestors.find( function(n)
			{
				var gotIt = this._parent.classList.find( function(m)
				{
					className = m;
					
					if ( matchedClass == className )
					{
						return false;
					}
					else
					{
						return $(n).match( m );
					}
				}.bind(this) );
				
				if ( gotIt && className )
				{
					this._parent.skinClasses.find( function(c)
					{
						if ( c.value[0] == className )
						{
							others.push( c.key );
						}
					}.bind(this) );
				}
			}.bind(this));
			
			// So we know the real classname
			// Now loop through our hash of customizable values
			// and find the one that matches the classname
			this._parent.skinClasses.find( function(c){
				if( c.value[0] == matchedClass ){
					this._parent.showPane( c.key, others );
					return true;
				} else {
					return false;
				}
			}.bind(this));
		}	
	},
	/* ! dropper */
	//============================================================
	// DROPPER
	// Selects a hex from mouse position
	//============================================================
	dropper: {
		_parent: null,
		e: {},
		item: null,
		id: null,
		
		//------------------------------------------
		// Find eligible elements, and set events
		//------------------------------------------
		start: function( item, id ){
			// All elements except the skingen_editor
			this.exclude = $$("#skingen_editor, #skingen_editor *");
			this.item = item;
			this.id   = id;
			
			this.e['md'] = $(document).on('mousedown', this.doDropper.bind(this));
			this.e['mm'] = $(document).on('mousemove', this.doFalse.bind(this));
			this.e['mo'] = $(document).on('mouseover', this.doFalse.bind(this));
		},
		
		//------------------------------------------
		// Unset events
		//------------------------------------------
		finish: function(){
			this.exclude = null;
			this.e['md'].stop();
			this.e['mm'].stop();
			this.e['mo'].stop();
			this._parent.body.removeClassName('selecting');
		},
		
		//------------------------------------------
		// mousedown handler; shows the xray flash box
		//------------------------------------------
		doDropper: function(e){
			Event.stop(e);
			
			var item = Event.findElement(e);
			
			if( this.exclude.include( item ) ){
				return;
			}
						
			var offset = item.cumulativeOffset();
			var dims = item.getLayout();
			var newElem = new Element('div', { id: 'skingen_dropper' }).addClassName('skingen_xray').setStyle({
							width: dims.get('padding-box-width') + "px",
							height: dims.get('padding-box-height') + "px",
							top: (offset.top ) + "px",
							left: (offset.left) + "px"
						}).writeAttribute('elem', id);
						
			this._parent.body.insert({bottom: newElem});
			new Effect.Fade( newElem, { duration: 0.3, afterFinish: function(){ newElem.remove() } } );
			
			this.finish();
			this.setColorPickerFromKnownSelectors( item );
		},
		
		//------------------------------------------
		// Simply return false and set the default cursor
		//------------------------------------------
		doFalse: function(e){
			this._parent.body.addClassName('selecting');
			Event.stop(e);
		},
		
		//------------------------------------------
		// Sets the color picker
		//------------------------------------------
		setColorPickerFromKnownSelectors: function( item ){
			// Get ancestors and add item itself to it
			var ancestors = $(item).ancestors();
			ancestors.unshift( item );
			
			var matchedClass = '';
			
			// Double loop - go through ancestors, checking each class for a match
			var matching = ancestors.find( function(n){ 
				return this._parent.classList.find( function(m){
					matchedClass = m;
					return $(n).match( m );
				}.bind(this));
			}.bind(this));
			
			if( !matchedClass ){
				alert("Couldn't find any matching styles for this item.");
			} 
			
			// So we know the real classname
			// Now loop through our hash of customizable values
			// and find the one that matches the classname
			this._parent.skinClasses.find( function(c){
				if( c.value[0] == matchedClass ){
					try
					{
						var bg = this._parent.getHexFromSelector( c.key, 'background' );
						var fc = this._parent.getHexFromSelector( c.key, 'color' );
						var hex = ( bg ) ? bg : fc;
						
						if ( hex !== false )
						{
							hex = hex.replace( '#', '' );
							
							this._parent.updatePicker( this.id, this.item, hex );
						}
					}
					catch( e ) { }
					
					return true;
				} else {
					return false;
				}
			}.bind(this));
		}	
	},

	
	//============================================================
	// TEMPLATES
	// Various templates for the editor
	//============================================================
	templates: {
		editor: new Template("<div id='skingen_editor' class='skingen_editor'>								\
								<h1>Visual Skin Editor</h1>													\
								<div id='skingen_content' class='skingen_content'>							\
									<div id='skingen_toolbar'>												\
										<!--<a href='#' id='skingen_images'>Edit Images</a>&nbsp;&nbsp;-->	\
										<a href='#' id='skingen_settings'><img src='" + skingen_imgs + "/settings.png' />&nbsp; Skin Settings</a>&nbsp;&nbsp;		\
										<a href='#' id='skingen_colorize'><img src='" + skingen_imgs + "/colorize.png' />&nbsp; Colorizer</a>&nbsp;&nbsp; \
										<a href='#' id='skingen_revert'><img src='" + skingen_imgs + "/delete.png' />&nbsp; Revert</a>&nbsp;&nbsp;		\
										<a href='#' id='skingen_save'><img src='" + skingen_imgs + "/build.png' />&nbsp; Build Skin</a>&nbsp;&nbsp;			\
										<a href='#' id='skingen_locate' class='right' title='Show where this style is used on this page'><img src='" + skingen_imgs + "/show_locations.png' /></a>&nbsp;&nbsp;\
										<a href='#' id='skingen_select' class='right' title='Select an element to style by clicking on it'><img src='" + skingen_imgs + "/select_element.png' /></a>\
									</div>																	\
									<div id='skingen_sections' class='skingen_sections'>#{sections}</div>							\
									<div id='skingen_panes'></div>											\
								</div>																		\
							</div>"),
		otherClassesGroup: new Template("<h3 class='open'>Related Classes</h3><ul>#{content}</ul>"),
		sectionGroup: new Template("<h3 class='open' data-group='#{id}'><a href='#' class='skingen_toggle_group'>&nbsp;</a> #{title}</h3><ul id='skingen_group_#{id}' data-group='#{id}'>#{content}</ul>"),
		sectionItem: new Template("<li id='section_#{id}' class='clearfix'><span style='#{style}' class='color'></span>#{name}</li>"),
		paneWrap: new Template("<div id='skingen_pane_#{id}' class='skingen_pane'><p class='right skingen_selector' title='This is the selector used to style this element in the CSS file'><span>#{selector}</span></p><h2>#{name}</h2><p>#{description}</p><div class='skingen_pane_contents'>#{content}</div><div id='skingen_pane_#{id}_others' class='skingen_sections otherClasses' style='display:none'></div></div>"),
		sectionColors: new Template("<fieldset class='colors'><span class='legend'>Colors</span>#{content}</fieldset>"),
		sectionBorders: new Template("<fieldset class='border'><span class='legend'>Borders</span>#{content}</fieldset>"),
		widgetBackground: new Template("<span class='control' title='Background color'><img src='" + skingen_imgs + "/background.png' /> <input type='text' class='' id='background_#{id}' data-type='background' /><span id='background_preview_#{id}' class='skingen_preview'></span> <a href='#' class='sg_dropper' title='Color picker' id='background_dropper_#{id}'><img src='" + skingen_imgs + "/color-picker.png' /></a></span>"),
		widgetForeground: new Template("<span class='control' title='Text color'><img src='" + skingen_imgs + "/color.png' /> <input type='text' class='' id='color_#{id}' data-type='color' /><span id='color_preview_#{id}' class='skingen_preview'></span> <a href='#' class='sg_dropper' title='Color picker' id='color_dropper_#{id}'><img src='" + skingen_imgs + "/color-picker.png' /></a></span>"),
		widgetBorder: new Template("<span class='control' title='Border style'><img src='" + skingen_imgs + "/border-color.png' /> <input type='text' class='' id='border_#{id}' data-type='border' /><span id='border_preview_#{id}' class='skingen_preview'></span> <a href='#' class='sg_dropper' title='Color picker' id='border_dropper_#{id}'><img src='" + skingen_imgs + "/color-picker.png' /></a></span>"),
		widgetBoxShadow: new Template("<span class='control' title='Box Shadow'><img src='" + skingen_imgs + "/box-shadow.png' /> <input type='text' class='' id='boxshadow_#{id}' data-type='boxshadow' /><span id='boxshadow_preview_#{id}' class='skingen_preview'></span> <a href='#' class='sg_dropper' title='Color picker' id='boxshadow_dropper_#{id}'><img src='" + skingen_imgs + "/color-picker.png' /></a></span>"),
		settingsEditor: new Template("<div id='skingen_settings_editor' class='skingen_editor'>				\
										<h1>Skin Settings</h1>												\
										<div id='skingen_settings_content' class='skingen_content'>			\
											<p class='skingen_desc'>Configure settings for your skin.</p>	\
												<br />														\
												<table style='margin: 10px auto; width: 80%'>				\
												<tr>														\
													<td>Main width</td>										\
													<td><input type='text' id='skingen_setting_width' value='#{width}' /> <select id='skingen_setting_widthunit'><option value='px'>pixels</option><option value='percent'>%</option></select>										\
													</td>													\
												</tr>														\
											</table>														\
											<br />																				\
											<p class='skingen_buttons'>															\
												<a href='#' id='skingen_settings_cancel'>Cancel</a>&nbsp;&nbsp;&nbsp;<a href='#' id='skingen_settings_save'>Save Changes</a>\
											</p>																				\
										</div>																					\
									</div>"),
		buildSkin: new Template("<div id='skingen_buildskin_editor' class='skingen_editor'>				\
										<h1>Build Skin</h1>												\
										<div id='skingen_buildskin_content' class='skingen_content'>			\
											<p class='skingen_desc'>Save and build the skin.</p>	\
												<br />														\
												<table style='margin: 10px auto; width: 80%'>				\
												<tr>														\
													<td><p>This will save the skin edits and close the skin editor box</p>	</td>													\
												</tr>															\
											</table>														\
											<br />																				\
											<p class='skingen_buttons'>															\
												<a href='#' id='skingen_buildskin_cancel'>Cancel</a>&nbsp;&nbsp;&nbsp;<a href='#' id='skingen_buildskin_save'>Build</a>\
											</p>																				\
										</div>																					\
									</div>"),
		colorizeEditor: new Template("<div id='skingen_colorize_editor' class='skingen_editor' style='display: none'>			\
										<h1>Colorize Your Skin</h1>																\
										<div id='skingen_colorize_content' class='skingen_content'>								\
											<p class='skingen_desc'>Use this tool to change the hue (color) of all relevant styles in one go. You can then edit individual styles using the main editor. Be aware that this will overwrite any styles you have already customized.</p>									\
											<table style='margin: 10px 0'>													\
												<tr>																			\
													<td style='width: 50%'><p id='skingen_colorize_base' class='color' data-type='base'></p><br />Base Color</td>\
													<td style='width: 50%'><p id='skingen_colorize_secondary' class='color' data-type='secondary'></p><br />Secondary Color</td>\
												</tr>																			\
												<tr>																			\
													<td><p id='skingen_colorize_tertiary' class='color' data-type='tertiary'></p><br />Tertiary Color</td>\
													<td><p id='skingen_colorize_text' class='color' data-type='text'></p><br />Text Color</td>\
												</tr>																			\
											</table>																			\
											<br />																				\
											<p class='skingen_buttons'>															\
												<a href='#' id='skingen_colorize_cancel'>Cancel</a>&nbsp;&nbsp;&nbsp;<a href='#' id='skingen_colorize_save'>Save Changes</a>																													\
											</p>																				\
										</div>																					\
									</div>"),				
		imageEditor: new Template("<div id='skingen_images_editor' class='skingen_editor'><h1>Image Editor</h1><div id='skingen_images_content' class='skingen_content'>#{content}</div></div>"),
		imageEditorItem: new Template("<div id='image_#{id}' class='skingen_image_item clearfix'>#{img}<p class='right'>#{upload}</p></div>")
	},
	
	//============================================================
	// SKIN CLASSES
	// Our master hash of the classes that can be customized
	//============================================================
	skinClasses: $H( IPS_SKIN_GEN_DATA['classes'] ),
	
	//============================================================
	// SKIN GROUPS
	// This object groups the classes in the main display
	//============================================================
	skinGroups: $H( IPS_SKIN_GEN_DATA['skinGroups'] ),	
	
	//============================================================
	// SKIN GROUPS
	// This object defines which classes are altered when one of the colorize selectors is altered
	//============================================================
	colorizeGroups: $H( IPS_SKIN_GEN_DATA['colorizeGroups'] ),
	
	imageReplacements: $H({
		logo: 	[	'logo.png',												// Filename
					'#logo',												// Scope to find image in (default: *)
					'/',													// Path to find this image in the skin folder
					'The main community logo'								// Description
				],
				
		defaultPhoto: [ 	'default_large.png',
							'',
							'/profile/',
							'Default user photo'
					]
	})
};

document.observe("dom:loaded", function(){
	skingen.boot();
});
/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* inlineUpload - Core javascript				*/
/* (c) IPS, Inc 2011							*/
/* -------------------------------------------- */
/* Author: Matt "We're not jQueried Up" Mecham	*/
/************************************************/

var _inlineUploader = window.IPBoard;
/**
 * 
 */
_inlineUploader.prototype.inlineUploader = {
	
	formId: null,
	fieldId: null,
	callBack: null,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ipb.inlineUploader.js");
		
		document.observe("dom:loaded", function(){
		});
	},
	
	/**
	 * Set the inline uploader to watch a form/field
	 * 
	 * @param	string	ID of form element
	 * @param	string	ID of input field element
	 * @param	string	Function to pass AJAX back to when it has been gathered
	 */
	watch: function( formId, fieldId, callBack )
	{
		ipb.inlineUploader.formId   = formId;
		ipb.inlineUploader.fieldId  = fieldId;
		ipb.inlineUploader.callBack = callBack;
		
		if ( ! $( ipb.inlineUploader.formId ) )
		{
			Debug.error("formID doesn't exist");
			return false;
		}
		
		if ( ! $( ipb.inlineUploader.fieldId ) )
		{
			Debug.error("fieldID doesn't exist");
			return false;
		}
		
		/* Perform some set up */
		$( ipb.inlineUploader.fieldId ).observe( 'change', ipb.inlineUploader.upload );
	},
	
	/**
	 * Upload has been triggered
	 */
	upload: function(e)
	{
		/* Disable form submit */
		//$( ipb.inlineUploader.formId ).disable();
		
		/* Build target */
		iFrame = new Element('iframe', { 'src': 'about:blank', 'style': 'background:transparent', 'name': 'ips_InlineUploader', 'id': 'ips_InlineUploader' } ).hide();
		$( ipb.inlineUploader.formId ).insert( { after: iFrame } );
		
		$('ips_InlineUploader').observe( 'load', ipb.inlineUploader.complete );
		
		/* Add on a param so we know we're capturing JSON */
		$( ipb.inlineUploader.formId ).action = $( ipb.inlineUploader.formId ).action + '&getJson=1'.replace( /&amp;/g, '&' );
		
		/* Submit form to iFrame then capture in complete */
		$( ipb.inlineUploader.formId ).writeAttribute('target', 'ips_InlineUploader');
		
		var img = new Element( 'img', { 'id': 'ips_InlineUploader_img', 'src': ipb.vars['loading_img'] } );
		
		$( ipb.inlineUploader.fieldId ).insert( { before: img } );
		$( ipb.inlineUploader.fieldId ).hide();
		
		$( ipb.inlineUploader.formId ).submit();
	},
	
	/**
	 * Upload complete. Hopefully
	 *
	 */
	complete: function(e)
	{
		var iFrame = $('ips_InlineUploader');
		var json   = iFrame.contentWindow.document.body.innerHTML.stripTags();
		
		if ( ! json )
		{
			return false;
		}
		
		/* Reset */
		ipb.inlineUploader.reset(e);
		
		/* Process JSON */
		if ( json.isJSON() )
		{
			json = json.evalJSON();
			Debug.dir( json );
		}
		else
		{
			Debug.error( "Not legal JSON\n" + json );
		}
		
		/* pass it to the callback */
		ipb.inlineUploader.callBack( json, e );
	},
	
	/**
	 * Resets form
	 */
	reset: function(e)
	{
		$( ipb.inlineUploader.formId ).enable();
		$( ipb.inlineUploader.formId ).writeAttribute('target', '_self');
		$( ipb.inlineUploader.formId ).stopObserving( 'submit' );
		$( 'ips_InlineUploader_img' ).remove();
		$( ipb.inlineUploader.fieldId ).show();
		
		setTimeout( function() { $('ips_InlineUploader').remove(); }, 500 );
	}
};
	
ipb.inlineUploader.init();
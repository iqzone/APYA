var _temp = window.IPBACP;

_temp.prototype.portal = {
	
	popups: {},
	
	viewBlock: function( e, url )
	{
		Event.stop(e);		
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		acp.portal.popups['block'] = new ipb.Popup('viewblock', { type: 'pane', modal: false, hideAtStart: false, w: '600px', h: '600px', ajaxURL: url } );		
	},	
	
	viewQuickTags: function( e, url )
	{
		Event.stop(e);		
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		acp.gms.popups['quick_tags'] = new ipb.Popup('quicktags', { type: 'pane', modal: false, hideAtStart: false, w: '600px', h: '600px', ajaxURL: url } );		
	}			
}
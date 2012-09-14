<?php
/**
 * @file		cp_skin_gallery_group_form.php 	IP.Gallery group form skin file
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * $LastChangedDate: 2011-05-18 11:46:43 -0400 (Wed, 18 May 2011) $
 * @version		v4.2.1
 * $Revision: 8828 $
 */

/**
 *
 * @class		cp_skin_gallery_group_form
 * @brief		IP.Gallery group form skin file
 */
class cp_skin_gallery_group_form
{
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$request
	 * @var		$lang
	 * @var		$member
	 * @var		$memberData
	 * @var		$cache
	 * @var		$caches
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
	}


/**
 * Main form to edit group settings
 *
 * @param	array		$group		Group data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
public function acp_gallery_group_form_main( $group, $tabId ) {

$form						  = array();
$form['g_gallery_use'] 		  = $this->registry->output->formYesNo( 'g_gallery_use', $group['g_gallery_use']  );
$form['g_max_diskspace'] 	  = $this->registry->output->formInput( 'g_max_diskspace', $group['g_max_diskspace']  );
$form['g_max_upload'] 		  = $this->registry->output->formInput( 'g_max_upload', $group['g_max_upload']  );
$form['g_max_transfer']		  = $this->registry->output->formInput( 'g_max_transfer', $group['g_max_transfer']  );
$form['g_max_views']		  = $this->registry->output->formInput( 'g_max_views', $group['g_max_views']  );
#$form['g_multi_file_limit']	  = $this->registry->output->formInput( 'g_multi_file_limit', $group['g_multi_file_limit']  );
#$form['g_zip_upload']		  = $this->registry->output->formYesNo( 'g_zip_upload', $group['g_zip_upload']  );
$form['g_create_albums']	  = $this->registry->output->formYesNo( 'g_create_albums', $group['g_create_albums']  );
$form['g_album_limit']		  = $this->registry->output->formInput( 'g_album_limit', $group['g_album_limit']  );
$form['g_img_album_limit']	  = $this->registry->output->formInput( 'g_img_album_limit', $group['g_img_album_limit']  );
$form['g_comment']			  = $this->registry->output->formYesNo( 'g_comment', $group['g_comment']  );
$form['g_album_private']	  = $this->registry->output->formYesNo( 'g_album_private', $group['g_album_private']  );
$form['g_mod_albums']		  = $this->registry->output->formYesNo( 'g_mod_albums', $group['g_mod_albums']  );
$form['g_edit_own']			  = $this->registry->output->formYesNo( 'g_edit_own', $group['g_edit_own']  );
$form['g_del_own']			  = $this->registry->output->formYesNo( 'g_del_own', $group['g_del_own']  );
#$form['g_move_own']			  = $this->registry->output->formYesNo( 'g_move_own', $group['g_move_own']  );
#$form['g_slideshows']		  = $this->registry->output->formYesNo( 'g_slideshows', $group['g_slideshows']  );
#$form['g_favorites']		  = $this->registry->output->formYesNo( 'g_favorites', $group['g_favorites']  );
#$form['g_rate']				  = $this->registry->output->formYesNo( 'g_rate', $group['g_rate']  );
#$form['g_ecard']			  = $this->registry->output->formYesNo( 'g_ecard', $group['g_ecard']  );
#$form['g_can_search_gallery'] = $this->registry->output->formYesNo( 'g_can_search_gallery', $group['g_can_search_gallery']  );
$form['g_movies']			  = $this->registry->output->formYesNo( 'g_movies', $group['g_movies']  );
$form['g_movie_size']		  = $this->registry->output->formInput( 'g_movie_size', $group['g_movie_size']  );
#$form['g_gallery_cat_cover']  = $this->registry->output->formYesNo( 'g_gallery_cat_cover', $group['g_gallery_cat_cover'] );
#$form['g_max_notes']		  = $this->registry->output->formInput( 'g_max_notes', $group['g_max_notes']  );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tab_GROUPS_{$tabId}_content'>
	<table class='ipsTable double_pad'>
		<tr>
	 		<th colspan='2'>{$this->lang->words['groups_desc_features']}</th>
	 	</tr>
	 	<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_access']}</strong></td>
			<td class='field_field'>{$form['g_gallery_use']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_comment']}</strong></td>
			<td class='field_field'>{$form['g_comment']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_media']}</strong></td>
			<td class='field_field'>{$form['g_movies']}</td>
		</tr>
		<tr>
	 		<th colspan='2'>{$this->lang->words['groups_desc_diskspace']}</th>
	 	</tr>     
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_diskspace']}</strong></td>
			<td class='_unlimitedNumber' unlimited-field="g_max_diskspace">{$form['g_max_diskspace']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_uploads']}</strong></td>
			<td class='_unlimitedNumber' unlimited-field="g_max_upload">{$form['g_max_upload']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_transfer']}</strong></td>
			<td class='_unlimitedNumber' unlimited-field="g_max_transfer">{$form['g_max_transfer']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_images']}</strong></td>
			<td class='_unlimitedNumber' unlimited-field="g_max_views">{$form['g_max_views']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_media_size']}</strong></td>
			<td class='_unlimitedNumber' unlimited-field="g_movie_size">{$form['g_movie_size']}</td>
		</tr>
		<tr>
	 		<th colspan='2'>{$this->lang->words['groups_desc_albums']}</th>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_albums']}</strong></td>
			<td class='field_field'>{$form['g_create_albums']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_form_private']}</strong></td>
			<td class='field_field'>{$form['g_album_private']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_alimit']}</strong></td>
			<td class='_unlimitedNumber' unlimited-field="g_album_limit">{$form['g_album_limit']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_aimages']}</strong></td>
			<td class='_unlimitedNumber' unlimited-field="g_img_album_limit">{$form['g_img_album_limit']}</td>
		</tr>
		<tr>
	 		<th colspan="2">{$this->lang->words['groups_desc_control']}</th>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_amod']}</strong></td>
			<td class='field_field'>{$form['g_mod_albums']}<br /><span class='desctext'>{$this->lang->words['groups_td_amod_desc']}</span></td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_edit']}</strong></td>
			<td class='field_field'>{$form['g_edit_own']}</td>
		</tr>
		<tr>
			<td class='field_title'><strong class='title'>{$this->lang->words['gf_groups_td_delete']}</strong></td>
			<td class='field_field'>{$form['g_del_own']}</td>
		</tr>
		</tbody>
	</table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Tabs for the group form
 *
 * @param	array		$group		Group data
 * @param	mixed		$tabId		Tab ID
 * @return	@e string	HTML
 */
function acp_gallery_group_form_tabs( $group, $tabId ) {

$IPBHTML = "<li id='tab_GROUPS_{$tabId}'>" . IPSLib::getAppTitle('gallery') . "</li>";

return $IPBHTML;
}

}
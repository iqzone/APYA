<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Look and feel skin file
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @subpackage	Core
 * @link		http://www.invisionpower.com
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 10721 $
 */
 
class cp_skin_mobileapp
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
 * Main display form to upload new images, etc
 *
 * @param	array 		Current Images
 * @param	array		Form data
 * @param	array 		Warnings
 * @return	string		HTML
 */
public function overview( $currentImages, $defaultImages, $imagePath='', $imgErrors, $canImport ) {

$IPBHTML = "";
//--starthtml--//

$information = sprintf( $this->lang->words['mi_information'], $imagePath );
$IPBHTML .= <<<EOF
<script type="text/javascript" src="{$this->settings['js_app_url']}acp.mobileimages.js"></script>
<script type='text/javascript'>
	ipb.templates['import_xml'] = new Template("<div class='acp-box'><h3 class='ipsBlock_title'>{$this->lang->words['mi_import_title']}</h3><form action='{$this->settings['base_url']}{$this->form_code}do=importXml' method='post' enctype='multipart/form-data'><table class='ipsTable double_pad'><tr><td class='field_title'><strong class='title'>{$this->lang->words['mi_select_xml']}</strong></td><td class='field_field'><input type='file' name='FILE_UPLOAD' /></td></tr></table><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['mi_upload']}' class='button primary' /></div></form></div>");
</script>
<style type="text/css">
.ios_image { max-height: 40px; width auto; }
</style>
<div class='section_title'>
	<h2>{$this->lang->words['mi_title']}</h2>
	<div class='section_info'>
		{$information}
	</div>
	<div class='ipsActionBar clearfix'>
	<ul>
		<li class='ipsActionButton'>
			<a href='{$this->settings['base_url']}{$this->form_code}do=refresh'><img src='{$this->settings['skin_acp_url']}/images/icons/arrow_refresh.png' alt='' /> {$this->lang->words['mi_recache']}</a>
		</li>
EOF;
	
		if ( $canImport )
		{
			$IPBHTML .= <<<EOF
		<li class='ipsActionButton' id='importXml'>
			<a href='javascript:void(0);'><img src='{$this->settings['skin_acp_url']}/images/icons/add.png' alt='' /> {$this->lang->words['mi_import_title']}</a>
		</li>
EOF;
		}
		else
		{
			$IPBHTML .= <<<EOF
		<li class='ipsActionButton'>
			<a href='javascript:void(0);'>{$this->lang->words['mi_cant_import']}</a>
		</li>
EOF;
		}
		
		$IPBHTML .= <<<EOF
		<li class='ipsActionButton right'>
			<a href='{$this->settings['base_url']}{$this->form_code}do=exportXml'><img src='{$this->settings['skin_acp_url']}/images/icons/cog.png' /> {$this->lang->words['mi_exportxml']}</a>
		</li>
	</ul>
	</div>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['mi_current_title']}</h3>
	<table class="ipsTable">
		<tr>
			<th width='1%'>&nbsp;</td>
			<th width='35%'>{$this->lang->words['mi_tt_filename']}</th>
			<th width='40%'>{$this->lang->words['mi_tt_modified']}</th>
			<th width='10%'>{$this->lang->words['mi_tt_size']}</th>
		</tr>
EOF;
	foreach( $currentImages as $image )
	{
		$modified = $this->registry->class_localization->getDate( $image['mtime'], 'long' );
		$size     = IPSLib::sizeFormat( $image['size'] );
		$errors   = array();
		$errorBox = '';
		
		if ( $imgErrors['writeable'] !== false && in_array( $image['filename'], $imgErrors['writeable'] ) ) 
		{
			$errors[] = $this->lang->words['mi_img_not_writable'];
		}
		
		if ( $imgErrors['dimensions'] !== false && in_array( $image['filename'], $imgErrors['dimensions'] ) )
		{
			foreach( $defaultImages as $def )
			{
				if ( $image['filename'] == $def['filename'] )
				{
					$rDims = $def['dimensions'][0] . ' x ' . $def['dimensions'][1];
					break;
				}
			}
			
			$errors[] = sprintf( $this->lang->words['mi_img_not_dims'], $rDims );
		}
		
		if ( count( $errors ) )
		{
			$errorBox = "<div class='warning'>" . implode( "<br />\n", $errors ) . "</div>";
		}
		
		$IPBHTML .= <<<EOF
		<tr class='ipsControlRow'>
		 <td><img src="{$image['imgsrc']}" class='ios_image' /></td>
		 <td>{$image['filename']}<p class='desctext'>{$image['dimensions'][0]}px x {$image['dimensions'][1]}px</p>{$errorBox}</td>
		 <td>{$modified}</td>
		 <td>{$size}</td>
		</tr>
EOF;
	
	}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

}
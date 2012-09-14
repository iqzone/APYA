<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Skin Functions
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * Owner: Matt
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10721 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class skinImportExport extends skinCaching
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
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
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		parent::__construct( $registry );
	}
	
	/**
	 * Imports a replacements XMLArchive
	 *
	 * @access	public
	 * @param	string		XMLArchive content to import
	 * @param	int			Set ID to apply to (if desired)
	 * @return	int			Number of items added
	 */
	public function importReplacementsXMLArchive( $content, $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$addedCount		= 0;
		$masterKeys     = $this->fetchMasterKeys();
		$skinMasterKey	= '';
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		$replacements = $this->parseReplacementsXML( $content );
		$where        = ( is_numeric( $setID ) ) ? 'replacement_set_id=' . $setID : 'replacement_master_key=\'' . $setID . '\'';
		
		/* If this is a 'master' skin, then reset master key */
		if ( in_array( $setID, $masterKeys ) )
		{
			$skinMasterKey = $setID;
			$setID         = 0;
		}

		//-----------------------------------------
		// Replacements...
		//-----------------------------------------
		
		if ( is_array( $replacements ) )
		{
			/* Get all the keys for this set id */
			$this->DB->build( array( 'select' => 'replacement_key', 'from' => 'skin_replacements', 'where' => $where ) );
			$this->DB->execute();
			
			$repKeyCache = array();
			while( $r = $this->DB->fetch() )
			{
				$repKeyCache[] = $r['replacement_key'];
			}

			foreach( $replacements as $replacement )
			{
				if ( $replacement['replacement_key'] )
				{
					/* Until this function is reworked, both update and insert count as 'added' */
					$addedCount++;

					if( in_array( $replacement['replacement_key'], $repKeyCache ) )
					{
						$this->DB->update( 'skin_replacements', array( 'replacement_content'  => $replacement['replacement_content'] ), "replacement_key='{$replacement['replacement_key']}' AND " . $where );	
					}
					else
					{
						$this->DB->insert( 'skin_replacements', array(  'replacement_key'        => $replacement['replacement_key'],
																		'replacement_content'    => $replacement['replacement_content'],
																		'replacement_set_id'     => $setID,
																		'replacement_added_to'   => $setID,
																		'replacement_master_key' => $skinMasterKey
																	) );
					}
				}
			}
		}

		$this->rebuildReplacementsCache( $setID );
		$this->rebuildSkinSetsCache( $setID );
		
		return $addedCount;
	}
	
	/**
	 * Imports a set XMLArchive
	 *
	 * @access	public
	 * @param	string		XMLArchive content to import
	 * @param	string		Images directory name to use.
	 * @param	int			[ Set ID to apply to (if desired) ]
	 * @return	mixed		Number of items added, or bool
	 */
	public function importImagesXMLArchive( $content, $imageSetName, $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$addedCount  = 0;
							
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! strstr( $content, "<xmlarchive" ) )
		{
			$this->_addErrorMessage( $this->lang->words['invalidxmlarchivei'] );
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab the XMLArchive class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlArchive = new classXMLArchive();
		
		$xmlArchive->readXML( $content );
		
		if ( ! $xmlArchive->countFileArray() )
		{
			$this->_addErrorMessage( $this->lang->words['emptyxmlarchivei'] );
			return FALSE;
		}
		
		$added = $xmlArchive->countFileArray();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------
		
		if ( $this->checkImageDirectoryExists( $imageSetName ) === TRUE )
		{
			/* Already exists... */
			$this->_addErrorMessage( sprintf( $this->lang->words['imagediralreadyexists'], $imageSetName ) );
			return FALSE;
		}
		
		if ( $this->createNewImageDirectory( $imageSetName ) !== TRUE )
		{
			$this->_addErrorMessage( sprintf( $this->lang->words['imagedircannotcreate'], $imageSetName ) );
			return FALSE;
		}
		
		//-----------------------------------------
		// OK, write it...
		//-----------------------------------------
		
		/* Find the name of the folder */
		//preg_match( "#<path>([^/]+?)</path>#", $content, $match );
		//$_strip = $match[1];
		
		/* Strip the path */
		//$xmlArchive->setStripPath( $_strip );

		/* Write it */
		if ( $xmlArchive->write( $content, $this->fetchImageDirectoryPath( $imageSetName ) ) === FALSE )
		{
			$this->_addErrorMessage( $this->lang->words['cannotwriteimageset'] );
			return FALSE;
		}
		
		//-----------------------------------------
		// Update set?
		//-----------------------------------------
		
		if ( $setID )
		{
			$this->DB->update( 'skin_collections', array( 'set_image_dir' => $imageSetName ), 'set_id=' . $setID );
			
			/* Rebuild trees */
			$this->rebuildTreeInformation( $setID );
			
			/* Now re-load to fetch the tree information */
			$newSet = $this->DB->buildAndFetch( array( 'select' => '*',
													   'from'   => 'skin_collections',
													   'where'  => 'set_id=' . $setID ) );
													
			/* Add to allSkins array for caching functions below */
			$newSet['_parentTree']     = unserialize( $newSet['set_parent_array'] );
			$newSet['_childTree']      = unserialize( $newSet['set_child_array'] );
			$newSet['_userAgents']     = unserialize( $newSet['set_locked_uagent'] );
			$newSet['_cssGroupsArray'] = unserialize( $newSet['set_css_groups'] );
			
			$this->registry->output->allSkins[ $setID ] = $newSet;
			
			$this->rebuildSkinSetsCache( $setID );
			$this->rebuildCSS( $setID );
			$this->rebuildReplacementsCache( $setID );
		}
		
		return $added;
	}
	
	/**
	 * Imports a set XMLArchive
	 *
	 * @access	public
	 * @param	string		XMLArchive content to import
	 * @param	int 		[ Skin set parent. If omitted, it will be made a root skin ]
	 * @param	string		[ Images directory to use. If omitted, default skin's image dir is used ]
	 * @param	string		[ Name of skin to create. If omitted, name from skin set is used ]
	 * @param	bool		[ Whether or not we are attempting to upgrade an existing skin ]
	 * @return	mixed		bool, or number of items added
	 */
	public function importSetXMLArchive( $content, $parentID=0, $imageDir='', $setName='', $upgrading=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templates    = array();
		$csss		  = array();
		$groups       = array();
		$defaultSkin  = array();
		$return       = array( 'replacements' => 0,
							   'css'		  => 0,
							   'templates'    => 0,
							   'upgrade'      => FALSE );
							
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! strstr( $content, "<xmlarchive" ) )
		{
			$this->_addErrorMessage( $this->lang->words['invalidxmlarchivei'] );
			return FALSE;
		}
		
		//-----------------------------------------
		// Make admin group list
		//-----------------------------------------
		
		foreach( $this->caches['group_cache'] as $id => $data )
		{
			if ( $data['g_access_cp'] )
			{
				$groups[] = $id;
			}
		}
		
		//-----------------------------------------
		// Grab the XMLArchive class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlArchive = new classXMLArchive();
		
		$xmlArchive->readXML( $content );
		
		if ( ! $xmlArchive->countFileArray() )
		{
			$this->_addErrorMessage( $this->lang->words['emptyxmlarchivei'] );
			return FALSE;
		}
		
		//-----------------------------------------
		// Gather data
		//-----------------------------------------
		
		/* Info */
		$infoXml	= $xmlArchive->getFile('info.xml');
		
		if( !$infoXml )
		{
			$this->_addErrorMessage( $this->lang->words['noinfoxml'] );
			return FALSE;
		}
		
		$info		= $this->parseInfoXML( $infoXml );
		
		/* Are we attempting to upgrade? */
		$setID     = 0;
		$doUpgrade = FALSE;
		$new_items = array( 'replacements' => array(),
							'css'          => array(),
							'templates'    => array(),
						  );
		
		if ( $info['set_key'] && $upgrading )
		{
			foreach ( $this->caches['skinsets'] as $set_id => $set_data )
			{
				if ( $set_data['set_key'] == $info['set_key'] )
				{
					$setID             = $set_id;
					$doUpgrade         = TRUE;
					$return['upgrade'] = TRUE;
					break;
				}
			}
		}
		
		/* Replacements */
		$replacements = $this->parseReplacementsXML( $xmlArchive->getFile( 'replacements.xml' ) );

		/* Templates */
		foreach( $xmlArchive->asArray() as $path => $fileData )
		{
			if ( $fileData['path'] == 'templates' && $fileData['content'] )
			{
				$templates[ str_replace( '.xml', '', $fileData['filename'] ) ] = $this->parseTemplatesXML( $fileData['content'] );
			}
		}

		/* Templates */
		foreach( $xmlArchive->asArray() as $path => $fileData )
		{
			if ( $fileData['path'] == 'css' )
			{
				$csss[ str_replace( '.xml', '', $fileData['filename'] ) ] = $this->parseCSSXML( $fileData['content'] );
			}
		}
		
		if ( ! is_array( $info ) )
		{
			$this->_addErrorMessage( $this->lang->words['noinfoxml'] );
			return FALSE;
		}
		
		$info['set_output_format'] = ( $info['set_output_format'] ) ? $info['set_output_format'] : 'html';
		
		//-----------------------------------------
		// Find default skin
		//-----------------------------------------
		
		foreach( $this->registry->output->allSkins as $id => $data )
		{
			if ( $data['set_is_default'] AND $data['set_output_format'] == $info['set_output_format'] )
			{
				$defaultSkin = $data;
				break;
			}
		}
		
		/* Make sure key in unique */
		$test = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'skin_collections',
												 'where'  => 'set_key=\'' . $this->DB->addSlashes( $info['set_key'] ) . '\' AND set_id != ' . intval( $setID ) ) );
												 
		if ( $test['set_id'] )
		{
			$info['set_key'] .= '_' . time();
		}

		//-----------------------------------------
		// Build Set Array
		//-----------------------------------------
		
		$newSet = array('set_name'				=> ( $setName ) ? $setName : $info['set_name'] . ' (Import)',
					    'set_key'				=> $info['set_key'],
						'set_parent_id'  		=> $parentID,
						'set_permissions'		=> implode( ",", $groups ),
						'set_is_default'		=> 0,
						'set_author_name'		=> $info['set_author_name'],
						'set_author_url'		=> $info['set_author_url'],
						'set_image_dir'			=> ( $imageDir ) ? $imageDir : $defaultSkin['set_image_dir'],
						'set_emo_dir'			=> $defaultSkin['set_emo_dir'],
						'set_css_inline'		=> 1,
						'set_output_format' 	=> $info['set_output_format'],
						'set_css_groups'    	=> '',
						'set_hide_from_list'	=> 1,	// Per Rikki :P
						'set_order'				=> intval( $this->fetchHighestSetPosition() ) + 1,
						'set_master_key'		=> ( isset( $info['set_master_key'] ) ) ? $info['set_master_key'] : 'root',
						'set_updated'       	=> time() );

		if ( $setID )
		{
			//-----------------------------------------
			// Update...
			//-----------------------------------------
			
			$this->DB->update( 'skin_collections', array( 'set_author_name' => $info['set_author_name'],
														  'set_author_url'  => $info['set_author_url'],
														  'set_updated'     => time(),
														), 'set_id=' . $setID );
		}
		else
		{
			//-----------------------------------------
			// Insert...
			//-----------------------------------------
			
			$this->DB->insert( 'skin_collections', $newSet );
			
			$setID = $this->DB->getInsertId();
		}
		
		/* Rebuild trees */
		$this->rebuildTreeInformation( $setID );
		
		/* Now re-load to fetch the tree information */
		$newSet = $this->DB->buildAndFetch( array( 'select' => '*',
												   'from'   => 'skin_collections',
												   'where'  => 'set_id=' . $setID ) );
												
		/* Add to allSkins array for caching functions below */
		$newSet['_parentTree']     = unserialize( $newSet['set_parent_array'] );
		$newSet['_childTree']      = unserialize( $newSet['set_child_array'] );
		$newSet['_userAgents']     = unserialize( $newSet['set_locked_uagent'] );
		$newSet['_cssGroupsArray'] = unserialize( $newSet['set_css_groups'] );
		
		$this->registry->output->allSkins[ $setID ] = $newSet;
		
		//-----------------------------------------
		// Replacements...
		//-----------------------------------------
		
		if ( is_array( $replacements ) )
		{
			foreach( $replacements as $replacement )
			{
				if ( $replacement['replacement_key'] )
				{
					$return['replacements']++;
					$existing_id = 0;
					
					/* Check if we need to update or insert */
					if ( $doUpgrade )
					{
						$exists = $this->DB->buildAndFetch( array( 'select' => 'replacement_id',
																   'from'   => 'skin_replacements',
																   'where'  => "replacement_key='{$replacement['replacement_key']}' AND replacement_set_id={$setID} AND replacement_added_to={$setID}" ) );
						
						
						$existing_id = $exists['replacement_id'];
					}
					
					if ( $existing_id )
					{
						$new_items['replacements'][] = $existing_id;
						$this->DB->update( 'skin_replacements', array( 'replacement_content'  => $replacement['replacement_content'] ), "replacement_id=".$existing_id );
					}
					else
					{
						$this->DB->insert( 'skin_replacements', array( 'replacement_key'      => $replacement['replacement_key'],
																	   'replacement_content'  => $replacement['replacement_content'],
																	   'replacement_set_id'   => $setID,
																	   'replacement_added_to' => $setID ) );
					}
				}
			}
		}
		
		//-----------------------------------------
		// CSS...
		//-----------------------------------------
		
		/* Fetch master CSS */
		$_MASTER = $this->fetchCSS( 0 );
		
		if ( is_array( $csss ) )
		{
			foreach( ipsRegistry::$applications as $appDir => $data )
			{
				if ( isset( $csss[ $appDir ] ) && is_array( $csss[ $appDir ] ) )
				{
					foreach( $csss[ $appDir ] as $css )
					{
						if ( $css['css_group'] )
						{
							$return['css']++;
							$existing_id = 0;
							
							/* Check if we need to update or insert */
							if ( $doUpgrade )
							{
								$exists = $this->DB->buildAndFetch( array( 'select' => 'css_id',
																		   'from'   => 'skin_css',
																		   'where'  => "css_group='{$css['css_group']}' AND css_app='{$css['css_app']}' AND css_set_id={$setID}" ) );
								
								$existing_id = $exists['css_id'];
							}
							
							if ( $existing_id )
							{
								$new_items['css'][] = $existing_id;
								$this->DB->update( 'skin_css', array( 'css_content'    => $css['css_content'],
																	  'css_position'   => $css['css_position'],
																	  'css_attributes' => $css['css_attributes'],
																	  'css_app_hide'   => $css['css_app_hide'],
																	  'css_modules'    => str_replace( ' ', '', $css['css_modules'] ),
																	  'css_updated'    => time(),
																	  'css_added_to'   => ( isset( $_MASTER[ $css['css_group'] ] ) ) ? 0 : $setID ), "css_id=".$existing_id );
							}
							else
							{
								$this->DB->insert( 'skin_css', array( 'css_group'      => $css['css_group'],
																	  'css_content'    => $css['css_content'],
																	  'css_position'   => $css['css_position'],
																	  'css_attributes' => $css['css_attributes'],
																	  'css_app'		   => $css['css_app'],
																	  'css_app_hide'   => $css['css_app_hide'],
																	  'css_modules'	   => str_replace( ' ', '', $css['css_modules'] ),
																	  'css_updated'    => time(),
																	  'css_set_id'     => $setID,
																	  'css_added_to'   => ( isset( $_MASTER[ $css['css_group'] ] ) ) ? 0 : $setID ) );
							}
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Templates - only import apps we have...
		//-----------------------------------------
		
		/* Fetch all master items */
		$_MASTER    = $this->fetchTemplates( 0, 'allNoContent' );
		
		if ( is_array( $templates ) )
		{
			foreach( ipsRegistry::$applications as $appDir => $data )
			{
				if ( array_key_exists( $appDir, $templates ) )
				{
					foreach( $templates[ $appDir ] as $template )
					{
						if ( $template['template_group'] AND $template['template_name'] )
						{
							/* Figure out if this is added by a user or not */
							$isAdded = ( is_array( $_MASTER[ $template['template_group'] ][ strtolower( $template['template_name'] ) ] ) AND ! $_MASTER[ $template['template_group'] ][ strtolower( $template['template_name'] ) ]['template_user_added'] ) ? 0 : 1;
							
							$return['templates']++;
							$existing_id = 0;
							
							/* Check if we need to update or insert */
							if ( $doUpgrade )
							{
								$exists = $this->DB->buildAndFetch( array( 'select' => 'template_id',
																		   'from'   => 'skin_templates',
																		   'where'  => "template_set_id={$setID} AND template_group='{$template['template_group']}' AND template_name='{$template['template_name']}'" ) );
								
								$existing_id = $exists['template_id'];
							}
								
							if ( $existing_id )
							{
								$new_items['templates'][] = $existing_id;
								$this->DB->update( 'skin_templates', array( 'template_content'     => $template['template_content'],
																			'template_data'        => $template['template_data'],
																			'template_updated'     => $template['template_updated'],
																			'template_removable'   => 1,
																			'template_user_edited' => 1,
																			'template_user_added'  => $isAdded,
																			'template_added_to'    => $setID ), "template_id=".$existing_id );
							}
							else
							{
								$this->DB->insert( 'skin_templates', array( 'template_set_id'      => $setID,
																			'template_group'       => $template['template_group'],
																			'template_content'     => $template['template_content'],
																			'template_name'        => $template['template_name'],
																			'template_data'        => $template['template_data'],
																			'template_updated'     => $template['template_updated'],
																			'template_removable'   => 1,
																			'template_user_edited' => 1,
																			'template_user_added'  => $isAdded,
																			'template_added_to'    => $setID ) );
							}
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// If upgrading, and there are items from an old
		// skin not in the new one, delete the old elements
		// This probably won't ever come up, but I'm anal retentive :P
		//-----------------------------------------
		
		if ( $doUpgrade )
		{
			if ( count( $new_items['replacements'] ) )
			{
				$this->DB->delete( 'skin_replacements', "replacement_set_id={$setID} AND replacement_id NOT IN (" . implode( ',', $new_items['replacements']) . ")" );
			}
			if ( count( $new_items['css'] ) )
			{
				$this->DB->delete( 'skin_css', "css_set_id={$setID} AND css_id NOT IN (" . implode( ',', $new_items['css']) . ")" );
			}
			if ( count( $new_items['templates'] ) )
			{
				$this->DB->delete( 'skin_templates', "template_set_id={$setID} AND template_id NOT IN (" . implode( ',', $new_items['templates']) . ")" );
			}
		}
		
		//-----------------------------------------
		// Re-cache
		//-----------------------------------------
		
		$this->rebuildReplacementsCache( $setID );
		$this->rebuildCSS( $setID );
		$this->rebuildPHPTemplates( $setID );
		$this->rebuildSkinSetsCache();
		
		//-----------------------------------------
		// Done....
		//-----------------------------------------
		
		return $return;
	}
	
	/**
	 * Parses an CSS XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseTemplatesXML( $xmlContents )
	{
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		$return = array();
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );
		
		foreach( $xml->fetchElements( 'template' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			if ( is_array( $data ) )
			{
				$return[] = $data;
			}
		}
		
		return $return;
	}
	
	/**
	 * Parses an CSS XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseCSSXML( $xmlContents )
	{
		if( ! $xmlContents )
		{
			return '';
		}
		
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		$return = array();
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );
		
		foreach( $xml->fetchElements( 'cssfile' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			if ( is_array( $data ) )
			{
				$return[] = $data;
			}
		}
		
		return $return;
	}
	
	/**
	 * Parses an replacements XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseReplacementsXML( $xmlContents )
	{
		if( ! $xmlContents )
		{
			return '';
		}
		
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml    = new classXML( IPS_DOC_CHAR_SET );
		$return = array();
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );
		
		foreach( $xml->fetchElements( 'replacement' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
			
			if ( is_array( $data ) )
			{
				$return[] = $data;
			}
		}
		
		return $return;
	}
	
	/**
	 * Parses an info XML file
	 *
	 * @access	public
	 * @param	string	XML
	 * @return	array
	 */
	public function parseInfoXML( $xmlContents )
	{
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->loadXML( $xmlContents );

		foreach( $xml->fetchElements( 'data' ) as $xmlelement )
		{
			$data = $xml->fetchElementsFromRecord( $xmlelement );
		}
		
		return $data;
	}
	
	/**
	 * Generate an XML archive for an image set
	 *
	 * @access	public
	 * @param	string		Image Directory
	 * @return	mixed		bool, or xml contents
	 */
	public function generateImagesXMLArchive( $imgDir )
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		//-----------------------------------------
		// Does this image directory exist?
		//-----------------------------------------
		
		if ( $this->checkImageDirectoryExists( $imgDir ) !== TRUE )
		{
			$this->_addErrorMessage( sprintf( $this->lang->words['imagedirnotexists'], $imgDir ) );
			return FALSE;
		}
		
		//-----------------------------------------
		// Create new XML archive...
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlArchive = new classXMLArchive();
		$xmlArchive->setStripPath( $this->fetchImageDirectoryPath( $imgDir ) );
		$xmlArchive->add( $this->fetchImageDirectoryPath( $imgDir ) );
		
		return $xmlArchive->getArchiveContents();
	}
	
	/**
	 * Generate XML Archive for skin set
	 *
	 * @access	public
	 * @param	int			Skin set ID
	 * @param	boolean		Modifications in this set only
	 * @param	array		[Array of apps to export from. Default is all]
	 * @return	string		XML
	 */
	public function generateSetXMLArchive( $setID=0, $setOnly=FALSE, $appslimit=null )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templates    = array();
		$csss		  = array();
		$replacements = "";
		$css          = "";
		$setData      = $this->fetchSkinData( $setID );
		
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		//-----------------------------------------
		// First up... fetch templates
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $appDir => $data )
		{
			if ( is_array( $appslimit ) AND ! in_array( $appDir, $appslimit ) )
			{
				continue;
			}
			
			if ( !empty( $data['app_enabled'] ) )
			{
				$templates[ $appDir ]	= $this->generateTemplateXML( $appDir, $setID, $setOnly );
				$csss[ $appDir ]		= $this->generateCSSXML( $appDir, $setID, $setOnly );
			}
		}

		//-----------------------------------------
		// Replacements
		//-----------------------------------------
		
		$replacements = $this->generateReplacementsXML( $setID, $setOnly );
		
		//-----------------------------------------
		// Information
		//-----------------------------------------
		
		$info = $this->generateInfoXML( $setID );
		
		//-----------------------------------------
		// De-bug
		//-----------------------------------------
		
		foreach( $templates as $app_dir => $templateXML )
		{
			IPSDebug::addLogMessage( "Template Export: $app_dir\n".$templateXML, 'admin-setExport', false, true, true );
		}
		
		foreach( $csss as $app_dir => $cssXML )
		{
			IPSDebug::addLogMessage( "CSS Export: $app_dir\n".$cssXML, 'admin-setExport', false, true );
		}
		
		IPSDebug::addLogMessage( "Replacements Export:\n".$replacements, 'admin-setExport', false, true );
		IPSDebug::addLogMessage( "Info Export:\n".$info, 'admin-setExport', false, true );
		
		//-----------------------------------------
		// Create new XML archive...
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXMLArchive.php' );/*noLibHook*/
		$xmlArchive = new classXMLArchive();
		
		/* Add in version numbers */
		$version = IPSLib::fetchVersionNumber();
		$xmlArchive->addRootTagValues( array( 'ipbLongVersion' => $version['long'], 'ipbHumanVersion' => $version['human'] ) );
		
		# Templates
		foreach( $templates as $app_dir => $templateXML )
		{
			$xmlArchive->add( $templateXML, "templates/" . $app_dir . ".xml" );
		}
		
		# CSS
		foreach( $csss as $app_dir => $cssXML )
		{
			$xmlArchive->add( $cssXML, "css/" . $app_dir . ".xml" );
		}
		
		# Replacements
		$xmlArchive->add( $replacements, "replacements.xml" );

		# Information
		$xmlArchive->add( $info, 'info.xml' );
		
		return $xmlArchive->getArchiveContents();
	}
	
	/**
	 * Export all Apps skin files
	 *
	 * @access	public
	 * @param	int		[Set ID - 0/root if omitted]
	 * @param	bool	Include root bits in any XML export. Default is true
	 * @return	@e void
	 */
	public function exportAllAppTemplates( $setID=0, $setOnly=TRUE )
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		if ( $setID == 0 )
		{
			/* This is which skins we want to export for default installations */
			$skinIDs  = array_values( $this->remapData['export'] );
		}
		else
		{
			$skinIDs = array( $setID );
		}
		
		foreach( $skinIDs as $setID )
		{
			foreach( ipsRegistry::$applications as $app_dir => $app_data )
			{
				if ( ! file_exists( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
				{
					$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . $this->lang->words['xmldirmissing'] );
					continue;
				}
				else if ( ! is_writable( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
				{
					if ( ! @chmod( IPSLib::getAppDir(  $app_dir ) . '/xml', 0755 ) )
					{
						$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . $this->lang->words['xmldirnotwrite'] );
						continue;
					}
				}
					
				$this->exportTemplateAppXML( $app_dir, $setID, $setOnly );
			}
		}
	}
	
	/**
	 * Export all Apps CSS
	 *
	 * @access	public
	 * @param	int		[Set ID - 0/root if omitted]
	 * @return	@e void
	 */
	public function exportAllAppCSS( $setID=0 )
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------

		$this->_resetErrorHandle();
		$this->_resetMessageHandle();

		if ( $setID == 0 )
		{
			/* This is which skins we want to export for default installations */
			$skinIDs  = array_values( $this->remapData['export'] );
		}
		else
		{
			$skinIDs = array( $setID );
		}
		
		foreach( $skinIDs as $setID )
		{
			foreach( ipsRegistry::$applications as $app_dir => $app_data )
			{
				$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_css.xml';
	
				if ( ! file_exists( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
				{
					$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . $this->lang->words['xmldirmissing'] );
					continue;
				}
				else if ( ! is_writable( IPSLib::getAppDir(  $app_dir ) . '/xml' ) )
				{
					if ( ! @chmod( IPSLib::getAppDir(  $app_dir ) . '/xml', 0755 ) )
					{
						$this->_addErrorMessage( IPSLib::getAppDir(  $app_dir ) . $this->lang->words['xmldirnotwrite'] );
						continue;
					}
				}
	
				$this->exportCSSAppXML( $app_dir, $setID );
			}
		}
	}
	
	/**
	 * Import all Apps skin files
	 *
	 * @todo  See Matt, this needs fixing! 
	 * @access	public
	 * @return	@e void
	 */
	public function importAllAppTemplates()
	{
		//-----------------------------------------
		// Reset handlers
		//-----------------------------------------
		
		$this->_resetErrorHandle();
		$this->_resetMessageHandle();
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_templates.xml';
			
			if ( ! is_file( $file ) )
			{
				$this->_addMessage( $app_dir . $this->lang->words['importnothingtoimport'] );
				continue;
			}
			else
			{
				$return = $this->importTemplateAppXML( $app_dir, 0 );
				$this->_addMessage( sprintf( $this->lang->words['importaddupdate'], $app_dir, $return['updateCount'], $return['insertCount'] ) );
			}
		}
	}
	
	/**
	 * Generate the master skin set files
	 *
	 * @access	public
	 * @param	array  		Array of IDs
	 * @return	string		XML contents
	 */
	public function generateMasterSkinSetXML( $skinIDs )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$classToLoad = IPSLib::loadLibrary( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php', 'userAgentFunctions' );
		$userAgentFunctions = new $classToLoad( $this->registry );
		
		$skins    = array();
		$setid    = 1;
		$exports  = array_values( $this->remapData['export'] );
		$remap    = array( 'mobile' => array( 'set_id'			  => 2,
											  'set_name' 		  => 'IP.Board Mobile',
											  'set_key'  		  => 'mobile',
											  'set_master_key'	  => 'mobile',
											  'set_output_format' => 'html',
											  'set_image_dir'     => 'mobile',
											  'set_locked_uagent' => serialize( $userAgentFunctions->getMobileSkinUserAgents() ) ),
						 'xmlskin' => array(  'set_id'			  => 3,
											  'set_name' 		  => 'IP.Board XML',
											  'set_key'  		  => 'xmlskin',
											  'set_master_key'	  => 'xmlskin',
											  'set_output_format' => 'xml',
											  'set_image_dir'     => 'master' ) );
											  
		/* Figure out ID 0 */
		if ( in_array( 0, $skinIDs ) OR in_array( 'root', $skinIDs ) )
		{
			$cssSkinCollections = array();
			$_css 			    = $this->fetchCSS( 0 );
			
			foreach( $_css as $name => $css )
			{
				/* Build skin set row*/
				$cssSkinCollections[ $css['css_position'] . '.' . $css['css_id'] ] = array( 'css_group' => $css['css_group'], 'css_position' => $css['css_position'] );
			}
			
			$setid++;
			
			$skins[ 0 ] = array( 'set_id'			  => 1,
		  						 'set_name'			  => 'IP.Board',
		  						 'set_key'			  => 'default',
		  						 'set_parent_id'	  => 0,
		  						 'set_parent_array'   => serialize( array() ),
		  						 'set_child_array'    => serialize( array() ),
		  						 'set_permissions'    => '*',
		  						 'set_is_default'	  => 1,
		  						 'set_author_name'	  => 'Invision Power Services, Inc',
		  						 'set_author_url'	  => 'http://www.invisionpower.com',
		  						 'set_image_dir'	  => 'master',
		  						 'set_emo_dir'		  => 'default',
		  						 'set_css_inline'	  => 1,
		  						 'set_css_groups'	  => serialize( $cssSkinCollections ),
		  						 'set_added'		  => time(),
		  						 'set_updated'		  => time(),
		  						 'set_output_format'  => 'html',
		  						 'set_locked_uagent'  => '',
		  						 'set_hide_from_list' => 0,
		  						 'set_master_key'     => 'root' );
		}
		
		foreach( $exports as $_id )
		{
			if ( $_id != 'root' AND in_array( $_id, $skinIDs ) )
			{
				$cssSkinCollections = array();
				$_css 			    = $this->fetchCSS( $_id );
				
				foreach( $_css as $name => $css )
				{
					/* Build skin set row*/
					$cssSkinCollections[ $css['css_position'] . '.' . $css['css_id'] ] = array( 'css_group' => $css['css_group'], 'css_position' => $css['css_position'] );
				}
				
				$setid++;
				
				$skins[ $remap[ $_id ]['set_id'] ] = array(  'set_id'			  => $remap[ $_id ]['set_id'],
									  						 'set_name'			  => $remap[ $_id ]['set_name'],
									  						 'set_key'			  => $remap[ $_id ]['set_key'],
									  						 'set_parent_id'	  => 0,
									  						 'set_parent_array'   => serialize( array() ),
									  						 'set_child_array'    => serialize( array() ),
									  						 'set_permissions'    => '*',
									  						 'set_is_default'	  => ( $remap[ $_id ]['set_master_key'] == 'root' ) ? 1 : 0,
									  						 'set_author_name'	  => 'Invision Power Services, Inc',
									  						 'set_author_url'	  => 'http://www.invisionpower.com',
									  						 'set_image_dir'	  => $remap[ $_id ]['set_image_dir'],
									  						 'set_emo_dir'		  => 'default',
									  						 'set_css_inline'	  => 1,
									  						 'set_css_groups'	  => serialize( $cssSkinCollections ),
									  						 'set_added'		  => time(),
									  						 'set_updated'		  => time(),
									  						 'set_output_format'  => $remap[ $_id ]['set_output_format'],
									  						 'set_locked_uagent'  => ( isset( $remap[ $_id ]['set_locked_uagent'] ) ) ? $remap[ $_id ]['set_locked_uagent'] : '',
									  						 'set_hide_from_list' => 0,
									  						 'set_master_key'     => $remap[ $_id ]['set_master_key'] );
			}
		}
		

		if ( count( $skins ) != count( $skinIDs ) )
		{
			/* Grab the rest */
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_collections',
									 'where'  => 'set_id IN (' . implode( ",", $skinIDs ) . ')' ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				/* Ensure settings */
				$row['set_id']    		   = $setid;
				$row['set_permissions']    = '*';
				$row['set_hide_from_list'] = 0;
				$row['set_css_inline']     = 1;
				
				$skins[ $row['set_id'] ] = $row;
				
				$setid++;
			}
		}
		
		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'skinsets' );
		
		foreach( $skins as $id => $setData )
		{
			$xml->addElementAsRecord( 'skinsets', 'set', $setData );
		}

		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML Replacements data file
	 *
	 * @access	public
	 * @param	int			Set ID
	 * @param	boolean		Just get the changes for this set (if TRUE)
	 * @return	mixed	bool, or XML
	 */
	public function generateReplacementsXML( $setID=0, $setOnly=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$replacements = array();
		
		//-----------------------------------------
		// Grab the CSS
		//-----------------------------------------
		
		if ( $setOnly === TRUE )
		{
			$where = ( is_numeric( $setID ) ) ? 'replacement_set_id=' . $setID : 'replacement_master_key=\'' . $setID . '\'';
				
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_replacements',
									 'where'  => $where ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$replacements[ $row['replacement_key'] ] = $row;
			}
		}
		else
		{
			$replacements = $this->fetchReplacements( $setID );
		}
				
		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'replacements' );
		
		foreach( $replacements as $key => $replacementsData )
		{
			unset( $replacementsData['replacement_id'] );
			unset( $replacementsData['replacement_added_to'] );
			unset( $replacementsData['theorder'] );
			unset( $replacementsData['SAFE_replacement_content'] );
			
			$xml->addElementAsRecord( 'replacements', 'replacement', $replacementsData );
		}
		
		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML CSS data file
	 *
	 * @access	public
	 * @param	int			Set ID
	 * @param	boolean		Just get the changes for this set (if TRUE)
	 * @return	mixed		bool, or XML
	 */
	public function generateCSSXML( $app_dir='core', $setID=0, $setOnly=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$css          = array();
		$gotSomething = FALSE;
		
		//-----------------------------------------
		// Grab the CSS
		//-----------------------------------------

		if ( $setOnly === TRUE AND $setID > 0 )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_css',
									 'where'  => 'css_set_id=' . $setID ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$css[ $row['css_group'] ] = $row;
			}
		}
		else
		{
			$_css = $this->fetchCSS( $setID );
			
			/* Remove set 0 templates */
			if ( $setID > 0 )
			{
				if ( is_array( $_css ) AND count( $_css ) )
				{
					foreach( $_css as $name => $cssData )
					{
						if ( $cssData['css_set_id'] > 0 )
						{
							$css[ $name ] = $cssData;
						}
					}
				}
			}
			else
			{
				$css = $_css;
			}
		}

		if ( ! is_array( $css ) OR ! count( $css ) )
		{
			$this->_addMessage( $this->lang->words['nocsstoexport'] . $app_dir );
			return FALSE;
		}
		
		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'css' );

		foreach( $css as $name => $cssData )
		{
			/* Checking this app dir? */
			$cssData['css_app'] = ( $cssData['css_app'] ) ? $cssData['css_app']  : 'core';
			
			if ( $cssData['css_app'] != $app_dir )
			{
				continue;
			}
			
			$gotSomething = TRUE;
			
			unset( $cssData['css_id'] );
			unset( $cssData['css_added_to'] );
			unset( $cssData['theorder'] );
			unset( $cssData['_cssSize'] );
			
			$xml->addElementAsRecord( 'css', 'cssfile', $cssData );
		}

		if ( ! $gotSomething )
		{
			$this->_addMessage( $this->lang->words['nocsstoexport'] . $app_dir );
			return FALSE;
		}
		
		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML template data file
	 *
	 * @access	public
	 * @param	string		App
	 * @param	int			Set ID
	 * @param	boolean		Just get the changes for this set (if TRUE)
	 * @return	mixed		bool, or XML
	 */
	public function generateTemplateXML( $app, $setID=0, $setOnly=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templateGroups = array();
		
		//-----------------------------------------
		// XML
		//-----------------------------------------
		
		$infoXML = IPSLib::getAppDir(  $app ) . '/xml/information.xml';
		
		if ( ! is_file( $infoXML ) )
		{
			$this->_addErrorMessage( $this->lang->words['couldnotlocate'] . $infoXML );
			return FALSE;
		}
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->load( $infoXML );
		
		foreach( $xml->fetchElements( 'template' ) as $template )
		{
			$name  = $xml->fetchItem( $template );
			$match = $xml->fetchAttribute( $template, 'match' );
		
			if ( $name )
			{
				$templateGroups[ $name ] = $match;
			}
		}
		
		if ( ! is_array( $templateGroups ) OR ! count( $templateGroups ) )
		{
			$this->_addMessage( sprintf( $this->lang->words['nothingtoexportforapp'], $app ) );
			return FALSE;
		}
		
		//-----------------------------------------
		// Fetch templates
		//-----------------------------------------
		$templates = array();
		
		if ( $setOnly === TRUE AND $setID > 0 )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_templates',
									 'where'  => 'template_set_id=' . $setID ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$templates[ $row['template_group'] ][ strtolower( $row['template_name'] ) ] = $row;
			}
		}
		else
		{
			$_templates = $this->fetchTemplates( $setID );
			
			/* Remove set 0 templates */
			if ( $setID > 0 )
			{
				if ( is_array( $_templates ) AND count( $_templates ) )
				{
					foreach( $_templates as $group => $data )
					{
						foreach( $data as $name => $templateData )
						{
							if ( $templateData['template_set_id'] > 0 )
							{
								$templates[ $group ][ $name ] = $templateData;
							}
						}
					}
				}
			}
			else
			{
				$templates = $_templates;
			}
		}
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'templates', '', array( 'application' => $app, 'templategroups' => serialize( $templateGroups ) ) );
		
		if ( ! is_array( $templates ) OR ! count( $templates ) )
		{
			$this->_addMessage( sprintf( $this->lang->words['nogroupsforexportapp'], $app ) );
			return FALSE;
		}
		
		$added   = 0;
		
		foreach( $templates as $group => $data )
		{
			$_okToGo = FALSE;
			
			foreach( $templateGroups as $name => $match )
			{
				if ( $match == 'contains' )
				{
					if ( stristr( $group, $name ) )
					{
						$_okToGo = TRUE;
						break;
					}
				}
				else if ( $group == $name )
				{
					$_okToGo = TRUE;
				}
			}
			
			/* If this is the core app, allow any custom bits also */
			if ( $app == 'core' )
			{
				$_data = $data;
				$test  = array_shift( $_data );
				
				if ( $test['template_user_added'] )
				{
					$_okToGo = TRUE;
				}
			}
		
			if ( $_okToGo === TRUE )
			{ 
				$xml->addElement( 'templategroup', 'templates', array( 'group' => $group ) );
			
				foreach( $data as $name => $templateData )
				{
					unset( $templateData['theorder'] );
					unset( $templateData['template_id'] );
					unset( $templateData['template_set_id'] );
					unset( $templateData['template_added_to'] );
				
					$xml->addElementAsRecord( 'templategroup', 'template', $templateData );
					$added++;
				}
			}
		}
		
		if ( ! $added )
		{
			$this->_addMessage( $this->lang->words['nothingtoexportfor'] . $app );
			return FALSE;
		}
		
		return $xml->fetchDocument();
	}
	
	/**
	 * Generate XML Information data file
	 *
	 * @access	public
	 * @param	int			Set ID
	 * @return	string		XML
	 */
	public function generateInfoXML( $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$data    = array();
		$setData = $this->fetchSkinData( $setID );
		$version = IPSLib::fetchVersionNumber();
		
		//-----------------------------------------
		// Grab the XML parser
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		$xml->newXMLDocument();
		$xml->addElement( 'info' );
		
		$xml->addElementAsRecord( 'info', 'data', array( 'set_name'          => $setData['set_name'],
														 'set_key'           => $setData['set_key'],
														 'set_author_name'   => $setData['set_author_name'],
														 'set_author_url'    => $setData['set_author_url'],
														 'set_output_format' => $setData['set_output_format'],
														 'set_master_key'    => $setData['set_master_key'],
														 'ipb_human_version' => $version['human'],
														 'ipb_long_version'  => $version['long'],
														 'ipb_major_version' => '3' ) );

		return $xml->fetchDocument();
	}
	
	/**
	 * Import CSS for a single app
	 *
	 * @access	public
	 * @param	string		App
	 * @param	string		Skin key to import
	 * @param	int			Set ID
	 * @return	mixed		bool, or number of items added
	 */
	public function importCSSAppXML( $app, $skinKey, $setID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$fileXML        = IPSLib::getAppDir(  $app ) . '/xml/' . $app . '_' . $skinKey . '_css.xml';
		$return			= array( 'updateCount' => 0, 'insertCount' => 0, 'updateBits' => array(), 'insertBits' => array() );
		$masterKeys     = $this->fetchMasterKeys();
		
		//-----------------------------------------
		// File exists
		//-----------------------------------------
		
		if ( ! is_file( $fileXML ) )
		{
			return FALSE;
		}
		
		if ( ! $setID and ! in_array( $skinKey, $masterKeys ) )
		{
			/* Figure out correct set ID based on key */
			$skinSetData = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'skin_collections',
															'where'  => "set_key='" . $skinKey . "'" ) );
															
			$setID         = $skinSetData['set_id'];
			$skinMasterKey = $skinSetData['set_master_key'];
		}
				
		/* If this is a 'master' skin, then reset master key */
		if ( in_array( $skinKey, $masterKeys ) )
		{
			$skinMasterKey = $skinKey;
			$setID         = 0;
		}
		
		//-----------------------------------------
		// Delete all CSS if this is set ID 0
		//-----------------------------------------
		
		if ( in_array( $skinKey, $masterKeys ) )
		{
			$this->DB->delete( 'skin_css', 'css_set_id=0 AND css_master_key=\'' . $skinMasterKey . '\' AND css_app=\''. addslashes( $app ) . '\'' );
		}
		
		//-----------------------------------------
		// Fetch CSS
		//-----------------------------------------
		
		$css = $this->parseCSSXML( file_get_contents( $fileXML ) );
	
		if ( is_array( $css ) )
		{
			foreach( $css as $_css )
			{
				if ( $_css['css_group'] )
				{
					$return['insertCount']++;
					
					if ( $setID )
					{
						$this->DB->delete( 'skin_css', 'css_set_id=' . $setID . ' AND css_group=\'' . addslashes( $_css['css_group'] ) . '\' AND css_app=\'' . addslashes( $_css['css_app'] ) . '\'' );
					}
					
					$this->DB->insert( 'skin_css', array( 'css_group'       => $_css['css_group'],
														  'css_content'     => $_css['css_content'],
														  'css_position'    => $_css['css_position'],
														  'css_updated'     => time(),
														  'css_app'		    => $_css['css_app'],
														  'css_app_hide'    => $_css['css_app_hide'],
														  'css_attributes'  => $_css['css_attributes'],
														  'css_modules'		=> $_css['css_modules'],
														  'css_master_key'  => ( in_array( $skinKey, $masterKeys ) ) ? $skinKey : '',
														  'css_set_id'      => $setID,
														  'css_added_to'    => $setID ) );
				}
			}
		}

		return $return;
	}
	 
	/**
	 * Export template CSS into app dirs
	 *
	 * @access	public
	 * @param	string		App to export into
	 * @param	int 		Set ID (0 / root by default )
	 * @return	@e void
	 */
	public function exportCSSAppXML( $app_dir, $setID=0 )
	{
		//-----------------------------------------
		// Get it
		//-----------------------------------------
		
		$setData = $this->fetchSkinData( $setID );
		$xml     = $this->generateCSSXML( $app_dir, $setID );
		
		//-----------------------------------------
		// Attempt to write...
		//-----------------------------------------
		
		/* Set file name */
		$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_' . $setData['set_key'] . '_css.xml';
		
		/* Attempt to unlink first */
		@unlink( $file );
		
		if ( $xml )
		{
			if ( is_file( $file ) AND ! IPSLib::isWritable( $file ) )
			{
				$this->_addErrorMessage( $file . ' ' . $this->lang->words['css_isnotwritable'] );
				return FALSE;
			}
			
			file_put_contents( $file, $xml );
			
			$this->_addMessage( sprintf( $this->lang->words['csscreatedmsg'], $app_dir, $setData['set_key'] ) );
		}
	}
	
	/**
	 * Export template XML into app dirs
	 *
	 * @access	public
	 * @param	string		App to export into
	 * @param	int			[Set ID (0/root if omitted)]
	 * @param	bool		Include root bits in any XML export. Default is true
	 * @return	@e void
	 */
	public function exportTemplateAppXML( $app_dir, $setID=0, $setOnly=TRUE )
	{
		//-----------------------------------------
		// Get it
		//-----------------------------------------
		
		$setData = $this->fetchSkinData( $setID );
		$xml     = $this->generateTemplateXML( $app_dir, $setID, $setOnly );
		
		//-----------------------------------------
		// Attempt to write...
		//-----------------------------------------
		
		/* Set file name */
		$file = IPSLib::getAppDir(  $app_dir ) . '/xml/' . $app_dir . '_' . $setData['set_key'] . '_templates.xml';
			
		/* Attempt to unlink first */
		@unlink( $file );
			
		if ( $xml )
		{
			if ( is_file( $file ) AND ! IPSLib::isWritable( $file ) )
			{
				$this->_addErrorMessage( $file . ' ' . $this->lang->words['css_isnotwritable'] );
				return FALSE;
			}
			
			file_put_contents( $file, $xml );
			
			$this->_addMessage( sprintf( $this->lang->words['templatescreatedmsg'], $app_dir, $setData['set_key'] ) );
		}
	}
	
	/**
	 * Import a single app
	 *
	 * @access	public
	 * @param	string		App
	 * @param	string		Skin key to import
	 * @param	int			Set ID
	 * @param	boolean		Set the edited / added flags to 0 (from install, upgrade)
	 * @return	mixed		bool, or array of info
	 */
	public function importTemplateAppXML( $app, $skinKey, $setID=0, $ignoreAddedEditedFlag=false )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templateGroups = array();
		$templates      = array();
		$fileXML        = IPSLib::getAppDir(  $app ) . '/xml/' . $app . '_' . $skinKey . '_templates.xml';
		$infoXML        = IPSLib::getAppDir(  $app ) . '/xml/information.xml';
		$return			= array( 'updateCount' => 0, 'insertCount' => 0, 'updateBits' => array(), 'insertBits' => array() );
		$masterKeys     = $this->fetchMasterKeys();
		
		if( ! is_file($fileXML) )
		{
			return $return;
		}
		
		if ( ! $setID and ! in_array( $skinKey, $masterKeys ) )
		{
			/* Figure out correct set ID based on key */
			$skinSetData = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'skin_collections',
															'where'  => "set_key='" . $skinKey . "'" ) );
															
			$setID         = $skinSetData['set_id'];
			$skinMasterKey = $skinSetData['set_master_key'];
		}
		
		/* Set ignore flag correctly */
		if ( ! empty( $skinKey ) AND in_array( $skinKey, $masterKeys ) )
		{
			$ignoreAddedEditedFlag = true;
		}
		
		/* If this is a 'master' skin, then reset master key */
		if ( in_array( $skinKey, $masterKeys ) )
		{
			$skinMasterKey = $skinKey;
			$setID         = 0;
		}
		
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH.'classXML.php' );/*noLibHook*/
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Get information file
		//-----------------------------------------
		
		$xml->load( $infoXML );
		
		foreach( $xml->fetchElements( 'template' ) as $template )
		{
			$name  = $xml->fetchItem( $template );
			$match = $xml->fetchAttribute( $template, 'match' );
		
			if ( $name )
			{
				$templateGroups[ $name ] = $match;
			}
		}
		
		if ( ! is_array( $templateGroups ) OR ! count( $templateGroups ) )
		{
			$this->_addMessage( $this->lang->words['nothingtoexportfor'] . $app );
			return FALSE;
		}
		
		//-----------------------------------------
		// Fetch templates
		//-----------------------------------------
	
		$_templates = $this->fetchTemplates( $setID        , 'allNoContent' );
		$_MASTER    = $this->fetchTemplates( $skinMasterKey, 'allNoContent' );
		
		//-----------------------------------------
		// Loop through...
		//-----------------------------------------
		
		foreach( $_templates as $group => $data )
		{
			$_okToGo = FALSE;
			
			foreach( $templateGroups as $name => $match )
			{
				if ( $match == 'contains' )
				{
					if ( stristr( $group, $name ) )
					{
						$_okToGo = TRUE;
						break;
					}
				}
				else if ( $group == $name )
				{
					$_okToGo = TRUE;
				}
			}
			
			if ( $_okToGo === TRUE )
			{
				foreach( $data as $name => $templateData )
				{
					$templates[ $group ][ $name ] = $templateData;
				}
			}
		}
		
		//-----------------------------------------
		// Wipe the master skins
		//-----------------------------------------
		
		if ( in_array( $skinKey, $masterKeys ) )
		{
			$this->DB->delete( 'skin_templates', "template_master_key='" . $skinKey . "' AND template_group IN ('" . implode( "','", array_keys( $templates ) ) . "') AND template_user_added=0 AND template_added_to=0" );
			
			/* Now wipe the array so we enforce creation */
			unset( $templates );
		}
					
		//-----------------------------------------
		// Now grab the actual XML files
		//-----------------------------------------

		$xml->load( $fileXML );

		foreach( $xml->fetchElements( 'template' ) as $templatexml )
		{
			$data = $xml->fetchElementsFromRecord( $templatexml );
			
			/* Figure out if this is added by a user or not */
			if ( $ignoreAddedEditedFlag === TRUE )
			{
				$isAdded  = 0;
				$isEdited = 0;
			}
			else
			{
				$isAdded  = ( is_array( $_MASTER[ $data['template_group'] ][ strtolower( $data['template_name'] ) ] ) AND ! $_MASTER[ $data['template_group'] ][ strtolower( $data['template_name'] ) ]['template_user_added'] ) ? 0 : 1;
				$isEdited = 1;
			}
			
			if ( is_array( $templates[ $data['template_group'] ][ strtolower( $data['template_name'] ) ] ) AND $templates[ $data['template_group'] ][ strtolower( $data['template_name'] ) ]['template_set_id'] == $setID )
			{
				/* Update.. */
				$return['updateCount']++;
				$return['updateBits'][] = $data['template_name'];
				$this->DB->update( 'skin_templates', array( 'template_content'     => $data['template_content'],
															'template_data'        => $data['template_data'],
															'template_user_edited' => $isEdited,
														    'template_user_added'  => $isAdded,
														    'template_master_key'  => ( in_array( $skinKey, $masterKeys ) ) ? $skinKey : '',
															'template_updated'     => time() ), 'template_set_id=' . $setID . " AND template_group='" . $data['template_group'] . "' AND template_name='" . $data['template_name'] . "'" );
			}
			else
			{
				/* Add... */
				$return['insertCount']++;
				$return['insertBits'][] = $data['template_name'];
				$this->DB->insert( 'skin_templates', array( 'template_set_id'      => $setID,
															'template_group'       => $data['template_group'],
														    'template_content'     => $data['template_content'],
														    'template_name'        => $data['template_name'],
														    'template_data'        => $data['template_data'],
														    'template_removable'   => ( $setID ) ? $data['template_removable'] : 0,
														    'template_added_to'    => $setID,
														    'template_user_edited' => $isEdited,
														    'template_user_added'  => $isAdded,
														    'template_master_key'  => ( in_array( $skinKey, $masterKeys ) ) ? $skinKey : '',
														    'template_updated'     => time() ) );
			}
		}

		return $return;
	}
	
}
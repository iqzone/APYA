<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Sabre classes by Matt Mecham
 * Last Updated: $Date: 2012-05-10 16:10:13 -0400 (Thu, 10 May 2012) $
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		Friday 18th March 2011
 * @version		$Revision: 10721 $
 */

class sabre_file_templates extends Sabre_DAV_File
{
	protected $_skinSet  = array();
	protected $_template = '';
	protected $_title    = '';
	protected $_group    = '';
	protected $_content  = null;
	
	public function __construct( $skinSet, $template=null, $title=null, $group=null )
	{
		$this->registry   =  ipsRegistry::instance();
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Require some files for our sabre implementation */
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/root/skins.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/directory/templates.php' );/*noLibHook*/
	 	require_once( IPS_ROOT_PATH . 'sources/classes/sabre/directory/groups.php' );/*noLibHook*/
	    require_once( IPS_ROOT_PATH . 'sources/classes/sabre/lock/nolocks.php' );/*noLibHook*/
	    
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );/*noLibHook*/
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );/*noLibHook*/
		
		$this->skinFunctions = new skinCaching( $this->registry );
		
		$this->_skinSet = $skinSet;
		$this->_group   = $group;
		
		if ( $template )
		{
			$this->_template = $template;
			$this->_title    = ( $this->_group == 'css' ) ? $template['css_group'] : $template['template_name'];
		}
		else
		{
			$this->_title = $title;
		}
	}

	public function getName()
	{ 
		return $this->_title . '.' . ( $this->_group == 'css' ? 'css' : 'html' );
	}

	public function getLastModified()
	{
		return intval( $this->_group == 'css' ? $this->_template['css_updated'] : $this->_template['template_updated'] );
	}

	public function getETag()
	{
		$content = $this->_getTemplateContent();
		
		return ( $content === false ) ? 'new' : md5( $content );
	}

	public function get()
	{
		$content = $this->_getTemplateContent();
		
		return ( $content === false ) ? '' : $content;
	}

	public function getSize()
	{
		$content = $this->_getTemplateContent();
		
		return ( $content === false ) ? 0 : strlen( $content );
	}

	public function getContentType()
	{
		if ( $this->_group != 'css' )
		{
			return 'text/html';
		}
		else if ( $this->_group == 'css' )
		{
			return 'text/css';
		}
		else
		{
			return null;
		}
	}

	public function put( $data )
	{
		$content = stream_get_contents( $data );
			
		if ( ! $this->_title || $this->_title == '.' || strtolower( $this->_title == '.ds_store' ) || $this->_title == 'Thumbs.db' || $this->_title == 'desktop.ini' )
		{
			return false;
		}
		
		/* Do what now? */
		return ( $this->_group == 'css' ) ? $this->_putCss( $content ) : $this->_putTemplate( $content );
		
	}

	public function delete()
	{
		try
		{
			if ( $this->_template )
			{
				if ( $this->_group != 'css' )
				{
					$this->skinFunctions->revertTemplateBit( $this->_template['template_id'], $this->_skinSet['set_id'] );
				}
				else
				{
					$this->skinFunctions->revertCSS( $this->_template['css_id'], $this->_skinSet['set_id'] );
				}
			}
		}
		catch( Exception $err )
		{
			/* Catch error - it might have come from a tmp save file being renamed */
			if ( $this->_group != 'css' )
			{
				$test = $this->DB->buildAndFetch( array( 'select' => '*',
														 'from'	  => 'skin_templates',
														 'where'  => 'template_set_id=' . $this->_skinSet['set_id'] . ' AND template_group=\'' . $this->DB->addSlashes( $this->_group ) . '\'',
														 'order'  => 'template_id DESC',
														 'limit'  => array( 0, 1 ) ) );
				
				if ( $test['template_added_to'] > 0 && $test['template_updated'] > time() - 240 && $test['template_removable'] && $test['template_added_to'] == $this->_skinSet['set_id'] )
				{
					return true;
				}
			}
			
			throw new Sabre_DAV_Exception_Forbidden( $err->getMessage() );
		}
	}

	public function setName($title)
	{
		$title = preg_replace( '#^(.*)\.(css|html)$#', '\1', $title );
		
		try
		{
			if ( $this->_group != 'css' )
			{
				if ( $this->_template )
				{
					$this->skinFunctions->renameTemplateName( $this->_template['template_id'], $title );
					
					/* Got a master skin of the same name? Then it's a simple edit */
					$test = $this->DB->buildAndFetch(  array( 'select' => '*',
														 	  'from'   => 'skin_templates',
														 	  'where'  => 'template_set_id=0 AND template_master_key=\'' . $this->_skinSet['set_master_key'] . '\' AND template_name=\'' . $this->DB->addSlashes( $this->_title ) . '\' AND template_group=\'' . $this->DB->addSlashes( $this->_group ) . '\'' ) );

					if ( $test['template_name'] )
					{
						/* Reset added to */
						$this->DB->update( 'skin_templates', array( 'template_added_to' => 0 ), 'template_id=' . $this->_template['template_id'] );
					}
				}
			}
			else
			{
				if ( $this->_template )
				{
					$this->skinFunctions->renameCssName( $this->_template['css_id'], $title );
				}
			}
		}
		catch( Exception $err )
		{ 
			throw new Sabre_DAV_Exception_Forbidden( $err->getMessage() );
		}
		
		return true;
	}
	
	/**
	 * Put CSS
	 * @param string $content
	 * @throws Sabre_DAV_Exception_Forbidden
	 * @return boolean
	 */
	protected function _putCss( $content )
	{
		/* Clear cache */
		$this->_content = null;
		
		/* Save it! */
		try
		{
			if ( ! empty( $this->_template['css_id'] ) && $this->_skinSet['set_id'] )
			{
				$this->skinFunctions->saveCSSFromEdit( $this->_template['css_id'], $this->_skinSet['set_id'], $content, $this->_title, $this->_template['css_position'], $this->_template['css_attributes'], $this->_template['css_app'], $this->_template['css_app_hide'], $this->_template['css_modules'] );
			}
			else
			{
				$this->skinFunctions->saveCSSFromAdd( $this->_skinSet['set_id'], $content, $this->_title, $this->_template['css_position'], $this->_template['css_attributes'], $this->_template['css_app'], $this->_template['css_app_hide'], $this->_template['css_modules'] );
			}
		}
		catch( Exception $err )
		{ 
			throw new Sabre_DAV_Exception_Forbidden( $err->getMessage() );
		}
		
		return true;
	}

	/**
	 * Put template ...
	 * @param string $content
	 * @throws Sabre_DAV_Exception_Forbidden
	 * @return boolean
	 */
	protected function _putTemplate( $content )
	{
		/* Got a data tag? */
		$params   = '';
		$template = '';
		
		if ( stristr( $content, '<ips:template parameters="' ) )
		{
			preg_match( '#<ips:template parameters="(.+?)" />(\n)?#', $content, $match );
			
			if ( $match[1] )
			{
				$params = $match[1];
				$template = str_replace( $match[0], '', $content );
			}
		}
		else
		{
			$template = $content;
		}
		
		/* Clear cache */
		$this->_content = null;
		
		/* Save it! */
		try
		{
			if ( ! empty( $this->_template['template_id'] ) && $this->_skinSet['set_id'] )
			{
				$this->skinFunctions->saveTemplateBitFromEdit( $this->_template['template_id'], $this->_skinSet['set_id'], $template, $params );
			}
			else
			{
				$newId = $this->skinFunctions->saveTemplateBitFromAdd( $this->_skinSet['set_id'], $template, $params, $this->_group, $this->_title );
				
				/* If webdav client adds, then renames... - in delete, if trying to delete master and set specific exists, allow without error but don't delete */
				if ( $newId )
				{
					/* Got a master skin of the same name? Then it's a simple edit */
					$test = $this->DB->buildAndFetch(  array( 'select' => '*',
														 	  'from'   => 'skin_templates',
														 	  'where'  => 'template_set_id=0 AND template_master_key=\'' . $this->_skinSet['set_master_key'] . '\' AND template_name=\'' . $this->DB->addSlashes( $this->_title ) . '\' AND template_group=\'' . $this->DB->addSlashes( $this->_group ) . '\'' ) );

					if ( $test['template_name'] )
					{
						/* Update new */
						$this->DB->update( 'skin_templates', array( 'template_set_id' => $this->_skinSet['set_id'], 'template_master_key' => '' ), 'template_id=' . $newId );
						
						$this->_template['template_set_id'] = $this->_skinSet['set_id'];
						$this->_template['template_id']     = $newId;
					}
				}
			}
		}
		catch( Exception $err )
		{ 
			throw new Sabre_DAV_Exception_Forbidden( $err->getMessage() );
		}
		
		return true;
	}
	
	protected function _getTemplateContent()
	{
		if ( ! $this->_template )
		{
			return false;
		}

		if ( $this->_content === null )
		{
			if ( $this->_group != 'css' )
			{
				$template = $this->skinFunctions->fetchTemplateBitForEdit( $this->_template['template_id'], $this->_skinSet['set_id'] );
				$data     = $template['template_data'];
				
				if ( ! empty( $data ) )
				{
					$template['_template_content'] = '<ips:template parameters="' . IPSText::formToText( $data ) . '" />' . "\n" . $template['_template_content'];
				}
				
				$this->_content = ( isset( $template['_template_content'] )  ) ? $template['_template_content'] : false;
			}
			else
			{
				$template = $this->skinFunctions->fetchCSSForEdit( $this->_template['css_id'], $this->_skinSet['set_id'] );
				
				$this->_content = ( isset( $template['css_content'] )  ) ? $template['css_content'] : false;
			}
		}
		
		return $this->_content;
	}
}
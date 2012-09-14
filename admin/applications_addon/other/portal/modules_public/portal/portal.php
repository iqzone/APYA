<?php
/*
+--------------------------------------------------------------------------
|   Portal 1.1.0
|   =============================================
|   by Michael John
|   Copyright 2011-2012 DevFuse
|   http://www.devfuse.com
+--------------------------------------------------------------------------
|   Based on IP.Board Portal by Invision Power Services
|   Website - http://www.invisionpower.com/
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_portal_portal_portal extends ipsCommand
{
	/**
	 * Array of portal objects
	 *
	 * @access	protected
	 * @var 	array 				Registered portal objects
	 */
	protected $portal_object		= array();

	/**
	 * Array of replacement tags
	 *
	 * @access	protected
	 * @var 	array 				Replacement tags
	 */
	protected $replace_tags			= array();
	
	/**
	 * Array of tags to module...
	 *
	 * @access	protected
	 * @var 	array 				Tags => Modules mapping
	 */
	protected $remap_tags_module 	= array();
	
	/**
	 * Array of tags to function...
	 *
	 * @access	protected
	 * @var 	array 				Tags => Function mapping
	 */
	protected $remap_tags_function	= array();
	
	/**
	 * Array of module objects
	 *
	 * @access	protected
	 * @var 	array 				Module objects
	 */
	protected $module_objects		= array();
	
	/**
	 * Basic template, replaced as needed
	 *
	 * @access	protected
	 * @var 	string 				Basic skin template to replace
	 */
	protected $template				= array();

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$conf_groups		= array();
		$found_tags			= array();
		$found_modules		= array();
		
		//-----------------------------------------
		// Make sure the portal is installed an enabled
		//-----------------------------------------
		
		if( ! IPSLib::appIsInstalled( 'portal' ) )
		{
			$this->registry->output->showError( 'no_permission', 1076, null, null, 404 );
		}
		
		//-----------------------------------------
		// Get settings...
		//-----------------------------------------
		
		foreach( $this->cache->getCache('portal') as $portal_data )
		{
			if( ! IPSLib::appIsInstalled( $portal_data['_app_dir'] ) )
			{
				continue;
			}
			
			//-----------------------------------------
			// Remap tags
			//-----------------------------------------
			
			if ( is_array( $portal_data['pc_exportable_tags'] ) AND count( $portal_data['pc_exportable_tags'] ) )
			{
				foreach( $portal_data['pc_exportable_tags'] as $tag => $tag_data )
				{
					$this->remap_tags_function[ $tag ]	= $tag_data[0];
					$this->remap_tags_module[ $tag ]	= $portal_data['pc_key'];
				}
			}
		}		

		//-----------------------------------------
		// Get global skin and language files
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_portal' ) );

		//-----------------------------------------
		// Assign skeletal template ma-doo-bob
		//-----------------------------------------
        
		$this->DB->build( array( 'select' => '*', 'from' => 'portal_blocks', 'order' => 'position ASC' ) );
		$this->DB->execute();
		
		$leftBlocks  = array();	
		$mainBlocks  = array();        
		$rightBlocks = array();
        		 
		while( $r = $this->DB->fetch() )
		{          
            $portalBlocks[] = $r;            					 	
		}    
        
        if( is_array( $portalBlocks ) AND count( $portalBlocks ) )
        {
            foreach( $portalBlocks as $block )
            {
                ob_start();
                eval("?>".$block['block_code']."<?php\n");
                $block['block_code'] = ob_get_contents();
                ob_end_clean();	
                
                if( $block['align'] == '1' )
                {
                    $leftBlocks[] = $block;    
                }
                else if( $block['align'] == '2' )
                {
                    $mainBlocks[] = $block;    
                }
                else if( $block['align'] == '3' )
                {
                    $rightBlocks[] = $block;    
                }            
            }            
        }
        		
		$this->template = $this->registry->getClass('output')->getTemplate('portal')->skeletonTemplate( $leftBlocks, $mainBlocks, $rightBlocks );
        
		
		//-----------------------------------------
		// Grab all special tags
		//-----------------------------------------
		
		preg_match_all( "#<!--\:\:(.+?)\:\:-->#", $this->template, $match );
		
		//-----------------------------------------
		// Assign functions
		//-----------------------------------------
		
		for ( $i=0, $m=count($match[0]); $i < $m; $i++ )
		{
			$tag = $match[1][$i];
			
			if ( $this->remap_tags_module[ $tag ] )
			{
				$found_tags[ $tag ] = 1;
				
				if ( $this->remap_tags_module[ $tag ])
				{
					$found_modules[ $this->remap_tags_module[ $tag ] ] = 1;
				}
			}
		}
			
		//-----------------------------------------
		// Require modules...
		//-----------------------------------------
		
		if ( is_array( $found_modules ) AND count( $found_modules ) )
		{
			foreach( $found_modules as $mod_name => $pointless )
			{
				if ( ! is_object( $this->module_objects[ $mod_name ] ) )
				{
					if ( file_exists( $this->caches['portal'][ $mod_name ]['_file_location'] ) )
					{
						$constructor = IPSLib::loadLibrary( $this->caches['portal'][ $mod_name ]['_file_location'], 'ppi_' . $mod_name, $this->caches['portal'][ $mod_name ]['_app_dir'] );
						$this->module_objects[ $mod_name ] = new $constructor();
						$this->module_objects[ $mod_name ]->makeRegistryShortcuts( $this->registry );
                        
                        if( method_exists($this->module_objects[ $mod_name ], 'init') )
                        {
                            $this->module_objects[ $mod_name ]->init();    
                        }
					}
				}
			}
		}
		
		//-----------------------------------------
		// Get the tag replacements...
		//-----------------------------------------
		
		if ( is_array( $found_tags ) AND count( $found_tags ) )
		{
			foreach( $found_tags as $tag_name => $even_more_pointless )
			{
				$mod_obj	= $this->remap_tags_module[ $tag_name ];
				$fun_obj	= $this->remap_tags_function[ $tag_name ];
				
				if ( method_exists( $this->module_objects[ $mod_obj ], $fun_obj ) )
				{
					$this->replace_tags[ $tag_name ] = $this->module_objects[ $mod_obj ]->$fun_obj();
					continue;
				}
			}
		}
		
		$this->_do_output();
 	}
 	
 	/**
 	 * Internal do output method.  Extend class and overwrite method if you need to modify this functionality.
 	 *
 	 * @access	protected
 	 * @return	void
 	 */
 	protected function _do_output()
 	{
 		//-----------------------------------------
		// SITE REPLACEMENTS
		//-----------------------------------------
		
		foreach( $this->replace_tags as $sbk => $sbv )
		{
			$this->template = str_replace( "<!--::" . $sbk . "::-->", $sbv, $this->template );
		}
 		
 		//-----------------------------------------
 		// Pass to print...
 		//-----------------------------------------
 		
 		$this->registry->output->addContent( $this->template );
 		$this->registry->output->setTitle( $this->lang->words['portal_title'] .' - '. $this->settings['board_name'] );
 		$this->registry->output->addNavigation( $this->lang->words['portal_title'], 'app=portal', "false", 'app=portal' );
        
        /*$this->registry->output->addContent( "<br /><div class='ipsType_smaller desc lighter right' style='clear: both; text-align:right;'>Portal v1.1.0 by <a href='http://www.devfuse.com/' title='DevFuse home page'>DevFuse</a> | Based on IP.Board Portal by <a href='http://www.invisionpower.com/products/board/' title='IP.Board Product Page'>IPS</a></div>" );*/
 		
 		$this->registry->output->sendOutput();

		exit();
 	}

}
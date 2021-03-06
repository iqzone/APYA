<?php

/*
+--------------------------------------------------------------------------
|   IP.Blog Component v<#VERSION#>
|   =============================================
|   by Remco Wilting
|   (c) 2001 - 2005 Invision Power Services, Inc.
|   http://www.invisionpower.com
|   =============================================
|   Web: http://www.invisionblog.com
+--------------------------------------------------------------------------
| > $Date: 2011-12-19 09:55:06 -0500 (Mon, 19 Dec 2011) $
| > $Revision: 4 $
| > $Author: ips_terabyte $
+--------------------------------------------------------------------------
|
|   > COMMUNITY BLOG SETUP INSTALLATION MODULES
|   > Script written by Matt Mecham
|   > Community Blog version by Remco Wilting
|   > Date started: 23rd April 2004
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @access	public
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			default:
				$this->upgradeBlogSettings();
			break;
		}
		
		return true;	
	}
	
	/*-------------------------------------------------------------------------*/
	// Blog Settings
	/*-------------------------------------------------------------------------*/
	
	public function upgradeBlogSettings()
	{
		$cnt = 0;

		$this->DB->build( array(
								'select'	=>	'blog_id, blog_settings',
								'from'		=>	'blog_blogs',
								'where'		=>	"blog_type = 'local'"
						)	);
		$qid = $this->DB->execute();
		while( $row = $this->DB->fetch( $qid ) )
		{
			$settings = unserialize( $row['blog_settings'] );
			$update = array( 'blog_allowguests' => $settings['allowguest'] );
			
			$this->DB->update( 'blog_blogs', $update, "blog_id = {$row['blog_id']}" );
			
			$cnt++;
		}

		$this->registry->output->addMessage( "$cnt Blogs updated...." );
	}

}
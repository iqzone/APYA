<?php
/*
+--------------------------------------------------------------------------
|   IP.Board v3.3.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
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
	 * @var		string
	 */
	protected $_output = '';
	
	/**
	 * fetchs output
	 * 
	 * @return	string
	 */
	public function fetchOutput()
	{
		return $this->_output;
	}
	
	/**
	 * Execute selected method
	 *
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
			case 'oldhooks':
				$this->removeOldHooks();
				break;
		}

		return true;
	}

	/**
	 * Remove old hooks
	 * 
	 * @return	@e void
	 */
	public function removeOldHooks()
	{
		/* Hooks to remove */
		$hooks		= array( 'sharelinks', 'fb_like' );
		$_hookIds	= array();
		$_total		= 0;

		/* Get hook records */
		$this->DB->build( array( 'select' => 'hook_id', 'from' => 'core_hooks', 'where' => "hook_key IN('" . implode( "','", $hooks ) . "')" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$_hookIds[]	= $r['hook_id'];
		}
		
		/* Remove associated files */
		if( count($_hookIds) )
		{
			$this->DB->build( array( 'select' => 'hook_file_stored', 'from' => 'core_hooks_files', 'where' => 'hook_hook_id IN(' . implode( ',', $_hookIds ) . ')' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				@unlink( IPS_HOOKS_PATH . $r['hook_file_stored'] );
			}
			
			/* Remove hook records */
			$this->DB->delete( 'core_hooks_files', 'hook_hook_id IN(' . implode( ',', $_hookIds ) . ')' );
			$this->DB->delete( 'core_hooks', 'hook_id IN(' . implode( ',', $_hookIds ) . ')' );
			
			$_total++;
		}

		/* Message */
		$this->registry->output->addMessage("{$_total} outdated hook(s) uninstalled....");
		
		/* Next Page */
		$this->request['workact'] = '';
	}
}
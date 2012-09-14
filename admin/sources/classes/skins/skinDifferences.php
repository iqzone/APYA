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

class skinDifferences extends skinCaching
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
	 * Diff class object
	 * @var	object
	 */
	protected $classDifference;
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make diff class object */
		require_once( IPS_KERNEL_PATH . 'classDifference.php' );/*noLibHook*/
		$this->classDifference         = new classDifference();
		$this->classDifference->method = 'PHP';
		
		/* Make object */
		parent::__construct( $registry );
	}
	
	/**
	 * Revert changes
	 *
	 * @access	public
	 * @param	array		Array of item (change_id) ids
	 * @return	int			Number of items reverted
	 */
	public function revert( $items )
	{
		/* INIT */
		$reverted = 0;
		$setIds    = array();
		$has       = array( 'css' => 0, 'template' => 0 );
		
		/* Basic check */
		if ( ! count( $items ) )
		{
			return false;
		}
		
		/* Fetch Data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_merge_changes',
								 'where'  => 'change_id IN(' . implode( ',', IPSLib::cleanIntArray( $items ) ) . ')' ) );
		$o = $this->DB->execute();
		
		/* GO freakiNG LoOPy */
		while( $row = $this->DB->fetch( $o ) )
		{
			$_oldContent = false;
			
			/* KNock out the bad boyz who ain't not worth it eva girlfriend */
			if ( ! $row['change_changes_applied'] OR ! $row['change_original_content'] )
			{
				/* slowly starting to lose the plot */
				continue;
				/* don't mind if I do! */
			}
			
			/* Which text? */
			$text    = $row['change_original_content'];
			
			/* Fetch session */
			$session = $this->fetchSession( $row['change_session_id'] );
			
			/* Store set id */
			$setIds[ $session['merge_set_id'] ] = $session['merge_set_id'];
			
			/* Fetch customized template details */
			if ( $row['change_data_type'] == 'template' )
			{
				$_data = $this->DB->buildAndFetch( array( 'select' => '*',
														  'from'   => 'skin_templates',
														  'where'  => 'template_set_id=' . intval( $session['merge_set_id'] ) . ' AND template_group=\'' . $this->DB->addSlashes( $row['change_data_group'] ) . '\' AND template_name=\'' . $this->DB->addSlashes( $row['change_data_title'] ) . '\'' ) );
			}
			else
			{
				$_data = $this->DB->buildAndFetch( array( 'select' => '*',
														  'from'   => 'skin_css',
														  'where'  => 'css_set_id=' . intval( $session['merge_set_id'] ) . ' AND css_app=\'' . $this->DB->addSlashes( $row['change_data_group'] ) . '\' AND css_group=\'' . $this->DB->addSlashes( $row['change_data_title'] ) . '\'' ) );
			}
		
			if ( $row['change_data_type'] == 'template' )
			{
				if ( $_data['template_id'] )
				{
					$has['template']++;
					$reverted++;
					/* Should in theory only ever get here, but you NEVER KNOW */
					$this->DB->update( 'skin_templates', array( 'template_content'     => $text,
																'template_user_edited' => 1,
																'template_updated'     => time() ), 'template_id=' . $_data['template_id'] );
				}
			}
			else
			{
				if ( $_data['css_id'] )
				{
					$has['css']++;
					$reverted++;
					$this->DB->update( 'skin_css', array( 'css_content'  	=> $text,
														  'css_updated'  	=> time() ), 'css_id=' . $_data['css_id'] );
				}
			}
			
			/* Did we successfully commit? */
			if ( is_array( $_data ) AND count( $_data ) )
			{
				/* Update row */
				$this->DB->update( 'skin_merge_changes', array( 'change_original_content' => '', 'change_changes_applied' => 0 ), 'change_id=' . $row['change_id'] );
			}
		}
		
		/* Do we need to recache? */
		if ( count( $setIds ) )
		{
			foreach( array_keys( $setIds ) as $id )
			{
				if ( $has['template'] )
				{
					$this->rebuildPHPTemplates( $id );
				}
				
				if ( $has['css'] )
				{
					$this->rebuildCSS( $id );
				}
			}
		}
		
		return $reverted;
	}
	
	/**
	 * Commit changes
	 *
	 * @access	public
	 * @param	array		Array of item (change_id) ids
	 * @return	int			Number of items committed
	 */
	public function commit( $items )
	{
		/* INIT */
		$committed = 0;
		$setIds    = array();
		$has       = array( 'css' => 0, 'template' => 0 );
		
		/* Basic check */
		if ( ! count( $items ) )
		{
			return false;
		}
		
		/* Fetch Data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_merge_changes',
								 'where'  => 'change_id IN(' . implode( ',', IPSLib::cleanIntArray( $items ) ) . ')' ) );
		$o = $this->DB->execute();
		
		/* GO freakiNG LoOPy */
		while( $row = $this->DB->fetch( $o ) )
		{
			$_oldContent = false;
			
			/* KNock out the bad boyz who ain't not worth it eva girlfriend */
			if ( ! $row['change_can_merge'] OR ( $row['change_is_conflict'] AND ! $row['change_final_content'] ) )
			{
				/* slowly starting to lose the plot */
				continue;
				/* don't mind if I do! */
			}
			
			/* Which text? */
			$text    = ( $row['change_final_content'] ) ? $row['change_final_content'] : $row['change_merge_content'];
			
			/* Fetch session */
			$session = $this->fetchSession( $row['change_session_id'] );
			
			/* Store set id */
			$setIds[ $session['merge_set_id'] ] = $session['merge_set_id'];
			
			/* Fetch customized template details */
			if ( $row['change_data_type'] == 'template' )
			{
				$_data = $this->DB->buildAndFetch( array( 'select' => '*',
														  'from'   => 'skin_templates',
														  'where'  => '(template_set_id=' . intval( $session['merge_set_id'] ) . ' OR ( template_set_id=0 AND template_master_key=\'' . $this->DB->addSlashes( $session['merge_master_key'] ) . '\' ) ) AND template_group=\'' . $this->DB->addSlashes( $row['change_data_group'] ) . '\' AND template_name=\'' . $this->DB->addSlashes( $row['change_data_title'] ) . '\'',
														  'order'  => 'template_set_id DESC',
														  'limit'  => array(0,1) ) );
			}
			else
			{
				$_data = $this->DB->buildAndFetch( array( 'select' => '*',
														  'from'   => 'skin_css',
														  'where'  => '(css_set_id=' . intval( $session['merge_set_id'] ) . ' OR ( css_set_id=0 AND css_master_key=\'' . $this->DB->addSlashes( $session['merge_master_key'] ) . '\' ) ) AND css_app=\'' . $this->DB->addSlashes( $row['change_data_group'] ) . '\' AND css_group=\'' . $this->DB->addSlashes( $row['change_data_title'] ) . '\'',
														  'order'  => 'css_set_id DESC',
														  'limit'  => array(0,1) ) );
			}
		
			if ( $row['change_data_type'] == 'template' )
			{
				if ( $_data['template_id'] )
				{
					if ( $this->testTemplateBitSyntax( $_data['template_name'], $_data['template_data'], $text ) !== TRUE )
					{
						/* Could log or throw an error here */
						continue;
					}
		
					$_oldContent = $_data['template_content'];
					$committed++;
					$has['template']++;
					
					/* Should in theory only ever get here, but you NEVER KNOW */
					$this->DB->update( 'skin_templates', array( 'template_content'     => $text,
																'template_user_edited' => 1,
																'template_updated'     => time() ), 'template_id=' . $_data['template_id'] );
				}
			}
			else
			{
				if ( $_data['css_id'] )
				{ 
					$_oldContent = $_data['css_content'];
					$committed++;
					$has['css']++;
					
					$this->DB->update( 'skin_css', array( 'css_content'  	=> $text,
														  'css_updated'  	=> time() ), 'css_id=' . $_data['css_id'] );
				}
			}
			
			/* Did we successfully commit? */
			if ( $_oldContent !== false )
			{
				/* Update row */
				$this->DB->update( 'skin_merge_changes', array( 'change_original_content' => $_oldContent, 'change_changes_applied' => 1 ), 'change_id=' . $row['change_id'] );
			}
		}
		
		/* Do we need to recache? */
		if ( count( $setIds ) )
		{
			foreach( array_keys( $setIds ) as $id )
			{
				if ( $has['template'] )
				{
					$this->rebuildPHPTemplates( $id );
				}
				
				if ( $has['css'] )
				{
					$this->rebuildCSS( $id );
				}
			}
		}
		
		return $committed;
	}

	/**
	 * Resolve Conflict Automatically
	 *
	 * @access	public
	 * @param	array		Array of item (change_id) ids
	 * @param	type		Which wins? YOU DECIDE (custom/new)
	 * @return	nufink
	 */
	public function resolveConflict( $items, $type='new' )
	{
		/* Basic check */
		if ( ! count( $items ) )
		{
			return false;
		}
		
		/* Fetch Data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_merge_changes',
								 'where'  => 'change_id IN(' . implode( ',', IPSLib::cleanIntArray( $items ) ) . ')' ) );
		$o = $this->DB->execute();
		
		/* GO freakiNG LoOPy */
		while( $row = $this->DB->fetch( $o ) )
		{
			$text = $row['change_merge_content'];
			
			/* Re-format  */
			preg_match_all( "#<ips:conflict id=\"([0-9]+?)\">((?<!(</ips:conflict>)).+?)</ips:conflict>#s", $text, $matches );
			
			if ( is_array($matches) AND count($matches) )
			{
				foreach( $matches[1] as $index => $m )
				{	
					/* Yeah, I like readable code and copying and pasting evidently */
					$_all	  = $matches[0][$index];
					$_id      = $matches[1][$index];
					$_content = $matches[2][$index];
					
					if ( $_id != null AND $_content )
					{
						/* Save which block? */
						if ( $type == 'new' )
						{
							$_content = preg_replace( "#(?:\n)?<ips:cblock type=\"original\">(?:\n)?(.+?)(?:\n)?</ips:cblock>#s", "", $_content );
							$_content = preg_replace( "#(?:\n)?<ips:cblock type=\"new\">(?:\n)?(.+?)(?:\n)?</ips:cblock>#s", "\n\\1", $_content );
						}
						else
						{
							$_content = preg_replace( "#(?:\n)?<ips:cblock type=\"original\">(?:\n)?(.+?)(?:\n)?</ips:cblock>#s", "\n\\1", $_content );
							$_content = preg_replace( "#(?:\n)?<ips:cblock type=\"new\">(?:\n)?(.+?)(?:\n)?</ips:cblock>#s", "", $_content );
						}
						
						$text = str_replace( $_all,  $_content, $text );
					}
				}
				
				/* Save to db */
				$this->DB->update( 'skin_merge_changes', array( 'change_final_content' => $text ), 'change_id=' . intval( $row['change_id'] ) );
			}
		}
		
		return true;
	}
	
	/**
	 * Format Merge: Edit
	 *
	 * @access	public
	 * @param	string		Content from DB
	 * @return	string		Content suitable for editing in a text area
	 */
	public function formatMergeForEdit( $text )
	{
		/* Re-format mark-up for preview */
		preg_match_all( "#<ips:conflict id=\"([0-9]+?)\">(.+?)</ips:conflict>#s", $text, $matches );
		
		if ( is_array($matches[1]) AND count($matches[1]) )
		{
			foreach( $matches[1] as $index => $m )
			{		
				/* Yeah, I like readable code */
				$_all	  = $matches[0][$index];
				$_id      = $matches[1][$index];
				$_content = $matches[2][$index];
				
				if ( $_id != null AND $_content )
				{
					/* Format old default block */
					$_content = preg_replace( "#(?:\n)?<ips:cblock type=\"original\">(?:\n)?(.+?)(?:\n)?</ips:cblock>#s", "~~~~~~~~~~ CUSTOM CONTENT\n\\1", $_content );
					
					/* Format custom block */
					$_content = preg_replace( "#(?:\n)?<ips:cblock type=\"new\">(?:\n)?(.+?)(?:\n)?</ips:cblock>#s", "\n===================\n\\1\n^^^^^^^^^^ NEW DEFAULT\n", $_content );
					
					$text = str_replace( $_all,  $_content, $text );
				}
			}
		}
		
		/* Encode */
		$text = IPSText::textToForm( $text );
		
		/* Convert special place holders */
		$text = str_replace( '~~~~~~~~~~', '<<<<<<<<<<', $text );
		$text = str_replace( '^^^^^^^^^^', '>>>>>>>>>>', $text );
		
		return $text;
	}
	
	/**
	 * Format Merge: Preview
	 *
	 * @access	public
	 * @param	string		Content from DB
	 * @return	string		Content suitable for viewing in a web page
	 */
	public function formatMergeForPreview( $text )
	{
		/* Encode and format white space */
		$text = htmlspecialchars( $text );
		$text = str_replace( "\n", "<br />", $text );
		$text = str_replace( "\t", "&nbsp; &nbsp; ", $text );
		
		/* Un-encode special mark-up */
		$text = preg_replace( "#&lt;ips:conflict id=&quot;([0-9]+?)&quot;&gt;#", "<ips:conflict id=\"\\1\">", $text );
		$text = preg_replace( "#&lt;ips:cblock type=&quot;(\w+?)&quot;&gt;#", "<ips:cblock type=\"\\1\">", $text );
		$text = preg_replace( "#&lt;/ips:(cblock|conflict)&gt;#", "</ips:\\1>", $text );
		
		/* Re-format mark-up for preview */
		preg_match_all( "#<ips:conflict id=\"([0-9]+?)\">(.+?)</ips:conflict>#s", $text, $matches );
		
		if ( is_array($matches[1]) AND count($matches[1]) )
		{
			foreach( $matches[1] as $index => $m )
			{		
				/* Yeah, I like readable code */
				$_all	  = $matches[0][$index];
				$_id      = $matches[1][$index];
				$_content = $matches[2][$index];
				
				if ( $_id != null AND $_content )
				{
					/* Format old default block */
					$_content = preg_replace( "#(?:<br />)?<ips:cblock type=\"original\">(?:<br />)?(.+?)(?:<br />)?</ips:cblock>#s", "<div class=\"ips_merge_custom __c{$_id}\" id=\"ips_merge_custom_id_{$_id}\"><h3>Custom Value</h3><p>\\1</p></div>", $_content );
					
					/* Format custom block */
					$_content = preg_replace( "#(?:<br />)?<ips:cblock type=\"new\">(?:<br />)?(.+?)(?:<br />)?</ips:cblock>#s", "<div class=\"ips_merge_new __n{$_id}\" id=\"ips_merge_new_id_{$_id}\"><h3>New Value</h3><p>\\1</p></div>", $_content );
					
					$text = str_replace( $_all,  "<div class=\"ips_merge_wrap __w{$_id}\"id=\"ips_merge_wrap_id_{$_id}\">" . $_content . "</div>", $text );
				}
			}
		}
		//IPSDebug::addLogMessage( str_replace( '<br />', "<br />\n", $text), 'mdmdmd', false, true, false );
		return $text;
	}
	
	/**
	 * Fetch differences between items
	 * Can be CSS or template
	 *
	 * @access	public
	 * @param	string		Original text
	 * @param	string		New text
	 * @return	mixed		Returns compiled diff text or false if no diffs
	 */
	public function fetchDifferences( $original, $new, $method='inline' )
	{
		$this->classDifference->diff_found = false;
		
		$diff = $this->classDifference->getDifferences( $original, $new, $method='inline' );
		
		if ( $this->classDifference->diff_found )
		{
			return $diff;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Fetch original item (from _previous table)
	 * Can be CSS or template
	 *
	 * @access	public
	 * @param	array		Array of data
	 * @return	string		Item content or false
	 */
	public function fetchOriginalItem( $data )
	{
		/* INIT */
		$original = false;
		$_data    = array();
		
		/* Template? */
		if ( $data['data_type'] == 'template' )
		{
			$_data = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'skin_templates_previous',
													  'where'  => 'p_template_group=\'' . $this->DB->addSlashes( $data['data_group'] ) . '\' AND p_template_name=\'' . $this->DB->addSlashes( $data['data_title'] ) . '\' AND p_template_master_key=\'' . $this->DB->addSlashes( $data['data_master_key'] ) . '\'' ) );
		}
		else
		{
			$_data = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'skin_css_previous',
													  'where'  => 'p_css_app=\'' . $this->DB->addSlashes( $data['data_group'] ) . '\' AND p_css_group=\'' . $this->DB->addSlashes( $data['data_title'] ) . '\' AND p_css_master_key=\'' . $this->DB->addSlashes( $data['data_master_key'] ) . '\'' ) );
		}
		
		if ( is_array( $_data ) AND count( $_data ) )
		{
			if ( $data['data_type'] == 'template' )
			{
				$original = $_data['p_template_content'];
			}
			else
			{
				$original = $_data['p_css_content'];
			}
			
		}
		
		return $original;
	}
	
	/**
	 * Fetch new item (from current table)
	 * Can be CSS or template
	 *
	 * @access	public
	 * @param	array		Array of data
	 * @return	string		Item content or false
	 */
	public function fetchNewItem( $data )
	{
		/* INIT */
		$original = false;
		$_data    = array();
		
		/* Template? */
		if ( $data['data_type'] == 'template' )
		{
			$_data = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'skin_templates',
													  'where'  => 'template_group=\'' . $this->DB->addSlashes( $data['data_group'] ) . '\' AND template_name=\'' . $this->DB->addSlashes( $data['data_title'] ) . '\' AND template_master_key=\'' . $this->DB->addSlashes( $data['data_master_key'] ) . '\'' ) );
		}
		else
		{
			$_data = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'skin_css',
													  'where'  => 'css_app=\'' . $this->DB->addSlashes( $data['data_group'] ) . '\' AND css_group=\'' . $this->DB->addSlashes( $data['data_title'] ) . '\' AND css_master_key=\'' . $this->DB->addSlashes( $data['data_master_key'] ) . '\'' ) );
		}
		
		if ( is_array( $_data ) AND count( $_data ) )
		{
			if ( $data['data_type'] == 'template' )
			{
				$original = $_data['template_content'];
			}
			else
			{
				$original = $_data['css_content'];
			}
			
		}
		
		return $original;
	}

	
	/**
	 * Fetch custom item (user edited item)
	 * Can be CSS or template
	 *
	 * @access	public
	 * @param	array		Array of data
	 * @return	array		Item content or false
	 */
	public function fetchCustomItem( $data )
	{
		/* INIT */
		$custom   = false;
		$_data    = array();
		
		/* Template? */
		if ( $data['data_type'] == 'template' )
		{
			$_data = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'skin_templates',
													  'where'  => 'template_set_id=' . intval( $data['data_set_id'] ) . ' AND template_group=\'' . $this->DB->addSlashes( $data['data_group'] ) . '\' AND template_name=\'' . $this->DB->addSlashes( $data['data_title'] ) . '\'' ) );
		}
		else
		{
			$_data = $this->DB->buildAndFetch( array( 'select' => '*',
													  'from'   => 'skin_css',
													  'where'  => 'css_set_id=' . intval( $data['data_set_id'] ) . ' AND css_app=\'' . $this->DB->addSlashes( $data['data_group'] ) . '\' AND css_group=\'' . $this->DB->addSlashes( $data['data_title'] ) . '\'' ) );
		}
		
		if ( is_array( $_data ) AND count( $_data ) )
		{
			if ( $data['data_type'] == 'template' )
			{
				$custom = $_data['template_content'];
			}
			else
			{
				$custom = $_data['css_content'];
			}
		}
		
		return $custom;
	}

	
	/**
	 * Fetch all skin difference sessions
	 *
	 * @access	public
	 * @return	array		Array of skin difference sessions
	 */
	public function fetchSessions()
	{
		/* INIT */
		$sessions = array();
		
		/* Fetch set up class */
		require_once( IPS_ROOT_PATH . "setup/sources/base/setup.php" );/*noLibHook*/
		
		/* Fetch version numbers */
		$versions = IPSSetUp::fetchXmlAppVersions('core');
		
		/* Load data */
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_merge_session',
								 'order'  => 'merge_date DESC' ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Prep dates */
			$row['_date'] = ipsRegistry::getClass( 'class_localization')->getDate( $row['merge_date'], 'TINY' );
			
			/* Prep version numbers */
			$row['_oldHumanVersion'] = $versions[ $row['merge_old_version'] ];
			$row['_newHumanVersion'] = $versions[ $row['merge_new_version'] ];
			
			/* Skin data */
			$row['_skinData'] = $this->fetchSkinData( $row['merge_set_id'] );
			
			/* Title */
			$row['_title']    = $this->fetchReportTitle( $row );
			
			$sessions[ $row['merge_id'] ] = $row;
		}
		
		return $sessions;
	}
	
	/**
	 * Fetch a title
	 *
	 * @access	public
	 * @return	string		Report title string
	 */
	public function fetchReportTitle( $session )
	{
		if ( ! isset( $session['_skinData'] ) )
		{
			$session = $this->fetchSession( $session['merge_id'], true );
		}
		
		return $session['_skinData']['set_name'] . ' (' . $session['_oldHumanVersion'] . ' &gt; ' . $session['_newHumanVersion'] .')';
	}
	
	/**
	 * Remove a session
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @return	bool		True
	 */
	public function removeSession( $sessionID )
	{
		/* Delete 'em */
		$this->DB->delete('skin_merge_session', 'merge_id='.$sessionID );
		
		$this->DB->delete('skin_merge_changes', 'change_session_id='.$sessionID );
	
		return TRUE;
	}
	
	/**
	 * Create new session
	 *
	 * @access	public
	 * @param	int			Template set ID to run merge on
	 * @return	int			New session ID
	 *
	 * Exception Codes
	 * NO_SET				No such skin set
	 * NO_PREVIOUS			No previous data found
	 */
	public function createSession( $setId )
	{
		/* INIT */
		$templates	  = array();
		$css		  = array();
		$skinData     = $this->fetchSkinData( $setId );
		
		/* Quick test */
		if ( ! $skinData['set_id'] )
		{
			throw new Exception('NO_SET');
		}
		
		/* Got a master key? */
		$skinData['set_master_key'] = ( $skinData['set_master_key'] ) ? $skinData['set_master_key'] : 'root';
		
		/* Fetch version ID of previous elements */
		$previous  = $this->DB->buildAndFetch( array( 'select' => 'p_template_long_version',
													  'from'   => 'skin_templates_previous',
													  'order'  => 'p_template_long_version DESC',
													  'limit'  => array(0,1) ) );
		
		
		/* Got any previous data? */
		if ( ! $previous['p_template_long_version'] )
		{
			throw new Exception('NO_PREVIOUS');
		}
		
		/* Delete all previous with this set ID */
		$this->DB->build( array( 'select' => 'merge_id',
								 'from'   => 'skin_merge_session',
								 'where'  => 'merge_set_id=' . $setId ) );
								 
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$this->removeSession( $row['merge_id'] );
		}
		
		/* Now grab master elements based on root key data */
		$tCount    = 0;
		$templates = $this->fetchTemplates( $skinData['set_master_key'], 'allNoContent' );
		$css       = $this->fetchCSS( $skinData['set_master_key'], false );
		
		/* Get correct count of templates */
		foreach( $templates as $group => $data )
		{
			$tCount += count( $templates[ $group ] );
		}
		
		/* Grab current version numbers */
		$cVersion  = IPSLib::fetchVersionNumber();
		
		/* Create session */
		$this->DB->insert( 'skin_merge_session', array( 'merge_date'    		 => time(),
													 	'merge_set_id'			 => $setId,
													 	'merge_master_key'		 => $skinData['set_master_key'],
													 	'merge_old_version'		 => $previous['p_template_long_version'],
													 	'merge_new_version'		 => $cVersion['long'],
													 	'merge_templates_togo'   => $tCount,
													 	'merge_css_togo'		 => intval( count( $css ) ),
													 	'merge_templates_done'   => 0,
													 	'merge_css_done'		 => 0 ) );
													 	
																		
		$diffSesssionID = $this->DB->getInsertId();
		
		return $diffSesssionID;
	}
	
	/**
	 * Fetch a report
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @return	array
	 */
	public function fetchReport( $diffSessionID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return = array( 'counts' => array( 'missing' => 0, 'changed' => 0 ), 'data' => array() );
	
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_merge_changes',
								 'where'  => 'change_session_id='.$diffSessionID,
								 'order'  => 'change_data_group ASC, change_data_title ASC' ) );
		
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Gen data
			//-----------------------------------------
			
			$row['_key']  = $row['change_key'];
			$row['_size'] = IPSLib::sizeFormat( IPSLib::strlenToBytes( IPSText::mbstrlen( $row['change_data_content'] ) ) );
			
			//-----------------------------------------
			// Diff type
			//-----------------------------------------
			
			if ( $row['change_is_new'] )
			{
				$row['_is'] = 'new';
				$return['counts']['missing']++;
			}
			else
			{
				$row['_is'] = 'changed';
				$return['counts']['changed']++;
			}
			
			/* Is it CSS? */
			if ( $row['change_data_type'] == 'css' )
			{
				$row['change_data_group'] = 'css';
				$row['change_data_title'] .= '.css';
			}
			
			/* Fetch basic stats */
			if ( $row['change_data_content'] )
			{
				$row['_diffs'] = substr_count( $row['change_data_content'], '-ips-match:1' );
			}
			
			if ( $row['change_merge_content'] AND stristr( $row['change_merge_content'], '<ips:conflict' ) )
			{
				$row['_conflicts'] = substr_count( $row['change_merge_content'], '<ips:conflict' );
			}
			
			//-----------------------------------------
			// Add data...
			//-----------------------------------------
			
			$return['data'][ $row['change_data_group'] ][ $row['_key'] ] = $row;
		}
		
		return $return;
	}
	
	/**
	 * Fetch a session
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @param	bool		Parse data
	 * @return	mixed 		Array of data, or false
	 */
	public function fetchSession( $diffSessionID, $parse=false )
	{
		$session = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'skin_merge_session', 'where' => 'merge_id='.$diffSessionID ) );
		
		if ( $session['merge_id']  AND $parse === true )
		{
			/* Fetch set up class */
			require_once( IPS_ROOT_PATH . "setup/sources/base/setup.php" );/*noLibHook*/
			
			/* Fetch version numbers */
			$versions = IPSSetUp::fetchXmlAppVersions('core');
			
			/* Prep dates */
			$session['_date'] = ipsRegistry::getClass('class_localization')->getDate( $session['merge_date'], 'TINY' );
			
			/* Prep version numbers */
			$session['_oldHumanVersion'] = $versions[ $session['merge_old_version'] ];
			$session['_newHumanVersion'] = $versions[ $session['merge_new_version'] ];
			
			/* Skin data */
			$session['_skinData'] = $this->fetchSkinData( $session['merge_set_id'] );
			
			/* Title */
			$session['_title']    = $this->fetchReportTitle( $session );
		}
		
		return ( $session['merge_id'] ) ? $session : FALSE;
	}
	
	/**
	 * Return the total number of bits to process
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @param	string		Type: template or css
	 * @return	int			Number of bits
	 */
	public function fetchNumberSessionTemplateBits( $diffSesssionID, $type='' )
	{
		$return = 0;
		
		/* Fetch data */
		$data = $this->DB->buildAndFetch( array( 'select' => '*',
												 'from'   => 'skin_merge_session',
												 'where'  => 'merge_id=' . intval( $diffSesssionID ) ) );
												 
		if ( $type )
		{
			$return = ( $type == 'template' ) ? $data['merge_templates_togo'] : $data['merge_css_togo'];
		}
		else
		{
			$return = $data['merge_templates_togo'] + $data['merge_css_togo'];
		}
												
		return intval( $return );
	}

	/**
	 * Return the total number of master template bits
	 *
	 * @access	public
	 * @return	int			Number of bits
	 */
	public function fetchNumberTemplateBits()
	{
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
												  'from'   => 'skin_templates',
												  'where'  => 'template_set_id=0' ) );
												
		return intval( $count['count'] );
	}
}
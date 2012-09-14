<?php

/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Editor Library: RTE (WYSIWYG) Class
 * Last Updated: $Date: 2012-06-06 12:23:15 -0400 (Wed, 06 Jun 2012) $
 * </pre>
 *
 * @author 		$Author: AndyMillne $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		9th March 2005 11:03
 * @version		$Revision: 10877 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * It's surprisingly difficult to keep posts consistent through various processes.
 * I'll keep some notes here for future me or future developers woring on this.
 * 
 * The text that is passed off in "process" must have HTML entities made safe, any
 * non char set characters converted into their numeric equivalent &#xxx; make
 * every effort to not auto-parse typed in entities. That is, if someone types in &#39; it
 * should NOT convert that to ' when they hit submit.
 * Test input: < > ' " & ¦ $ \ ! &amp; &#39; &quot; &lt; &gt; &para;
 * Should be processed as: &lt; &gt; &#39; &quot; &amp; ¦ $ &#92; ! &amp;amp; &amp;#39; &amp;quot; &amp;lt; &amp;gt; &amp;para;
 * This is *the* golden rule. Use this to test any fixes. It must work in the following modes:
 * RTE > STD Switch
 * STD > RTE Switch
 * RTE > Preview
 * STD > Preview
 * RTE > Edit
 * STD > Edit
 * RTE > Edit > More options
 * STD > Edit > More options
 * RTE > Quote from post
 * STD > Quote from post
 * 
 * Please test all these modes when you have finished modifying this class
 */
/**
 * 
 * IP.Board PHP work to display an editor
 * @author Matt
 *
 * Example:
 * $editor = new classes_editor_composite();
 * $editor->setAllowBbcode( true );
 * $editor->setAllowHtml( false );
 * $editor->setContent( '[b]Hello, I am some text from the database[/b]' );
 * $html = $editor->show('post_content');
 */
class classes_editor_composite
{
	/**
	 * Use P for line breaks (std CKEditor mode)
	 */
	const IPS_P_MODE = true;
	
	/**
	 * Parsing array
	 *
	 * @access	public
	 * @var		array
	 */
	public $delimiters			= array( "'", '"' );
	
	/**
	 * Parsing array
	 *
	 * @access	public
	 * @var		array
	 */
	public $non_delimiters		= array( "=", ' ' );
	
	/**
	 * Start tags
	 *
	 * @access	public
	 * @var		array
	 */
	public $start_tags			= array();
	
	/**
	 * End tags
	 *
	 * @access	public
	 * @var		array
	 */
	public $end_tags			= array();
	
	/**#@+
	* Internal setting objects
	*
	* @var		boolean
	*/
	protected $isHtml			= null;
	protected $allowHtml		= null;
	protected $allowBbcode		= true;
	protected $content			= null;
	protected $rteEnabled		= false;
	protected $allowSmilies		= true;
	protected $_edCount			= 1;
	/**#@+
	* Registry objects
	*
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
	
	protected $_isSwitchingFrom = null;
	/**
	 * Main font sizes
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $font_sizes     = array( 1 => 8,
									   2 => 10,
									   3 => 12,
									   4 => 14,
									   5 => 18,
									   6 => 24,
									   7 => 36,
									   8 => 48 );

	
	/**
	 * Constructor
	 *
	 * @return	@e void
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry   =  ipsRegistry::instance();
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang	      =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		define( 'IPS_EDITOR_NO_SANITIZE', true );
		define( 'IPS_MAKE_AMP_SAFE'     , true );
		define( 'IPS_QUICK_EDIT_TO_FULL', ( $this->request['_from'] == 'quickedit' ) ? true : false );
		
		/* Auto set the RTE */
		$this->setRteEnabled( $this->_canWeRte() );
		
		/* Set up default options */
		$this->setAllowBbcode( true );
		$this->setAllowSmilies( true );
		$this->setAllowHtml( false );
		
		foreach( $this->font_sizes as $bbcode => $real )
		{
			$this->rev_font_sizes[ $real ] = $bbcode;
		}
	}	
	
	/**
	 * @return the $isHtml
	 */
	public function getIsHtml()
	{
		return $this->isHtml;
	}
	
	/**
	 * @return the $isHtml
	 */
	public function setIsHtml( $isHtml )
	{
		$this->isHtml = $isHtml ? true : false;
		
		if ( $isHtml )
		{
			$this->setAllowHtml( true );
		}
	}
	
	/**
	 * @return the $allowSmilies
	 */
	public function getAllowSmilies()
	{
		return $this->allowSmilies;
	}

	/**
	 * @return the $allowHtml
	 */
	public function getAllowHtml()
	{
		return $this->allowHtml;
	}

	/**
	 * @return the $allowBbcode
	 */
	public function getAllowBbcode()
	{
		return $this->allowBbcode;
	}

	/**
	 * @return the $content
	 */
	public function getContent()
	{
		return $this->content;
	}
	
	/**
	 * @return the $rteEnabled
	 */
	public function getRteEnabled()
	{
		return $this->rteEnabled;
	}
	
	/**
	 * @param boolean $allowHtml
	 */
	public function setAllowHtml( $allowHtml )
	{
		/* No mechanics in the mobile skin to deal with this */
		if ( $this->registry->output->getAsMobileSkin() )
		{
			$allowHtml = false;
		}
		
		$this->allowHtml = $allowHtml ? true : false;
	}

	/**
	 * @param boolean $allowBbcode
	 */
	public function setAllowBbcode( $allowBbcode )
	{
		$this->allowBbcode = $allowBbcode ? true : false;
	}

	/**
	 * @param string $content
	 * @param string BBCode parsing section
	 */
	public function setContent( $content, $section='topics' )
	{
		$this->content = $this->convertContent( $content, 'auto', $section );
	}
	
	/**
	 * Takes raw content and makes it good for RTE or STD editor
	 * 
	 * @param string $content
	 * @param string auto = detect, rte = CKEditor, std = textarea
	 * @param string BBCode parsing section
	 */
	public function convertContent( $content, $rteMethod='auto', $section='topics' )
	{
		/* start */
		$isRte = true;
		
		/* Return if empty */
		if ( ! $content )
		{
			return $content;
		}
		
		/* Forcing HTML, forces STD */
		if ( $this->getIsHtml() && ! IN_ACP )
		{
			$rteMethod = 'std';
		}
		
		/* Fix method */
		switch ( $rteMethod )
		{
			case 'auto':
				$isRte = ( ( $this->contentIsRte( $content ) || $this->getRteEnabled() ) && ! $this->_isInSourceMode() ) ? true : false;		
			break;
			case 'rte':
				$isRte = true;
			break;
			case 'std':
				$isRte = false;
			break;
		}
		
		/* Setup our parser class partially here */
		IPSText::getTextClass('bbcode')->parse_smilies		= $this->getAllowSmilies();
		IPSText::getTextClass('bbcode')->parse_bbcode		= $this->getAllowBbcode();
		IPSText::getTextClass('bbcode')->parsing_section	= $section;
		
		/* RTE needs a code tidy whereas STD needs BBCode */
		if ( $isRte )
		{
			/* More parser setup #1 */
			IPSText::getTextClass('bbcode')->parse_html = 0;
			
			if ( $this->getAllowHtml() )
			{
				$content = IPSText::UNhtmlspecialchars( $content );
			}
			else
			{
				$content = $this->_rtePreShow( IPSText::getTextClass('bbcode')->convertForRTE( $content ), true );
			}
		}
		else
		{
			/* More parser setup #2 */
			IPSText::getTextClass('bbcode')->parse_html  = $this->getAllowHtml();
			IPSText::getTextClass('bbcode')->parse_nl2br = 0;
			
			if ( IPS_QUICK_EDIT_TO_FULL )
			{
				$content = str_replace( '&', '&amp;', $content );
				$content = preg_replace( '#<br([^>])?>#', '&lt;br\1&gt;', $content );
			}
			
			if ( IN_ACP && $this->getIsHtml() )
			{
				/* Test to see if we need to convert */
				if ( strstr( $content, '[/' ) )
				{
					$content = str_replace( '<br />', "<br />\n", IPSText::getTextClass('bbcode')->convertForRTE( $content ) );
				}
				
				$content = $this->_stdPreShow( $content );
			}
			else
			{
				$content = $this->_stdPreShow( IPSText::getTextClass('bbcode')->preEditParse( $content ) );
			}
			
			/* This is designed only for new STD forms with content and NOT when switching modes */
			if ( ! IPS_IS_AJAX )
			{
				$content = str_replace( '&', '&amp;', $content );
			}
			else
			{
				$content = str_replace( '&quot;', '"', $content );
			}
		}
		
		$content = $this->_convertNumericEntityToNamed( $content );
		$content = $this->_allowNonUtf8CharsWhenNotUsingUtf8Doc( $content );
		
		return $content;
	}

	/**
	 * @param boolean $rteEnabled
	 */
	public function setRteEnabled( $rteEnabled )
	{
		$this->rteEnabled = $rteEnabled ? true : false;
	}
	
	/**
	 * @param boolean $allowSmilies
	 */
	public function setAllowSmilies( $allowSmilies )
	{
		$this->allowSmilies = $allowSmilies ? true : false;
	}

	/**
	 * Determines whether or not we can use the RTE
	 * @return	boolean
	 */
	protected function _canWeRte()
	{
		/* Sent inline */
		if ( isset( $_REQUEST['isRte'] ) && $_REQUEST['isRte'] == 1 )
		{
			return true;
		}
		
		/* Enforce bypass for mobile skin */
		if ( $this->registry->output->getAsMobileSkin() && !IN_ACP )
		{
			return false;
		}
		
		$return = FALSE;

		if ( $this->memberData['userAgentKey'] == 'explorer' AND $this->memberData['userAgentVersion'] >= 7 )
		{
			$return = TRUE;
		}
		else if ( $this->memberData['userAgentKey'] == 'opera' AND $this->memberData['userAgentVersion'] >= 9.00 )
		{
			$return = TRUE;
		}
		else if ( $this->memberData['userAgentKey'] == 'firefox' AND $this->memberData['userAgentVersion'] >= 3 )
		{
			$return = TRUE;
		}
		else if ( $this->memberData['userAgentKey'] == 'safari' AND $this->memberData['userAgentVersion'] >= 4 )
		{
			$return = TRUE;
		}
		else if ( $this->memberData['userAgentKey'] == 'chrome' AND $this->memberData['userAgentVersion'] >= 2 )
		{
			$return = TRUE;
		}
		else if ( $this->memberData['userAgentKey'] == 'camino' AND $this->memberData['userAgentVersion'] >= 2 )
		{
			$return = TRUE;
		}
		else if ( $this->memberData['userAgentKey'] == 'mozilla' AND $this->memberData['userAgentVersion'] >= 4 )
		{
			$return = TRUE;
		}
		else if ( $this->memberData['userAgentKey'] == 'aol' AND $this->memberData['userAgentVersion'] >= 9 )
		{
			$return = TRUE;
		}
		
		/* No iDevice */
		if ( $this->registry->output->isLargeTouchDevice() || $this->registry->output->isSmallTouchDevice() )
		{
			$return = FALSE;
		}
		
		return $return;
	}
	
	/**
	 * Have we specifically set our CKEditor to saucy mode?
	 * @return boolean
	 */
	protected function _isInSourceMode()
	{
		/* Forced into HTML mode? */
		if ( $this->getIsHtml() )
		{
			return true;
		}
		
		/* If we're using the mobile skin, then force STD */
		if ( $this->registry->getClass('output')->getAsMobileSkin() )
		{
			return true;
		}
		
		/* Sent inline */
		if ( isset( $_REQUEST['isRte'] ) )
		{
			return ( $_REQUEST['isRte'] == 0 ) ? true : false;
		}
		
		/* Check the mode */
		if ( IPSCookie::get('rteStatus') == 'std' )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Shows the editor
	 * print $editor->show( 'message', 'reply-topic-1244' );
	 * @param	string	Field
	 * @param	array   Options: Auto save key, a unique key for the page. If supplied, editor will auto-save at regular intervals. Works for logged in members only
	 * @param	string	Optional content
	 */
	public function show( $fieldName, $options=array(), $content='' )
	{
		/* Have we forced RTE? */
		if ( ! empty( $this->request['isRte'] ) )
		{
			$options['isRte'] = intval( $this->request['isRte'] );
		}
		
		$_autoSaveKeyOrig		     = ( ! empty( $options['autoSaveKey'] ) ) ? $options['autoSaveKey'] : '';
		$options['editorName']       = ( ! empty( $options['editorName'] ) ) ? $options['editorName'] : $this->_fetchEditorName();
		$options['autoSaveKey']      = ( $_autoSaveKeyOrig && $this->memberData['member_id'] ) ? $this->_generateAutoSaveKey( $_autoSaveKeyOrig ) : '';
		$options['type']             = ( ! empty( $options['type'] ) && $options['type'] == 'mini' ) ? 'mini' : 'full';
		$options['minimize']	     = intval( $options['minimize'] );
		$options['height']	     	 = intval( $options['height'] );
		$options['isTypingCallBack'] = ( ! empty( $options['isTypingCallBack'] ) ) ? $options['isTypingCallBack'] : '';
		$options['noSmilies']		 = ( ! empty( $options['noSmilies'] ) ) ? true : false;
		$options['delayInit']		 = ( ! empty( $options['delayInit'] ) ) ? 1 : 0;
		$options['smilies']          = $this->fetchEmoticons( 20 );
		$options['bypassCKEditor']   = $this->getRteEnabled() ? 0 : 1;
		$options['isRte']			 = ( isset( $options['isRte'] ) ) ? $options['isRte'] : ( $this->_isInSourceMode() ? 0 : 1 );
		$html         = '';
		
		/* If we're using the mobile skin, then force STD */
		if ( $this->registry->getClass('output')->getAsMobileSkin() )
		{
			$options['isRte'] = 0;
		}
		
		if ( isset( $options['recover'] ) )
		{
			$content = $_POST['Post'];
		}

		if ( ! empty( $options['isHtml'] ) )
		{
			$this->setIsHtml( true );
			
			if ( IN_ACP )
			{
				$options['type'] = 'ipsacp';
			}
		}
		else if ( $this->getIsHtml() )
		{
			$options['isHtml'] = 1;
		}
		
		/* inline content */
		if ( $content )
		{
			$this->setContent( str_replace( '\\\'', '\'', $content ) );
		}
	
		/* Store last editor ID in case calling scripts need it */
		$this->settings['_lastEditorId']	= $options['editorName'];
		
		if ( IN_ACP )
  		{ 
  			$html = $this->registry->getClass('output')->global_template->editor( $fieldName, $this->getContent(), $options, $this->getAutoSavedContent( $_autoSaveKeyOrig ) );
		}
		else
		{
			$warningInfo = '';
			$acknowledge = FALSE;
			
			//-----------------------------------------
			// Warnings
			//-----------------------------------------
			
			if ( isset( $options['warnInfo'] ) and $this->memberData['member_id'] )
			{
				$message = '';
				
				/* Have they been restricted from posting? */
				if ( $this->memberData['restrict_post'] )
				{
					$data = IPSMember::processBanEntry( $this->memberData['restrict_post'] );
					if ( $data['date_end'] )
					{
						if ( time() >= $data['date_end'] )
						{
							IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
						}
						else
						{
							$message = sprintf( $this->lang->words['warnings_restrict_post_temp'], $this->lang->getDate( $data['date_end'], 'JOINED' ) );
						}
					}
					else
					{
						$message = $this->lang->words['warnings_restrict_post_perm'];
					}
					
					if ( $this->memberData['unacknowledged_warnings'] )
					{
						$warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_logs', 'where' => "wl_member={$this->memberData['member_id']} AND wl_rpa<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
						if ( $warn['wl_id'] )
						{
							$moredetails = "<a href='javascript:void(0);' onclick='warningPopup( this, {$warn['wl_id']} )'>{$this->lang->words['warnings_moreinfo']}</a>";
						}
					}
					
					if ( $options['warnInfo'] == 'full' )
					{
						$this->registry->getClass('output')->showError( "{$message} {$moredetails}", 103126, null, null, 403 );
					}
				}
				
				/* Nope? - Requires a new if in case time restriction got just removed */
				if ( empty($message) )
				{
					/* Do they have any warnings they have to acknowledge? */
					if ( $this->memberData['unacknowledged_warnings'] )
					{
						$unAcknowledgedWarns = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_logs', 'where' => "wl_member={$this->memberData['member_id']} AND wl_acknowledged=0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
						if ( $unAcknowledgedWarns['wl_id'] )
						{
							if ( $options['warnInfo'] == 'full' )
							{
								$this->registry->getClass('output')->silentRedirect( $this->registry->getClass('output')->buildUrl( "app=members&amp;module=profile&amp;section=warnings&amp;do=acknowledge&amp;id={$unAcknowledgedWarns['wl_id']}" ) );
							}
							else
							{
								$this->lang->loadLanguageFile( 'public_profile', 'members' );
								$acknowledge = $unAcknowledgedWarns['wl_id'];
							}
						}
					}
					
					/* No? Are they on mod queue? */
					if ( $this->memberData['mod_posts'] )
					{
						$data = IPSMember::processBanEntry( $this->memberData['mod_posts'] );
						if ( $data['date_end'] )
						{
							if ( time() >= $data['date_end'] )
							{
								IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'mod_posts' => 0 ) ) );
							}
							else
							{
								$message = sprintf( $this->lang->words['warnings_modqueue_temp'], $this->lang->getDate( $data['date_end'], 'JOINED' ) );
							}
						}
						else
						{
							$message = $this->lang->words['warnings_modqueue_perm'];
						}
						
						if ( $message )
						{
							$warn = ipsRegistry::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'members_warn_logs', 'where' => "wl_member={$this->memberData['member_id']} AND wl_mq<>0", 'order' => 'wl_date DESC', 'limit' => 1 ) );
							if ( $warn['wl_id'] )
							{
								$moredetails = "<a href='javascript:void(0);' onclick='warningPopup( this, {$warn['wl_id']} )'>{$this->lang->words['warnings_moreinfo']}</a>";
							}
						}
					}
					
					/* How about our group? - Requires a new if in case mod queue restriction got just removed */
					if ( empty($message) && $this->memberData['g_mod_preview'] )
					{
						/* Do we only limit for x posts/days? */
						if ( $this->memberData['g_mod_post_unit'] )
						{
							if ( $this->memberData['gbw_mod_post_unit_type'] )
							{
								/* Days.. .*/
								if ( $this->memberData['joined'] > ( time() - ( 86400 * $this->memberData['g_mod_post_unit'] ) ) )
								{
									$message = sprintf( $this->lang->words['ms_mod_q'] . ' ' . $this->lang->words['ms_mod_q_until'], $this->lang->getDate( $this->memberData['joined'] + ( 86400 * $this->memberData['g_mod_post_unit'] ), 'long' ) );
								}
							}
							else
							{
								/* Posts */
								if ( $this->memberData['posts'] < $this->memberData['g_mod_post_unit'] )
								{
									$message = sprintf( $this->lang->words['ms_mod_q'] . ' ' . $this->lang->words['ms_mod_q_until_posts'], $this->memberData['g_mod_post_unit'] - $this->memberData['posts'] );
								}
							}
						}
						else
						{
							/* No limit, but still checking moderating */
							$message = $this->lang->words['ms_mod_q'];
						}
					}
					/* Or just everyone? */
					elseif ( $options['modAll'] and !$this->memberData['g_avoid_q'] )
					{
						$message = $this->lang->words['ms_mod_q'];
					}
					
					if ( $message )
					{
						$warningInfo = "{$message} {$moredetails}";
					}
				}
			}
			
			//-----------------------------------------
			// Show the editor
			//-----------------------------------------
		
			$content = ( IPS_IS_AJAX ) ? str_replace( '&', '&amp;', $this->getContent() ) : $this->getContent();
			
			$html = $this->registry->getClass('output')->getTemplate('editors')->editor( $fieldName, $content, $options, $this->getAutoSavedContent( $_autoSaveKeyOrig ), $warningInfo, $acknowledge );
		}
		
		return $html;
	}
	
	/**
	 * Process contents of RTE into BBCode ready for storing
	 * @param  string	$content
	 * @return string	$content
	 */
	public function process( $content )
	{
		//header("Content-type: text/plain");
		return ( ( $this->contentIsRte( $content ) || $this->getRteEnabled() ) && ! $this->_isInSourceMode() ) ? $this->_rteProcess( $content ) : $this->_stdProcess( $content, false );
		//exit();
	}
	
	/**
	 * Fetches the saved content
	 * @param string $autoSaveKey
	 * @return array
	 */
	public function getAutoSavedContent( $autoSaveKey )
	{
		$autoSaveKey = $this->_generateAutoSaveKey( $autoSaveKey );
		$return      = array();
		/* fetch from the dee bee */
		$raw = $this->DB->buildAndFetch( array( 'select' => '*',
												'from'   => 'core_editor_autosave',
												'where'  => 'eas_key=\'' . $autoSaveKey . '\'' ) );
		
		/* Make sure no tomfoolery is occuring */
		if ( $raw['eas_key'] && ( $this->memberData['member_id'] == $raw['eas_member_id'] ) )
		{
			$return['key']         = $raw['eas_key'];
			$return['updated']     = $raw['eas_updated'];
			$return['raw']         = $raw['eas_content'];
			$return['restore_rte'] = $this->convertContent( nl2br($return['raw']), 'rte' );
			$return['restore_std'] = $this->convertContent( nl2br($return['raw']), 'std' );
			$return['updatedDate'] = $this->registry->getClass('class_localization')->getDate( $return['updated'], 'LONG' );
			
			/* Now figure out previewable content */
			$return['parsed']  = IPSText::getTextClass('bbcode')->preDisplayParse( IPSText::getTextClass('bbcode')->preDbParse( $this->_rteProcess( $raw['eas_content'] ) ) );
		}
		
		return $return;
	}
	
	/**
	 * Remove auto saved content
	 * @param array $where options member_id = x , app = x, time = x 
	 */
	public function removeAutoSavedContent( $where=array() )
	{
		$_sql = array();
		
		if ( ! count( $where ) )
		{
			$_sql[] = 'eas_app=\'' . IPS_APP_COMPONENT . '\'';
		}
		
		if ( ! empty( $where['app'] ) )
		{
			$_sql[] = 'eas_app=\'' . $this->DB->addSlashes( $where['app'] ) . '\'';
		}
		
		if ( ! empty( $where['member_id'] ) )
		{
			$_sql[] = 'eas_member_id=' . intval( $where['member_id'] );
		}
		
		if ( ! empty( $where['autoSaveKey'] ) )
		{
			if ( strlen( $where['autoSaveKey'] ) != 32 )
			{
				$where['autoSaveKey'] = $this->_generateAutoSaveKey( $where['autoSaveKey'] );
			}
			
			$_sql[] = 'eas_key=\'' . trim( $where['autoSaveKey'] ) . '\'';
		}
		
		if ( ! empty( $where['time'] ) )
		{
			$_sql[] = 'eas_updated < ' . intval( $where['time'] );
		}
		
		$this->DB->delete( 'core_editor_autosave', implode( ' AND ', $_sql ) );
	}
	
	/**
	 * Sniff out RTE content
	 * @param string $content
	 * @return boolean
	 */
	public function contentIsRte( $content )
	{
		$content = trim( $content );
		
		if ( substr( $content, 0, 3 ) == '<p>' && substr( $content, -4 ) == '</p>' )
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * Auto save via ajax
	 * @param string $content
	 * @param string $autoSaveKey
	 */
	public function autoSave( $content, $autoSaveKey )
	{
		/* Convert the data so it is safe to store and preview */
		$content = IPSText::getTextClass('bbcode')->preEditParse( $this->_rteProcess( $content, IPS_EDITOR_NO_SANITIZE ) );
		
		/* Pretty much just dump it in the DB */
		$this->DB->replace( 'core_editor_autosave', array( 'eas_key'       => $autoSaveKey,
														   'eas_member_id' => $this->memberData['member_id'],
														   'eas_app'	   => IPS_APP_COMPONENT,
														   'eas_section'   => $this->request['module'] . '.' . $this->request['section'],
														   'eas_updated'   => time(),
														   'eas_content'   => $content ), array( 'eas_key' ) );
		
		return true;
	}
	
	/**
	 * Switch content from one mode t'other
	 * 
	 * @param	string	Content
	 * @param	int		Is RTE?
	 */
	public function switchContent( $content, $isRte )
	{
		IPSText::getTextClass('bbcode')->parse_html			= false;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
		IPSText::getTextClass('bbcode')->parse_smilies		= ( ! empty( $this->request['noSmilies'] ) ) ? false : true;
		IPSText::getTextClass('bbcode')->parse_bbcode		= true;
		IPSText::getTextClass('bbcode')->parsing_section	= 'topics';
		
		/* Default state for ACP */
		if ( IN_ACP && $this->getAllowHtml() === null )
		{
			IPSText::getTextClass('bbcode')->parse_html = true;
		}
				
		if ( $isRte )
		{
			$this->_isSwitchingFrom = 'std';
			/* Assume RTE text has been sent in and we want BBCode */
			$content = str_replace( '\\', '&#92;', $content ); # Makes manually entered < safe
			
			if ( ! $this->getAllowHtml() )
			{
				//IPSDebug::addLogMessage( $content, 'stdeditor', $_POST, true, true );
				$content = $this->_rteProcess( $content, IPS_EDITOR_NO_SANITIZE );
				//IPSDebug::addLogMessage( $content, 'stdeditor', false, true, false );
				$content = IPSText::getTextClass('bbcode')->preEditParse( $content );
				//IPSDebug::addLogMessage( $content, 'stdeditor', false, true, false );
				$content = $this->_stdPreShow( $content );
				//IPSDebug::addLogMessage( $content, 'stdeditor', false, true, false );
			}
			
			$content = str_replace( '&#92;', '\\', $content );
		}
		else
		{
			$this->_isSwitchingFrom = 'rte';
			/* Assume BBCode has been sent in and we want RTE */
			
			if ( ! $this->getAllowHtml() )
			{
				//IPSDebug::addLogMessage( $content, 'editor', $_POST, true, true );
				
				$content = $this->_stdProcess( $content, IPS_EDITOR_NO_SANITIZE );
				//IPSDebug::addLogMessage( $content, 'editor', false, true, false );
				$content = IPSText::getTextClass('bbcode')->convertForRTE( $content );
				//IPSDebug::addLogMessage( $content, 'editor', false, true, false );
				$content = $this->_rtePreShow( $content, false, true );
				//IPSDebug::addLogMessage( $content, 'editor', false, true, false );
			}
		}
		
		$this->_isSwitchingFrom = null;
		
		return $content;
	}
	
	/**
	 * Processes text ready for non RTE
	 *
	 * @access	public
	 * @param	string		Raw text
	 * @return	string		Text ready for editor
	 */
	public function _stdPreShow( $t )
	{
		$t = str_replace( array( "\r\n", "\r" ), "\n", $t );
		
		IPSDebug::fireBug( 'info', array( 'Start (std): ' . nl2br($t) ) );
		
		$t = IPSText::decodeNamedHtmlEntities( $t );

		$t = str_replace( '&#33;', '!', $t );
		$t = str_replace( '&#34;', '"', $t );
		$t = str_replace( '&quot;', '"', $t );
		$t = str_replace( '&#60;', '<', $t );
		$t = str_replace( '&#62;', '>', $t );
		$t = str_replace( '&lt;', '<', $t );
		$t = str_replace( '&gt;', '>', $t );
		$t = str_replace( '&#39;', "'", $t );
		$t = str_replace( '&#036;', "$", $t );
		$t = str_replace( '&#36;', "$", $t );
		$t = str_replace( '&#92;', '\\', $t );
		$t = str_replace( '&#092;', '\\', $t );
		$t = str_replace( '&#160;', ' ', $t );
		$t = str_replace( '&#91;', '[', $t );
		$t = str_replace( '&#93;', ']', $t );
		$t = str_replace( '&#123;', '{', $t );
		$t = str_replace( '&#125;', '}', $t );
		$t = str_replace( '&nbsp;', ' ', $t );
		$t = str_replace( "\t"    , '    ', $t );

		
		/* We replace \ to 092 so convert it back */
		$t = str_replace( '&amp;#092;', '\\', $t );
		
		if ( ! IPS_IS_AJAX )
		{
			$t = str_replace( '&amp;', '&', $t );
		}
		
		$t = str_replace( '</textarea', '&lt;textarea', $t );	
		$t = str_replace( '<script'   , '&lt;script'  , $t );
		
		
		$t = $this->_allowNonUtf8CharsWhenNotUsingUtf8Doc( $t );
		
		/* Sect breaks on std editor @link http://community.invisionpower.com/tracker/issue-37498-gets-converted-into-sect%3B-when-editing-a-post/ */
		$t = $this->_convertNumericEntityToNamed( $t );
		
		/* We make paths safe in clean globals */
		$t = str_replace( '&#46;&#46;/', '../', $t );
		
		IPSDebug::fireBug( 'info', array( 'End (std): ' . nl2br($t) ) );

		return $t;
	}
	
	/**
	 * Processes text for RTE
	 *
	 * @access	public
	 * @param	string		Raw text
	 * @param	bool		Double encode entities (not needed for switching, only for full editor)
	 * @param	bool		Ajax request
	 * @return	string		Text ready for editor
	 */
	public function _rtePreShow( $t, $encodeEntities=false )
	{
		IPSDebug::fireBug( 'info', array( 'Start (rte): ' . nl2br($t) ) );
		/* Trim and remove comments */
		$t = preg_replace( '#\<\!\-\-(.+?)\-\-\>#is', "", $t );
		$t = rtrim($t);
		
		/* Parsing can kill img URLs as they are made 'safe' to not parse */
		$t = str_replace( 'http&#58;', 'http:', $t );
		
		/* Convert single/double quotes */
		if ( strtolower(IPS_DOC_CHAR_SET) != 'utf-8' )
		{
			$t = str_replace(chr(145), chr(39), $t);
			$t = str_replace(chr(146), chr(39), $t);
			$t = str_replace(chr(147), chr(34), $t);
			$t = str_replace(chr(148), chr(34), $t);
		}

		$t = str_replace( "\t", "{'tab'}", $t );

		/* Remove CR/LF */
		$t = str_replace(chr(10), "", $t);
		$t = str_replace(chr(13), "", $t);

		/* Fix some entities to display correctly */
		$t	= str_replace( array( '<br />', '<br>' ), '__~~--__', $t );
		
		if ( $encodeEntities )
		{
			$t	= str_replace( '&#60;', '-<-', $t );
			$t	= str_replace( '&#62;', '->-', $t );
		}
		else
		{
			$t	= str_replace( array( '&#60;', '&lt;' ), '-<-', $t );
			$t	= str_replace( array( '&#62;', '&gt;' ), '->-', $t );
		}

		$t	= str_replace( '&#10;', '<br />', $t );
		
		/* AJAX content is inserted into editor via insertText which does not parse entities as they would normally display */
		if ( ! IPS_IS_AJAX )
		{
			$t	= str_replace( '&', '&amp;', $t );
		}
			
		$t	= str_replace( '-<-', '&lt;', $t );
		$t	= str_replace( '->-', '&gt;', $t );
		$t	= str_replace( '__~~--__', '<br />', $t );

		/* Clean up quote tags (remove many <br />s */
		$t = preg_replace( '#(\[quote([^\]]+?)\])(<br />){1,}#is', "\\1<br />", $t );
		
		/* Replace tabs */	
		$t = str_replace( "{'tab'}", "&nbsp;&nbsp;&nbsp;&nbsp;", $t );
		
		/* Clean up the rest of the tags */
		//$t = str_replace( array( '&lt;br&gt;', '&lt;br /&gt;' ), '<br />', IPSText::htmlspecialchars( $t ) );
		//$t = str_replace( array( '&lt;br&gt;', '&lt;br /&gt;' ), '<br />', $t );
		$t = str_replace( "&lt;#IMG_DIR#&gt;", "<#IMG_DIR#>", $t );
		$t = str_replace( "&lt;#EMO_DIR#&gt;", "<#EMO_DIR#>", $t );
		
		/* Convert multiple spaces (Causes more issues than it fixes) */
		//$t = str_replace( "  ", "&nbsp;&nbsp;", $t );
		
		/* Fix up lists */
		$t = str_replace( '<br /></li>', '</li>', $t );
		
		/* make it look a bit pretty */
		$t = str_replace( '<br />', "<br />\n", $t );
		
		/* CK needs this - legacy data only, new stuff uses proper tags */
		if ( stristr( $t, '<b>' ) )
		{
			$t = str_replace( '<b>' , '<strong>', $t );
			$t = str_replace( '</b>', '</strong>', $t );
		}
		
		if ( stristr( $t, '<i>' ) )
		{
			$t = str_replace( '<i>' , '<em>', $t );
			$t = str_replace( '</i>', '</em>', $t );
		}
		
		/* Wrap in P tags */
		if ( self::IPS_P_MODE )
		{
			if ( $t AND ( ! preg_match( '#^<(p)#', $t ) ) )
			{
				$t = $this->_convertBrToWrappedP( $t );
				
				$t = preg_replace( '#<p>(\s+?)?<ul#is', '<ul', $t );
				$t = preg_replace( '#</ul>(\s+?)?</p>#is', '</ul>', $t );
			}
		}
		
		/* If the last item is an image, this causes issues in IE8 */
		if ( $this->memberData['userAgentKey'] == 'explorer' )
		{
			$test = str_replace( "<#EMO_DIR#>", '', $t );
			
			if ( preg_match( '#(<img(?:[^>]+?)/>)</p>$#', $test ) )
			{
				$t = preg_replace( '#</p>$#', '<span>&nbsp;</span></p>', $t );
			}
		}
		
		IPSDebug::fireBug( 'info', array( 'End (rte): ' . $t ) );
		
		if ( $this->getAllowHtml() )
		{
			$t = str_replace( '&amp;', '&', $t );
			$t = str_replace( '&gt;', '>', $t );
			$t = str_replace( '&lt;', '<', $t );
			$t = str_replace( '&nbsp;', ' ', $t );
		}
		
		return $t;
	}
	
	/**
	 * Convert <br /> tagged data to <p>
	 * @link http://www.php.net/manual/en/function.nl2br.php#97643
	 * @author James bandit.co dot nz
	 * @param string $string
	 */
	protected function _convertBrToWrappedP( $string )
	{
		return '<p>' . $string . '</p>';

		/*return  '<p>' 
            	. preg_replace('#(<br\s*?/?>\s*?){2,}#', '</p>'."\n".'<p>', $string ) 
            	. '</p>'; */
    } 
	
	/**
	 * Take content from STD and make safe
	 * @param string $content
	 * @return string
	 */
	protected function _stdProcess( $content, $NO_SANITIZE=false )
	{
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		
		$content = preg_replace( '#^([ ]+?)\n#m', "\n", $content );
		
		/* We make paths safe in clean globals */
		$content = str_replace( '&#46;&#46;/', '../', $content );
		
		$content = trim( $this->_clean( $content, $NO_SANITIZE, false ) );
	
		return $content;
	}
	
	/**
	 * Process the content before passing off to the bbcode library
	 *
	 * @access	public
	 * @param	string		Form field name OR Raw text
	 * @param	boolean		Does not run _clean() to santize HTML. Used when 'switching' editors via ajax
	 * @return	string		Text ready for editor
	 */
	protected function _rteProcess( $content, $NO_SANITIZE=false )
	{
		//-----------------------------------------
		// Save some processing if no content
		//-----------------------------------------
		
		if ( ! $content )
		{
			return $content;
		}
		
		$ot	= $content;
		
		$content = str_replace( array( "\r\n", "\n" ), "\n", $content );
		
		/* Using P mode? CKEditor will send data like so
		 * <p>test</p>
		 * <p>More data</p>
		 * <p><br /></p>
		 */
		if ( self::IPS_P_MODE && ! $this->getAllowHtml() )
		{
			/* Tidy up first */
			$content = str_replace( '<div' , '<p' , $content );
			$content = str_replace( '</div>', '</p>', $content );
			$content = preg_replace( '#<p>(\s+?)?<ul#is', '<ul', $content );
			$content = preg_replace( '#(\r\n|\r|\n)<p#is', '<p', $content );
			//$content = preg_replace( '#(\r\n|\r|\n)<p([^>]+?)?'.'>&nbsp;</p>#is', "<br />", $content );
			$content = preg_replace( '#</ul>(\s+?)?(<br([^>]+?)?>|</p>)#is', '</ul>', $content );
		}
		
		/* Before we content/strip newlines, lets make code safe */
		$content = $this->_recurseAndParse( 'pre', $content, "_parsePreTag" );
		
		//-----------------------------------------
		// Fix up tabs/spaces
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content = str_replace( '&nbsp;&nbsp;&nbsp;&nbsp;', "{'tab'}", $content );
			
			$content = str_replace( "\t", "{'tab'}", $content );
			$content = str_replace( '&nbsp;', ' ', $content );
			
			/* looks to be non RTE content that has ended up there */
			if ( ! strstr( $content, '<br' ) && ! strstr( $content, '<p' ) && strstr( $content, "\n" ) )
			{
				$content = str_replace( "\n", "<br />", $content );
			}
			else
			{
				$content = str_replace( "\n", "", $content );
			}
		}
		
		//-----------------------------------------
		// Clean up already encoded HTML
		//-----------------------------------------
		
		$content = str_replace( '&quot;', '"', $content );
		$content = str_replace( '&apos;', "'", $content );
		
		//-----------------------------------------
		// Fix up incorrectly nested urls / BBcode
		//-----------------------------------------
		
		// @link	http://community.invisionpower.com/tracker/issue-24704-pasting-content-in-rte-with-image-first/
		// Revert the fix for now as it causes more issues than the original one
		$content = preg_replace( '#<a\s+?href=[\'"]([^>]+?)\[(.+?)[\'"](.+?)'.'>(.+?)\[\\2</a>#is', '<a href="\\1"\\3>\\4</a>[\\2', $content );
		//$content = preg_replace( '#<a\s+?href=[\'"]([^>\'"]+?)[\'"](.*?)>(.+?)\[([^<]+?)</a>#is', '<a href="\\1">\\3</a>[\\4', $content );

		//-----------------------------------------
		// Make URLs safe (prevent tag stripping)
		//-----------------------------------------

		$content = preg_replace_callback( '#<(a href|img src)=([\'"])([^>]+?)(\\2)#is', array( $this, '_unhtmlUrl' ), $content );

		//-----------------------------------------
		// WYSI-Weirdness #1: BR tags to \n
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content = preg_replace( '#<br([^>]+?)>#', "<br />", $content );
			$content = str_ireplace( array( "<br>", "<br />" ), "\n", $content );
		}
		
		$content = trim( $content );
		
		//-----------------------------------------
		// Before we can use strip_tags, we should
		// clean out any javascript and CSS
		//-----------------------------------------
		
		$content	= preg_replace( '/\<script(.*?)\>(.*?)\<\/script\>/', '', $content );
		$content	= preg_replace( '/\<style(.*?)\>(.*?)\<\/style\>/', '', $content );
		
		//-----------------------------------------
		// Remove tags we're not bothering with
		// with PHPs wonderful strip tags func
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content = strip_tags( $content, '<h1><h2><h3><h4><h5><h6><font><span><div><br><p><img><a><li><ol><ul><b><strong><em><i><u><s><strike><del><blockquote><sub><sup><pre>' );
		}

		//-----------------------------------------
		// WYSI-Weirdness #2: named anchors
		//-----------------------------------------
		
		$content = preg_replace( '#<a\s+?name=.+?'.'>(.+?)</a>#is', "\\1", $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #2.1: Empty a hrefs
		//-----------------------------------------
		
		$content = preg_replace( '#<a\s+?href([^>]+)></a>#is'         , ""   , $content );
		$content = preg_replace( '#<a\s+?href=([\'\"])>\\1(.+?)</a>#is', "\\1", $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #2.2: Double linked links
		//-----------------------------------------
		
		$content = preg_replace( '#href=[\"\']\w+://(%27|\'|\"|&quot;)(.+?)\\1[\"\']#is', "href=\"\\2\"", $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #3: Headline tags
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content = preg_replace( "#<(h[0-9])(?:[^>]+?)?>(.+?)</\\1>#is", "\n[b]\\2[/b]\n", $content );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #4: Font tags
		//-----------------------------------------
		
		$content = preg_replace( '#<font (color|size|face)=\"([a-zA-Z0-9\s\#\-]*?)\">(\s*)</font>#is', " ", $content );

		//-----------------------------------------
		// WYSI-Weirdness #5a: Fix up smilies: IE RTE
		// @see Ticket 623146
		//-----------------------------------------
		
		$content = preg_replace( '#<img class=(\S+?) alt=(\S+?) src=[\"\'](.+?)[\"\']>#i', "<img src='\\3' class='\\1' alt='\\2' />", $content );
		$content = preg_replace( '#alt=\'[\"\'](\S+?)[\'\"]\'#i', "alt='\\1'", $content );
		$content = preg_replace( '#class=\'[\"\'](\S+?)[\'\"]\'#i', "class='\\1'", $content );
		$content = preg_replace( '#([a-zA-Z0-9])<img src=[\"\'](.+?)[\"\'] class=[\"\'](.+?)[\"\'] alt=[\"\'](.+?)[\"\'] />#i', "\\1 <img src='\\2' class='\\3' alt='\\4' />", $content );
		
		/* Remove <img src="data:"> */
		$content = preg_replace( '#<img\s+?(alt=""\s+?)?src="data:([^"]+?)"\s+?/>#', '', $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #6: Image tags
		//-----------------------------------------
		
		$content = preg_replace( '#<img alt=[\"\'][\"\'] height=[\"\']\d+?[\"\'] width=[\"\']\d+?[\"\']\s+?/>#', "", $content );
		$content = preg_replace( '#<img.+?src=[\"\'](.+?)[\"\']([^>]+?)?'.'>#is', "[img]\\1[/img]", $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #7: Linked URL tags
		//-----------------------------------------
		
		$content = preg_replace( '#\[url=(\"|\'|&quot;)<a\s+?href=[\"\'](.*)/??[\'\"]\\2/??</a>#is', "[url=\\1\\2", $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #8: Make relative images full links
		//-----------------------------------------
		
		$content = preg_replace( '#\[img\](/)?public/style_(emoticons|images)#i', '[img]' . $this->settings['board_url'] . '/public/style_' . '\\2', $content );
	
		//-----------------------------------------
		// Clean up whitespace between lists
		//-----------------------------------------
		
		$content = preg_replace( '#<li>\s+?(\S)#', '<li>\\1', $content );
		$content = preg_replace( '#</li>\s+?(\S)#', '</li>\\1', $content );
		$content = preg_replace( '#<br />(\s+?)?</li>#si', '</li>', $content );
		
		//-----------------------------------------
		// Now, recursively parse the other tags
		// to make sure we get the nested ones
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content = $this->_recurseAndParse( 'b'			, $content, "_parseSimpleTag", 'b' );
			$content = $this->_recurseAndParse( 'u'			, $content, "_parseSimpleTag", 'u' );
			$content = $this->_recurseAndParse( 'strong'	, $content, "_parseSimpleTag", 'b' );
			$content = $this->_recurseAndParse( 'i'			, $content, "_parseSimpleTag", 'i' );
			$content = $this->_recurseAndParse( 'em'		, $content, "_parseSimpleTag", 'i' );
			$content = $this->_recurseAndParse( 'strike'	, $content, "_parseSimpleTag", 's' );
			$content = $this->_recurseAndParse( 'del'		, $content, "_parseSimpleTag", 's' );
			$content = $this->_recurseAndParse( 's'			, $content, "_parseSimpleTag", 's' );
			$content = $this->_recurseAndParse( 'blockquote', $content, "_parseSimpleTag", 'indent' );
			$content = $this->_recurseAndParse( 'sup' 		, $content, "_parseSimpleTag", 'sup' );
			$content = $this->_recurseAndParse( 'sub'		, $content, "_parseSimpleTag", 'sub' );
	
			//-----------------------------------------
			// More complex tags
			//-----------------------------------------
	
			$content = $this->_recurseAndParse( 'a'          , $content, "_parseAnchorTag" );
			$content = $this->_recurseAndParse( 'font'       , $content, "_parseFontTag" );
			$content = $this->_recurseAndParse( 'div'        , $content, "_parseDivTag" );
			$content = $this->_recurseAndParse( 'p'          , $content, "_parseParagraphTag" );
			$content = $this->_recurseAndParse( 'span'       , $content, "_parseSpanTag" );
			
			/* Possibility of preceeding \n because of P tag */
			$content = trim( $content );
	
			//-----------------------------------------
			// Lists
			//-----------------------------------------
			
			$content = $this->_recurseAndParse( 'ol'         , $content, "_parseListTag" );
			$content = $this->_recurseAndParse( 'ul'         , $content, "_parseListTag" );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #9: Fix up para tags
		//-----------------------------------------
		
		//$content = str_ireplace( array( "<p>", "<p />" ), "\n\n", $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #10: Random junk
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content = str_ireplace( array( "<a>", "</a>", "</li>" ), "", $content );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #11: Fix up list stuff
		//-----------------------------------------
		
		$content = preg_replace( '#<li>(.*)((?=<li>)|</li>)#is', '\\1', $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #11.1: Safari badness
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content	= str_replace( "</div>", "", $content );
		
			/* Sometimes, unclosed P tags remain if text is copied and pasted directly */
			$content	= preg_replace( '#<(p|div|ul|li)([^\>]+?)\>#is', '', $content );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #12: Convert rest to HTML
		//-----------------------------------------
		
		if ( ! $this->getAllowHtml() )
		{
			$content = str_replace(  '&lt;' , '<', $content );
			$content = str_replace(  '&gt;' , '>', $content );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #13: Remove useless tags
		//-----------------------------------------
		
		/* Remove embedded stuffs */
		$content = preg_replace( '#\[(center|right|left)\]\[\1\]#i', '[\1]', $content );
		$content = preg_replace( '#\[/(center|right|left)\]\[/\1\]#i', '[/\1]', $content );
				
		while( preg_match( '#\<(b|u|i|s|li)\>(\s+?)?\</\1\>#is', $content ) )
		{
			$content = preg_replace( '#\<(b|u|i|s|li)\>(\s+?)?\</\1\>#is', "", $content );
		}
		
		//-----------------------------------------
		// WYSI-Weirdness #14: Opera crap
		//-----------------------------------------
		
		$content = preg_replace( '#\[(font|size|color)\]=[\"\']([^\"\']+?)[\"\']\]\[/\\1\]#is', "", $content );
		
		//-----------------------------------------
		// WYSI-Weirdness #14.1: Safari crap
		//-----------------------------------------
		
		$content = preg_replace( '#\[(font|size|color)=&quot;([^\"\']+?)&quot;\]\[/\\1\]#is', "", $content );

		//-----------------------------------------
		// WYSI-Weirdness #15: No domain in FF?
		//-----------------------------------------	
		
		$content = preg_replace( '#(http|https):\/\/index.php(.*?)#is', $this->settings['board_url'].'/index.php\\2', $content );	
		$content = preg_replace( '#\[url=[\'\"]index.php(.*?)[\"\']#is', "[url=\"".$this->settings['board_url'].'/index.php\\1"', $content );	
		
		/* Fix up incorrect tags outside of quotes */
		$content = preg_replace( '#\[(b|u|s)\](\[quote)#'   , '\2[\1]' , $content );
		$content = preg_replace( '#(\[/quote])\[/(b|u|s)\]#', '[/\2]\1', $content );
		
		//-----------------------------------------
		// Replace tabs
		//-----------------------------------------
		#IPSDebug::addLogMessage( $content, 'editor', false, true );
		$content = str_replace( "{'tab'}", "\t", $content );
		
		//-----------------------------------------
		// Now call the santize routine to make
		// html and nasties safe. VITAL!!
		//-----------------------------------------
		
		$content = $this->_clean( trim( $content ), $NO_SANITIZE, true );

		/* Ensure [xxx=&quot; is fixed */
		$content = preg_replace( '#\[(\w+?)=&quot;(.+?)&quot;\]#', "[\\1=\"\\2\"]", $content );
		
		/* Relative paths */
		$content = preg_replace( '#\[img\](../../|&\#46;&\#46;/&\#46;&\#46;/)public/#', '[img]' . $this->settings['board_url'] . '/public/', $content );
				
		/* Finally ensure emoticons are converted to normal tags */
		if ( count( $this->cache->getCache('emoticons') ) > 0 )
		{
			$emoDir = $this->_fetchSmilieDir();
			
			foreach( $this->cache->getCache('emoticons') as $row )
			{
				if ( $row['emo_set'] != $emoDir )
				{
					continue;
				}
				
				$content = preg_replace( '#(\s)?\[img\]' . preg_quote( $this->settings['public_cdn_url'] . 'style_emoticons/' . $this->registry->output->skin['set_emo_dir'] . '/' . $row['image'], '#' ) . '\[/img\]#',
										 ' ' . $row['typed'], $content ); 
				
			}
		}
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		if ( $this->debug )
		{
			print "<pre><hr>";
			print nl2br(htmlspecialchars($ot));
			print "<hr>";
			print nl2br($content);
			print "<hr>";
			exit();
		}
		
		//-----------------------------------------
		// Done
		//-----------------------------------------
		
		return $content;
	}

	/**
	 * RTE: Parse List tag
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseListTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$list_type = trim( preg_replace( '#"?list-style-type:\s+?([\d\w\_\-]+);?"?#si', '\\1', $this->_getValueOfOption( 'style', $opening_tag ) ) );
		$css       = $this->_getValueOfOption( 'class', $opening_tag );
		
		//-----------------------------------------
		// Set up a default...
		//-----------------------------------------
		
		if ( ( stristr( $css, ' decimal' ) ) OR ! $list_type and $tag == 'ol' )
		{
			$list_type = 'decimal';
		}
		
		//-----------------------------------------
		// Tricky regex to clean all list items
		//-----------------------------------------
		
		$between_text = preg_replace( '#<li([^\>]+?)\>#is', '<li>', $between_text );
		$between_text = preg_replace( '#<li>\s+?</li>#is', '', $between_text );
		$between_text = preg_replace('#<li>((.(?!</li))*)(?=</?ul|</?ol|\[list|<li|\[/list)#siU', '<li>\\1</li>', $between_text);

		$between_text = trim( $this->_recurseAndParse( 'li', $between_text, "_parseListElement" ) );
		
		$allowed_types = array( 'upper-alpha' => 'A',
								'upper-roman' => 'I',
								'lower-alpha' => 'a',
								'lower-roman' => 'i',
								'decimal'     => '1' );
		
		if ( ! $allowed_types[ $list_type ] )
		{
			$open_tag = "[list]\n";
		}
		else
		{
			$open_tag = '[list=' . $allowed_types[ $list_type ] . "]\n";
		}
		
		return $open_tag . $this->_recurseAndParse( $tag, $between_text, '_parseListTag' ) . "\n[/list]";
	}

	/**
	 * RTE: Parse List Element tag
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseListElement( $tag, $between_text, $opening_tag, $parse_tag )
	{
		/* Check for quote tags */
		$openQuote  = substr_count( strtolower( $between_text ), '[quote' );
		$closeQuote = substr_count( strtolower( $between_text ), '[/quote]' );
		
		if ( $openQuote != $closeQuote )
		{
			$between_text = str_replace( array( '[quote', '[/quote]' ), array( '&#91;quote', '&#91;/quote&#93;' ), $between_text );
		}
		
		return '[*]' . rtrim( $between_text ) . "\n";
	}
	
	/**
	 * RTE: Parse paragraph tags
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseParagraphTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		/* Got any text to wrap? beware empty() because we might have 0 */
		if ( $between_text == '' )
		{
			return;
		}
		
		//-----------------------------------------
		// Reset local start tags
		//-----------------------------------------
		
		$start_tags = "";
		$end_tags   = "";
		//print $opening_tag . "\n" . $tag . "\n'" . $between_text. "'\n----\n";
		
		//-----------------------------------------
		// Check for inline style moz may have added and append start_tags
		//-----------------------------------------
		
		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		//-----------------------------------------
		// Now parse align and style (if any)
		//-----------------------------------------
		
		$align      = $this->_getValueOfOption( 'align', $opening_tag );
		$style      = $this->_getValueOfOption( 'style', $opening_tag );
		$textAlign  = $this->_extractCssValue( $style, 'text-align' );
		$marginLeft = intval( $this->_extractCssValue( $style, 'margin-left' ) );
		
		if ( $align == 'center' OR $textAlign == 'center' )
		{
			$start_tags = "\n" . $start_tags;
			
			if ( ! stristr( $start_tags, '[center]' ) )
			{
				$start_tags .= '[center]';
				$end_tags   .= '[/center]';
			}
		}
		else if ( $align == 'left' OR $textAlign == 'left' )
		{
			$start_tags = "\n" . $start_tags;
			
			if ( ! stristr( $start_tags, '[left]' ) )
			{
				$start_tags .= '[left]';
				$end_tags   .= '[/left]';
			}
		}
		else if ( $align == 'right' OR $textAlign == 'right' )
		{
			$start_tags = "\n" . $start_tags;
			
			if ( ! stristr( $start_tags, '[right]' ) )
			{
				$start_tags .= '[right]';
				$end_tags   .= '[/right]';
			}
		}
		else if ( $marginLeft )
		{
			$level = ( $marginLeft > 40 ) ? $marginLeft / 40 : 1;
			
			if ( trim( $between_text ) )
			{
				$start_tags = "\n" . $start_tags;
				
				$start_tags .= '[indent=' . $level . ']';
				$end_tags   .= '[/indent]';
			}
		}
		else
		{
			# No align? Make paragraph
			$start_tags .= "\n";
		}
		
		/* Was there just a blank space in there? */
		if ( preg_match( '#^[ ]+$#', $between_text ) )
		{
			return "\n";
		}
		
		return $start_tags . $this->_recurseAndParse( 'p', $between_text, '_parseParagraphTag' ) . $end_tags;
	}
	
	/**
	 * RTE: Parse pre tags
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parsePreTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		/* Got any text to wrap? beware empty() because we might have 0 */
		if ( $between_text == '' )
		{
			return;
		}
		
		if ( ! stristr( $between_text, '<br' ) && stristr( $between_text, "\n" ) )
		{
			$between_text = str_replace( "\n", "<br />", $between_text );
		}
		
		return $this->_recurseAndParse( 'pre', $between_text, '_parsePreTag' );
	}	
	
	/**
	 * RTE: Parse Span tag
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseSpanTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------

		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		return $start_tags . $this->_recurseAndParse( 'span', $between_text, '_parseSpanTag' ) . $end_tags;
	}
	
	/**
	 * RTE: Parse Fieldset tag used to contain code
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseFieldsetTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		//-----------------------------------------
		// Reset local start tags
		//-----------------------------------------
		
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "<b><span style='color:red'>DIV FIRED</b></span><br />Start tags: {$this->start_tags}<br />End tags: {$this->end_tags}<br />Between text:<br />".htmlspecialchars($between_text)."<hr />";
		}
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------
		
		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		//-----------------------------------------
		// Now parse align (if any)
		//-----------------------------------------
		
		$class = $this->_getValueOfOption( 'class', $opening_tag );
		
		if ( $class == 'ipbCode' )
		{
			$start_tags .= '[code]';
			$end_tags   .= '[/code]';
		}

		//-----------------------------------------
		// Get recursive text
		//-----------------------------------------
		
		$final = $this->_recurseAndParse( 'fieldset', trim( $between_text ), '_parseFieldsetTag' );
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "\n<hr><b style='color:green'>FINISHED</b><br/ >".$start_tags . trim( $final ) . $end_tags."<hr>";
		}
		
		//-----------------------------------------
		// Now return
		//-----------------------------------------
		
		return $start_tags . $final . $end_tags;
	}
	
	/**
	 * RTE: Parse DIV tag
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseDivTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		//-----------------------------------------
		// Reset local start tags
		//-----------------------------------------
		
		$start_tags = "";
		$end_tags   = "";
		$allowEndNl = true;
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "<b><span style='color:red'>DIV FIRED</b></span><br />Start tags: {$this->start_tags}<br />End tags: {$this->end_tags}<br />Between text:<br />".htmlspecialchars($between_text)."<hr />";
		}
		
		//-----------------------------------------
		// Check for inline style moz may have added
		//-----------------------------------------
		
		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		//-----------------------------------------
		// Now parse align (if any)
		//-----------------------------------------
		
		$align = $this->_getValueOfOption( 'align', $opening_tag );
		
		if ( $align == 'center' )
		{
			$start_tags .= '[center]';
			$end_tags   .= '[/center]';
			$allowEndNl  = false;
		}
		else if ( $align == 'left' )
		{
			$start_tags .= '[left]';
			$end_tags   .= '[/left]';
			$allowEndNl  = false;
		}
		else if ( $align == 'right' )
		{
			$start_tags .= '[right]';
			$end_tags   .= '[/right]';
			$allowEndNl  = false;
		}
		else
		{
			# No align? Make paragraph
			$start_tags .= "\n";
		}

		//-----------------------------------------
		// Get recursive text
		//-----------------------------------------
		
		$final = $this->_recurseAndParse( 'div', $between_text, '_parseDivTag' );
		
		//-----------------------------------------
		// #DEBUG
		//-----------------------------------------
		
		if ( $this->debug == 2 )
		{
			print "\n<hr><b style='color:green'>FINISHED</b><br/ >".$start_tags . $final . $end_tags."<hr>";
		}
		
		//-----------------------------------------
		// Now return
		//-----------------------------------------
		
		return $start_tags . $final . $end_tags;
	}
	
	/**
	 * RTE: Parse style attributes (color, font, size, b, i..etc)
	 *
	 * @access	protected
	 * @param	string	Opening tag
	 * @param	string	Start tags
	 * @param	string	End tags
	 * @return	string	Converted text
	 */
	protected function _parseStyles( $opening_tag, &$start_tags, &$end_tags )
	{
		$style_list = array(
							array('tag' => 'color'      , 'rx' => '(?<![\w\-])color:\s*([^;]+);?'	, 'match' => 1),
							array('tag' => 'font'       , 'rx' => 'font-family:\s*([^;]+);?'		, 'match' => 1),
							array('tag' => 'size'       , 'rx' => 'font-size:\s*(.+);?'			, 'match' => 1),
							array('tag' => 'b'          , 'rx' => 'font-weight:\s*(bold);?'),
							array('tag' => 'i'          , 'rx' => 'font-style:\s*(italic);?'),
							array('tag' => 'u'          , 'rx' => 'text-decoration:\s*(underline);?'),
							array('tag' => 'left'       , 'rx' => 'text-align:\s*(left);?'),
							array('tag' => 'center'     , 'rx' => 'text-align:\s*(center);?'),
							array('tag' => 'right'      , 'rx' => 'text-align:\s*(right);?'),
							array('tag' => 'background' , 'rx' => 'background-color:\s*([^;]+);?', 'match' => 1),
						  );
		
		//-----------------------------------------
		// get style option
		//-----------------------------------------
		
		$style = $this->_getValueOfOption( 'style', $opening_tag );
		$class = $this->_getValueOfOption( 'class', $opening_tag );

		//-----------------------------------------
		// Convert RGB to hex
		//-----------------------------------------

		$style = preg_replace_callback( '#(?<![\w\-])color:\s+?rgb\((\d+,\s+?\d+,\s+?\d+)\)(;?)#i', array( &$this, '_rgbToHex' ), $style );
		
		//-----------------------------------------
		// Pick through possible styles
		//-----------------------------------------
		
		foreach( $style_list as $data )
		{
			if ( preg_match( '#' . $data['rx'] . '#i', $style, $match ) )
			{
				if ( $data['match'] )
				{
					if ( $data['tag'] != 'size' )
					{
						if( $data['tag'] != 'font' OR $match[ $data['match'] ] != 'Verdana, arial, sans-serif' )
						{
							$start_tags .= "[{$data['tag']}={$match[$data['match']]}]";
						}
					}
					else
					{
						$start_tags .= "[{$data['tag']}=" . $this->convertRealsizeToBbsize($match[$data['match']]) ."]";
					}
				}
				else
				{
					$start_tags .= "[{$data['tag']}]";
				}
				
				if ( $start_tags && $data['tag'] != 'font' OR $match[ $data['match'] ] != 'Verdana, arial, sans-serif' )
				{
					$end_tags = "[/{$data['tag']}]" . $end_tags;
				}
			}
		}
		
		if( $class == 'bbc_underline' )
		{
			$start_tags	= '[u]';
			$end_tags	= '[/u]';
		}
	}

	/**
	 * RTE: Parse FONT tag
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseFontTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		$font_tags  = array( 'font' => 'face', 'size' => 'size', 'color' => 'color' );
		$start_tags = "";
		$end_tags   = "";
		
		//-----------------------------------------
		// Check for attributes
		//-----------------------------------------
		
		foreach( $font_tags as $bbcode => $string )
		{
			$option = $this->_getValueOfOption( $string, $opening_tag );
			
			if ( $option )
			{
				$start_tags .= "[{$bbcode}=\"{$option}\"]";
				$end_tags    = "[/{$bbcode}]" . $end_tags;
				
				if ( $this->debug == 2 )
				{
					print "<br />Got bbcode=$bbcode / opening_tag=$opening_tag";
					print "<br />- Adding [$bbcode=\"$option\"] [/$bbcode]";
					print "<br />-- start tags now: {$start_tags}";
					print "<br />-- end tags now: {$end_tags}";
				}
			}
		}
		
		//-----------------------------------------
		// Now check for inline style moz may have
		// added
		//-----------------------------------------
		
		$this->_parseStyles( $opening_tag, $start_tags, $end_tags );
		
		return $start_tags . $this->_recurseAndParse( 'font', $between_text, '_parseFontTag' ) . $end_tags;
	}

	/**
	 * RTE: Simple tags
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseSimpleTag( $tag, $between_text, $opening_tag, $parse_tag )
	{
		if ( ! $parse_tag )
		{
			$parse_tag = $tag;
		}
		
		return "[{$parse_tag}]" . $this->_recurseAndParse( $tag, $between_text, '_parseSimpleTag', $parse_tag ) . "[/{$parse_tag}]";
	}

	/**
	 * RTE: Parse A HREF tag
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Opening tag complete
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _parseAnchorTag( $tag, $between_text, $opening_tag, $parse_tag='' )
	{
		$mytag = 'url';
		$href  = $this->_getValueOfOption( 'href', $opening_tag );
		$class = $this->_getValueOfOption( 'class', $opening_tag );
		
		$href  = str_replace( '<', '&lt;', $href );
		$href  = str_replace( '>', '&gt;', $href );
		$href  = str_replace( ' ', '%20' , $href );
		
		if ( preg_match( '#^mailto\:#is', $href ) )
		{
			$mytag = 'email';
			$href  = str_replace( "mailto:", "", $href );
		}
		
		return "[{$mytag}=\"{$href}\"]" . $this->_recurseAndParse( $tag, $between_text, '_parseAnchorTag', $parse_tag ) . "[/{$mytag}]";
	}

	/**
	 * RTE: Recursively parse tags
	 *
	 * @access	protected
	 * @param	string	Tag
	 * @param	string	Text between opening and closing tag
	 * @param	string	Callback Function
	 * @param	string	Parse tag
	 * @return	string	Converted text
	 */
	protected function _recurseAndParse( $tag, $text, $function, $parse_tag='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$tag              = strtolower($tag);
		$open_tag         = "<" . $tag;
		$open_tag_len     = strlen($open_tag);
		$close_tag        = "</" . $tag . ">";
		$close_tag_len    = strlen($close_tag);
		$start_search_pos = 0;
		$tag_begin_loc    = 1;
		
		//-----------------------------------------
		// Start the loop
		//-----------------------------------------

		while ( $tag_begin_loc !== FALSE )
		{
			$lowtext       = strtolower($text);
			$tag_begin_loc = @strpos( $lowtext, $open_tag, $start_search_pos );
			$lentext       = strlen($text);
			$quoted        = '';
			$got           = FALSE;
			$tag_end_loc   = FALSE;
			
			//-----------------------------------------
			// No opening tag? Break
			//-----------------------------------------
		
			if ( $tag_begin_loc === FALSE )
			{
				break;
			}
			
			//-----------------------------------------
			// Pick through text looking for delims
			//-----------------------------------------
			
			for ( $end_opt = $tag_begin_loc; $end_opt <= $lentext; $end_opt++ )
			{
				$chr = $text{$end_opt};
				
				//-----------------------------------------
				// We're now in a quote
				//-----------------------------------------
				
				if ( ( in_array( $chr, $this->delimiters ) ) AND $quoted == '' )
				{
					$quoted = $chr;
				}
				
				//-----------------------------------------
				// We're not in a quote any more
				//-----------------------------------------
				
				else if ( ( in_array( $chr, $this->delimiters ) ) AND $quoted == $chr )
				{
					$quoted = '';
				}
				
				//-----------------------------------------
				// Found the closing bracket of the open tag
				//-----------------------------------------
				
				else if ( $chr == '>' AND ! $quoted )
				{
					$got = TRUE;
					break;
				}
				
				else if ( ( in_array( $chr, $this->non_delimiters ) ) AND ! $tag_end_loc )
				{
					$tag_end_loc = $end_opt;
				}
			}
			
			//-----------------------------------------
			// Not got the complete tag?
			//-----------------------------------------
			
			if ( ! $got )
			{
				break;
			}
			
			//-----------------------------------------
			// Not got a tag end location?
			//-----------------------------------------
			
			if ( ! $tag_end_loc )
			{
				$tag_end_loc = $end_opt;
			}
			
			//-----------------------------------------
			// Extract tag options...
			//-----------------------------------------
			
			$tag_opts        = substr( $text   , $tag_begin_loc + $open_tag_len, $end_opt - ($tag_begin_loc + $open_tag_len) );
			$actual_tag_name = substr( $lowtext, $tag_begin_loc + 1            , ( $tag_end_loc - $tag_begin_loc ) - 1 );
			
			//-----------------------------------------
			// Check against actual tag name...
			//-----------------------------------------
			
			if ( $actual_tag_name != $tag )
			{
				$start_search_pos = $end_opt;
				continue;
			}
	
			//-----------------------------------------
			// Now find the end tag location
			//-----------------------------------------
			
			$tag_end_loc = strpos( $lowtext, $close_tag, $end_opt );
			
			//-----------------------------------------
			// Not got one? Break!
			//-----------------------------------------
			
			if ( $tag_end_loc === FALSE )
			{
				break;
			}
	
			//-----------------------------------------
			// Check for nested tags
			//-----------------------------------------
			
			$nest_open_pos = strpos($lowtext, $open_tag, $end_opt);
			
			while ( $nest_open_pos !== FALSE AND $tag_end_loc !== FALSE )
			{
				//-----------------------------------------
				// It's not actually nested
				//-----------------------------------------
				
				if ( $nest_open_pos > $tag_end_loc )
				{
					break;
				}
				
				if ( $this->debug == 2)
				{
					print "\n\n<hr>( ".htmlspecialchars($open_tag)." ) NEST FOUND</hr>\n\n";
				}
				
				$tag_end_loc   = strpos($lowtext, $close_tag, $tag_end_loc   + $close_tag_len);
				$nest_open_pos = strpos($lowtext, $open_tag , $nest_open_pos + $open_tag_len );
			}
			
			//-----------------------------------------
			// Make sure we have an end location
			//-----------------------------------------
			
			if ( $tag_end_loc === FALSE )
			{
				$start_search_pos = $end_opt;
				continue;
			}
	
			$this_text_begin  = $end_opt + 1;
			$between_text     = substr($text, $this_text_begin, $tag_end_loc - $this_text_begin);
			$offset           = $tag_end_loc + $close_tag_len - $tag_begin_loc;
			
			//-----------------------------------------
			// Pass to function
			//-----------------------------------------
			
			$final_text       = $this->$function($tag, $between_text, $tag_opts, $parse_tag);
			
			//-----------------------------------------
			// #DEBUG
			//-----------------------------------------
			
			if ( $this->debug == 2)
			{
				print "<hr><b>REPLACED {$function}($tag, ..., $tag_opts):</b><br />".htmlspecialchars(substr($text, $tag_begin_loc, $offset))."<br /><b>WITH:</b><br />".htmlspecialchars($final_text)."<hr>NEXT ITERATION";
			}
				
			//-----------------------------------------
			// Swap text
			//-----------------------------------------
			
			$text             = substr_replace($text, $final_text, $tag_begin_loc, $offset);
			$start_search_pos = $tag_begin_loc + strlen($final_text);
		} 
	
		return $text;
	}

	/**
	 * RTE: Extract option HTML
	 *
	 * @access	protected
	 * @param	string	Option
	 * @param	string	Text
	 * @return	string	Converted text
	 */
	protected function _getValueOfOption( $option, $text )
	{
		if( $option == 'face' )
		{
			// Bad font face, bad
			preg_match( "#{$option}(\s+?)?\=(\s+?)?[\"']?(.+?)([\"']|$|color|size|>)#is", $text, $matches );
		}
		else
		{
			// @link http://community.invisionpower.com/tracker/issue-29336-colours-do-not-work-in-signatures
			// ckeditor should have a more universal formatting so let's go with the one regex now
			//if( $option == 'style' AND ( $this->memberData['userAgentKey'] == 'safari' OR $this->memberData['userAgentKey'] == 'chrome' ) )
			//{
				/* @link http://community.invisionpower.com/tracker/issue-37385-editor-removes-apostrophe-from-url/
				   tightened up regex end ([\"'](\s|$|>)) */
				preg_match( "#{$option}(\s*?)?\=(\s*?)?[\"']?(.+?)([\"'](\s|$|>))#is", $text, $matches );
			//}
			//else
			//{
			//	preg_match( "#{$option}(\s*?)?\=(\s*?)?[\"']?(.+?)([\"']|$|\s|>)#is", $text, $matches );
			//}
		}

		if( $option == 'style' )
		{
			switch( $matches[3] )
			{
				case 'font-size: x-small;':
					$matches[3]	= 'font-size: 8;';
				break;
				
				case 'font-size: small;':
					$matches[3]	= 'font-size: 10;';
				break;
				
				case 'font-size: medium;':
					$matches[3]	= 'font-size: 12;';
				break;
				
				case 'font-size: large;':
					$matches[3]	= 'font-size: 14;';
				break;
				
				case 'font-size: x-large;':
					$matches[3]	= 'font-size: 18;';
				break;
				
				case 'font-size: xx-large;':
					$matches[3]	= 'font-size: 24;';
				break;
				
				case 'font-size: xxx-large;':
				case 'font-size: -webkit-xxx-large;':
					$matches[3]	= 'font-size: 36;';
				break;
			}
		}

		return isset($matches[3]) ? trim( $matches[3] ) : '';
	}

	/**
	 * unhtml url: Removes < and >
	 *
	 * @access	protected
	 * @param	array 		Matches from preg_replace_callback
	 * @return	string		Converted text
	 */
	protected function _unhtmlUrl( $matches=array() )
	{
		$url  = stripslashes( $matches[3] );
		$type = stripslashes( $matches[1] ? $matches[1] : 'a href' );
		
		$url  = str_replace( '<', '&lt;', $url );
		$url  = str_replace( '>', '&gt;', $url );
		$url  = str_replace( ' ', '%20' , $url );
		
		return '<' . $type . '="' . $url . '"';
	}
	
	/**
	 * Fetches the value of an inline style
	 * @param string $style
	 * @param string $lookFor
	 * @return string|boolean
	 */
	protected function _extractCssValue( $style, $lookFor )
	{
		if ( strstr( $style, 'style=') )
		{
			$style = $this->_getValueOfOption( 'style', $style );
		}
		
		if ( strstr( $style, $lookFor ) )
		{
			if ( preg_match( '#' . preg_quote( $lookFor, '#') . ':(?:\s+?)?(.+?)(;|$|\n)#', $style, $matches ) )
			{
				return trim( $matches[1] );
			}
		}
		
		return false;
	}

	/**
	 * Converts color:rgb(x,x,x) to color:#xxxxxx
	 *
	 * @access	protected
	 * @param	string	rgb contents: x,x,x
	 * @param	string	regex end
	 * @return	string	Converted text
	 */
	protected function _rgbToHex($matches)
	{
		$t  = $matches[1];
		$t2 = $matches[2];
		
		$tmp = array_map( "trim", explode( ",", $t ) );
		return 'color: ' . sprintf( "#%02X%02X%02X" . $t2, intval($tmp[0]), intval($tmp[1]), intval($tmp[2]) );
	}
	
	/**
	 * Generates unique editor ID
	 */
	protected function _fetchEditorName()
	{
		return 'editor_' . uniqid();
	}
	
	/**
	 * Returns current emoticon directory...
	 * @return string
	 */
	protected function _fetchSmilieDir()
	{
  		if ( IN_ACP )
  		{
			$image_set = $this->DB->buildAndFetch( array( 'select' => 'set_image_dir, set_emo_dir', 'from' => 'skin_collections', 'where' => 'set_is_default=1' ) );

			return $image_set['set_emo_dir'];
  		}
  		else
  		{
			return ipsRegistry::getClass('output')->skin['set_emo_dir'];
  		}
	}
	
	/**
	 * Generates a full autosave key
	 * @param string $autoSaveKey
	 * @return string
	 */
	protected function _generateAutoSaveKey( $autoSaveKey )
	{
		return md5( IPS_APP_COMPONENT . '-' . trim( $autoSaveKey ) . '-' . intval( $this->memberData['member_id'] ) );
	}
	
	/**
	 * Clean up and make the text for the DB
	 *
	 * @access	protected
	 * @param	string		Raw text
	 * @return	string		Converted text
	 */
	protected function _clean( $t, $NO_SANITIZE=false, $isFromRte=false )
    {
    	if ( $t == "" )
    	{
    		return "";
    	}
		
    	if ( $this->getAllowHtml() )
    	{
    		return $t;
    	}
   
		if ( IPS_DOC_CHAR_SET == 'UTF-8' || $this->_isSwitchingFrom == 'std' )
		{
    		$t = IPSText::decodeNamedHtmlEntities( $t );
		}
		
    	if ( $isFromRte === false )
    	{
	    	$t = preg_replace( '#&(?!\#[0-9]+;)#', '&amp;', $t );
	    	
	    	$t = preg_replace("/&#([0-9]+);/s", "&amp;#\\1;", $t );
    	}
    	
    	$t = str_replace( ">", "&gt;", $t );
	    $t = str_replace( "<", "&lt;", $t );
	    	
    	/* We replace \ to 092 so convert it back */
		$t = str_replace( '&amp;#092;', '\\', $t );
		
    	/* Make it safe */
    	if ( $NO_SANITIZE !== true )
    	{   			
	    	$t = str_replace( "<!--"		, "&#60;&#33;--"  , $t );
	    	$t = str_replace( "-->"			, "--&#62;"       , $t );
	    	$t = str_ireplace( "<script"	, "&#60;script"   , $t );
	    	$t = str_replace( '"'			, "&quot;"        , $t );
	    	$t = str_replace( '$'			, "&#036;"        , $t );
	    	$t = str_replace( "\r"			, ""              , $t );
	    	$t = str_replace( "!"			, "&#33;"         , $t );
	    	$t = str_replace( "'"			, "&#39;"         , $t );
    	}    	
    		
    	$t = str_replace( "&#34;", '&quot;', $t );
    	$t = str_replace( "&#38;", '&amp;', $t );
    	$t = str_replace( "&#160;", ' ', $t );
    	
	   	/* Make tags safe */
    	$t = preg_replace( '#\{(style_image(?:s)?_url)\}#i', '&#123;\1&#125;', $t );
    	
    	$t = str_replace( "\n", "<br />", $t );
    	
    	$t = $this->_allowNonUtf8CharsWhenNotUsingUtf8Doc( $t );

    	$t = $this->_convertNumericEntityToNamed( $t );
   // print header('Content-type: text/plain'); print $t; exit();	
    	return $t;
    }
    
	/**
	 * Get BBCode font size from real PX size
	 *
	 * @access	public
	 * @param	integer		PX Size
	 * @return	integer		BBCode size
	 */
	public function convertRealsizeToBbsize( $real )
	{
		$real = intval( $real );
		
		//-----------------------------------------
		// If we have a true mapping, use it
		//-----------------------------------------
		
		if ( $this->rev_font_sizes[ $real ] )
		{
			return $this->rev_font_sizes[ $real ];
		}
		else
		{
			//-----------------------------------------
			// Otherwise find the next closest size down
			//-----------------------------------------
			
			foreach( $this->rev_font_sizes as $font => $bbcode )
			{
				if( $real < $font )
				{
					return ( ( $bbcode - 1 ) > 1 ) ? ( $bbcode - 1 ) : 1;
				}
			}
			
			return 2;
		}
	}
	
	/**
	 * Fetch emoticons as JSON for editors, etc
	 *
	 * @param	mixed		Number of emoticons to fetch (false to fetch all, or an int limit)
	 * @return	string		JSON
	 */
	public function fetchEmoticons( $fetchFirstX=false )
	{
		$emoDir    = $this->_fetchSmilieDir();
		$emoString = '';
		$smilie_id = 0;
		$total     = 0;

		foreach( ipsRegistry::cache()->getCache('emoticons') as $elmo )
		{
			if ( $elmo['emo_set'] != $emoDir )
			{
				continue;
			}
			
			$total++;
			
			if ( $fetchFirstX !== false && ( $smilie_id + 1 > $fetchFirstX ) )
			{
				continue;
			}

			//-----------------------------------------
			// Make single quotes as URL's with html entites in them
			// are parsed by the browser, so ' causes JS error :o
			//-----------------------------------------
			
			if ( strstr( $elmo['typed'], "&#39;" ) )
			{
				$in_delim  = '"';
			}
			else
			{
				$in_delim  = "'";
			}
			
			$emoArray[ $smilie_id ] = array( 'src'  => $elmo['image'],
											 'text' => addslashes($elmo['typed']) );
			
			
			$smilie_id++;
		}
		
		return array( 'total' => $total, 'count' => $smilie_id, 'emoticons' => $emoArray );
	}

	/**
	 * Makes code stuff safe
	 * @param array $matches
	 * @return string
	 */
	protected function _rteMakeCodeSafeWhileParsing( $matches )
	{
		/* Code can come in PRE tags so there will be \n but no <br> */
		if ( ! stristr( $matches[0], '<br' ) && stristr( $matches[0], "\n" ) )
		{
			return str_replace( "\n", "<code-new-line>", str_replace( array( '<br>', '<br />' ), '', $matches[0] ) );
		}
		
		return $matches[0];
	}
	
	/**
	 * The crazy crap we have to deal with. User enters squiggly char, it gets turned into &#128; or &Aacute; but there is
	 * no matching character in the doc set so we have to try and decode manually

	 * @param string Raw string
	 * @return string	Converted string
	 */
	protected function _allowNonUtf8CharsWhenNotUsingUtf8Doc( $string )
	{ 
		/* If we're not using UTF-8, lets try and handle encoded data. */
		if ( IPS_DOC_CHAR_SET != 'UTF-8' )
		{
			/* First, convert any left over &acute; style chars */
			preg_match_all( '/&amp;([a-zA-Z]{5,8});/', $string, $matches );
			
			foreach( $matches[1] as $word )
			{
				$converted = str_replace( '&', '&amp;', $this->_convertEntity( array( 1 => $word ) ) );
				
				if ( $converted  )
				{
					$string = str_replace( '&amp;' . $word . ';', $converted, $string );
				}
			}
			
			preg_match_all( '/&amp;#([0-9]{3,6});/', $string, $matches );
			
			foreach( $matches[1] as $word )
			{
				if ( $word > 127 )
				{
					$string = str_replace( '&amp;#' . $word . ';', '&#' . $word . ';', $string );
				}
			}
		}
		
		return $string;
	}
	
	/**
	 * Converts numeric entities to their named equivalents
	 *
	 * @param	string
	 * @return	string
	 */
	protected function _convertNumericEntityToNamed( $string )
	{
		/* If we're not using UTF-8, lets try and handle encoded data. */
		if ( IPS_DOC_CHAR_SET != 'UTF-8' )
		{
			/* First, convert any left over &acute; style chars */
			preg_match_all( '/&#([0-9]{3,8});/', $string, $matches );
			
			foreach( $matches[1] as $word )
			{
				if ( $word > 127 )
				{
					$converted = str_replace( '&', '&amp;', $this->_convertEntity( array( 1 => $word ), true ) );
					
					if ( $converted  )
					{
						$string = str_replace( '&#' . $word . ';', $converted, $string );
					}
				}
			}
		}
		
		return $string;
	}
	
	/**
	 * Function to convert named entities to numeric entities
	 *
	 * @param	array 	$matches	Results from preg_match call
	 * @return	string
	 * @link	http://www.lazycat.org/php-convert-entities.php
	 */
	protected function _convertEntity( $matches, $flip=false )
	{
	  static $table = array(/*'quot' => '&#34;','amp' => '&#38;','lt' => '&#60;','gt' => '&#62;',*/'OElig' => '&#338;','oelig' => '&#339;','Scaron' => '&#352;','scaron' => '&#353;','Yuml' => '&#376;',
							'circ' => '&#710;','tilde' => '&#732;','ensp' => '&#8194;','emsp' => '&#8195;','thinsp' => '&#8201;','zwnj' => '&#8204;','zwj' => '&#8205;','lrm' => '&#8206;','rlm' => '&#8207;',
							'ndash' => '&#8211;','mdash' => '&#8212;','lsquo' => '&#8216;','rsquo' => '&#8217;','sbquo' => '&#8218;','ldquo' => '&#8220;','rdquo' => '&#8221;','bdquo' => '&#8222;','dagger' => '&#8224;',
							'Dagger' => '&#8225;','permil' => '&#8240;','lsaquo' => '&#8249;','rsaquo' => '&#8250;','euro' => '&#8364;','fnof' => '&#402;','Alpha' => '&#913;','Beta' => '&#914;','Gamma' => '&#915;',
							'Delta' => '&#916;','Epsilon' => '&#917;','Zeta' => '&#918;','Eta' => '&#919;','Theta' => '&#920;','Iota' => '&#921;','Kappa' => '&#922;','Lambda' => '&#923;','Mu' => '&#924;','Nu' => '&#925;',
							'Xi' => '&#926;','Omicron' => '&#927;','Pi' => '&#928;','Rho' => '&#929;','Sigma' => '&#931;','Tau' => '&#932;','Upsilon' => '&#933;','Phi' => '&#934;','Chi' => '&#935;','Psi' => '&#936;',
							'Omega' => '&#937;','alpha' => '&#945;','beta' => '&#946;','gamma' => '&#947;','delta' => '&#948;','epsilon' => '&#949;','zeta' => '&#950;','eta' => '&#951;','theta' => '&#952;','iota' => '&#953;',
							'kappa' => '&#954;','lambda' => '&#955;','mu' => '&#956;','nu' => '&#957;','xi' => '&#958;','omicron' => '&#959;','pi' => '&#960;','rho' => '&#961;','sigmaf' => '&#962;','sigma' => '&#963;',
							'tau' => '&#964;','upsilon' => '&#965;','phi' => '&#966;','chi' => '&#967;','psi' => '&#968;','omega' => '&#969;','thetasym' => '&#977;','upsih' => '&#978;','piv' => '&#982;','bull' => '&#8226;',
							'hellip' => '&#8230;','prime' => '&#8242;','Prime' => '&#8243;','oline' => '&#8254;','frasl' => '&#8260;','weierp' => '&#8472;','image' => '&#8465;','real' => '&#8476;','trade' => '&#8482;',
							'alefsym' => '&#8501;','larr' => '&#8592;','uarr' => '&#8593;','rarr' => '&#8594;','darr' => '&#8595;','harr' => '&#8596;','crarr' => '&#8629;','lArr' => '&#8656;','uArr' => '&#8657;',
							'rArr' => '&#8658;','dArr' => '&#8659;','hArr' => '&#8660;','forall' => '&#8704;','part' => '&#8706;','exist' => '&#8707;','empty' => '&#8709;','nabla' => '&#8711;','isin' => '&#8712;',
							'notin' => '&#8713;','ni' => '&#8715;','prod' => '&#8719;','sum' => '&#8721;','minus' => '&#8722;','lowast' => '&#8727;','radic' => '&#8730;','prop' => '&#8733;','infin' => '&#8734;',
							'ang' => '&#8736;','and' => '&#8743;','or' => '&#8744;','cap' => '&#8745;','cup' => '&#8746;','int' => '&#8747;','there4' => '&#8756;','sim' => '&#8764;','cong' => '&#8773;','asymp' => '&#8776;',
							'ne' => '&#8800;','equiv' => '&#8801;','le' => '&#8804;','ge' => '&#8805;','sub' => '&#8834;','sup' => '&#8835;','nsub' => '&#8836;','sube' => '&#8838;','supe' => '&#8839;','oplus' => '&#8853;',
							'otimes' => '&#8855;','perp' => '&#8869;','sdot' => '&#8901;','lceil' => '&#8968;','rceil' => '&#8969;','lfloor' => '&#8970;','rfloor' => '&#8971;','lang' => '&#9001;','rang' => '&#9002;',
							'loz' => '&#9674;','spades' => '&#9824;','clubs' => '&#9827;','hearts' => '&#9829;','diams' => '&#9830;','nbsp' => ' ','iexcl' => '&#161;','cent' => '&#162;','pound' => '&#163;',
							'curren' => '&#164;','yen' => '&#165;','brvbar' => '&#166;','sect' => '&#167;','uml' => '&#168;','copy' => '&#169;','ordf' => '&#170;','laquo' => '&#171;','not' => '&#172;','shy' => '&#173;',
							'reg' => '&#174;','macr' => '&#175;','deg' => '&#176;','plusmn' => '&#177;','sup2' => '&#178;','sup3' => '&#179;','acute' => '&#180;','micro' => '&#181;','para' => '&#182;','middot' => '&#183;',
							'cedil' => '&#184;','sup1' => '&#185;','ordm' => '&#186;','raquo' => '&#187;','frac14' => '&#188;','frac12' => '&#189;','frac34' => '&#190;','iquest' => '&#191;','Agrave' => '&#192;',
							'Aacute' => '&#193;','Acirc' => '&#194;','Atilde' => '&#195;','Auml' => '&#196;','Aring' => '&#197;','AElig' => '&#198;','Ccedil' => '&#199;','Egrave' => '&#200;','Eacute' => '&#201;',
							'Ecirc' => '&#202;','Euml' => '&#203;','Igrave' => '&#204;','Iacute' => '&#205;','Icirc' => '&#206;','Iuml' => '&#207;','ETH' => '&#208;','Ntilde' => '&#209;','Ograve' => '&#210;',
							'Oacute' => '&#211;','Ocirc' => '&#212;','Otilde' => '&#213;','Ouml' => '&#214;','times' => '&#215;','Oslash' => '&#216;','Ugrave' => '&#217;','Uacute' => '&#218;','Ucirc' => '&#219;',
							'Uuml' => '&#220;','Yacute' => '&#221;','THORN' => '&#222;','szlig' => '&#223;','agrave' => '&#224;','aacute' => '&#225;','acirc' => '&#226;','atilde' => '&#227;','auml' => '&#228;',
							'aring' => '&#229;','aelig' => '&#230;','ccedil' => '&#231;','egrave' => '&#232;','eacute' => '&#233;','ecirc' => '&#234;','euml' => '&#235;','igrave' => '&#236;','iacute' => '&#237;',
							'icirc' => '&#238;','iuml' => '&#239;','eth' => '&#240;','ntilde' => '&#241;','ograve' => '&#242;','oacute' => '&#243;','ocirc' => '&#244;','otilde' => '&#245;','ouml' => '&#246;',
							'divide' => '&#247;','oslash' => '&#248;','ugrave' => '&#249;','uacute' => '&#250;','ucirc' => '&#251;','uuml' => '&#252;','yacute' => '&#253;','thorn' => '&#254;','yuml' => '&#255;' );
		
		if ( $flip === true )
		{
			$tmp = array_flip( $table );
			
			$lookup = '&#' . $matches[1] . ';';
			
			return isset( $tmp[ $lookup ] ) ? '&' . $tmp[ $lookup ] . ';' : $lookup;
		}
		
		return isset( $table[ $matches[1] ] ) ? $table[ $matches[1] ] : '&' . $matches[1] . ';';
	}
}
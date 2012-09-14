<?php
/**
 * @file		googleplusone.php 	Google+1 plugin for share links library
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: ips_terabyte $
 * @since		-
 * $LastChangedDate: 2010-10-14 13:11:17 -0400 (Thu, 14 Oct 2010) $
 * @version		v3.3.3
 * $Revision: 477 $
 */

/* Class name must be in the format of:
   sl_{key}
   Where {key}, place with the value of: core_share_links.share_key
 */


/**
 * @class		sl_googleplusone
 * @brief		Google+1 plugin for share links library
 */
class sl_googleplusone
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
	public function __construct( $registry )
	{
		/* Make object */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Requires a permission check
	 *
	 * @param	array		$array		Data array
	 * @return	@e boolean
	 */
	public function requiresPermissionCheck( $array )
	{
		return false;
	}
	
	/**
	 * Using a custom template bit?
	 *
	 * @return	@e array	array( skinGroup, templateBitName, lang )
	 */
	public function customOutput()
	{
		//-----------------------------------------
		// Work out the language
		// @see https://developers.google.com/+/plugins/+1button/#available-languages
		//-----------------------------------------
						
		$langCode = 'en-US';
		
		if ( isset( $this->settings['googlePlusOneLanguage'] ) ) // so you can use a power setting if you like
		{
			$langCode = $this->settings['googlePlusOneLanguage'];
		}
		else
		{
			$acceptedLanguages = array( 'ar', 'bg', 'ca', 'zh', 'hr', 'cs', 'da', 'nl', 'en', 'et', 'fil', 'fi', 'fr', 'de', 'el', 'iw', 'hi', 'hu', 'id', 'it', 'ja', 'ko', 'lv', 'lt', 'ms', 'no', 'fa', 'pl', 'pt', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'sv', 'th', 'tr', 'uk', 'vi' );
			$subLanguages = array(
				'en' => array( 'gb', 'us' ),
				'zh' => array( 'cn', 'tw' ),
				'pt' => array( 'br', 'pt' )
				);
		
			foreach( $this->caches['lang_data'] as $lang )
			{
				if ( $lang['lang_default'] )
				{				
					if ( strstr( $lang['lang_short'], '_' ) !== FALSE )
					{
						$localeExploded = explode( '_', $lang['lang_short'] );
					}
					elseif ( strstr( $lang['lang_short'], '-' ) !== FALSE )
					{
						$localeExploded = explode( '-', $lang['lang_short'] );
					}
					else
					{
						break;
					}
										
					if ( in_array( strtolower( $localeExploded[0] ), $acceptedLanguages ) )
					{
						$langCode = strtolower( $localeExploded[0] );
						
						if ( isset( $subLanguages[ strtolower( $localeExploded[0] ) ] ) )
						{
							if ( isset( $subLanguages[ strtolower( $localeExploded[1] ) ] ) )
							{
								$langCode = strtolower( $localeExploded[0] ) . '-' . strtoupper( $localeExploded[1] );
							}
							else
							{
								$langCode = strtolower( $localeExploded[0] ) . '-' . strtoupper( $subLanguages[ strtolower( $localeExploded[0] ) ][0] );
							}
						}
					}					
					
					break;
				}
			}
		}
		
		return array( 'global', 'googlePlusOneButton', array( 'lang' => $langCode ) ); 
	}
	
	/**
	 * Redirect to Google
	 * Exciting, isn't it.
	 *
	 * @param	string		Plug in
	 */
	public function share( $title, $url )
	{
		$title = IPSText::convertCharsets( $title, IPS_DOC_CHAR_SET, 'utf-8' );
		$url   = "http://www.google.com/sharer.php?u=" . urlencode( $url );
		
		$this->registry->output->silentRedirect( $url );
	}
}
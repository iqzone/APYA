<?php
/**
 * <pre>
 * Invision Power Services
 * IP.Board v3.3.3
 * Advertisements
 * </pre>
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2010 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/company/standards.php#license
 * @package		IP.Board
 * @link		http://www.invisionpower.com
 * @since		17th February 2010
 * @version		$Revision: 10721 $
 */

class IPSAdCode extends IPSAdCodeDefault
{
	/**
	 * Constructor
	 *
	 */
	public function __construct( ipsRegistry $registry )
	{
		return parent::__construct( $registry );
	}

	/**
	 * Checks to see if the specified member group can view ads
	 *
	 * @access	public
	 *
	 * @param	integer		$group_id		Group to check
	 * @return	bool 
	 */
	public function userCanViewAds()
	{
		/* Check to see if the system is globally disabled */
		if( ! ipsRegistry::$settings['ad_code_global_enabled'] )
		{
			return false;
		}
		
		/* Check to see if this member group can view ads */
		if( ipsRegistry::$settings['ad_code_exempt_groups'] )
		{
			if( IPSMember::isInGroup( ipsRegistry::member()->fetchMemberData(), explode( ',', ipsRegistry::$settings['ad_code_exempt_groups'] ) ) )
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Sets global advertising code for display
	 *
	 * @access	public
	 *
	 * @param	string		$position		header or footer
	 * @param	string		$code			Key for code to be displayed at specified position
	 * @return	@e void 
	 */
	public function setGlobalCode( $position, $code )
	{
		$code = $this->settings[ $code ];
		
		if( $position == 'header' )
		{
			$this->headerCode = $code;
		}
		else if( $position == 'footer' )
		{
			$this->footerCode = $code;
		}
	}
	
	/**
	 * Retrieves global advertising code for display
	 *
	 * @access	public
	 *
	 * @param	string		$position		header or footer
	 * @param	string		$code			Code to be displayed at specified position
	 * @return	@e void 
	 */
	public function getGobalCode( $position )
	{
		if( $position == 'header' )
		{
			return $this->headerCode;
		}
		else if( $position == 'footer' )
		{
			return $this->footerCode;
		}
	}
	
	/**
	 * Get Ad Code
	 * This is really just a passthrough so we can extend
	 *
	 * @param	string	Key
	 */
	public function getAdCode( $key )
	{
		return $this->settings[ $key ];
	}
}

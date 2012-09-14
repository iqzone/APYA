<?php

class boardIndexCalendar
{
	public $registry;
	
	public function __construct()
	{
        /* Make registry objects */
		$this->registry		=  ipsRegistry::instance();
		$this->DB			=  $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->lang			=  $this->registry->getClass('class_localization');
	}
	
	public function getOutput()
	{
		/* Make sure the calednar is installed and enabled */
		if( ! IPSLib::appIsInstalled( 'calendar' ) )
		{
			return '';
		}
		
		/* Load language  */
		$this->registry->class_localization->loadLanguageFile( array( 'public_calendar' ), 'calendar' );

		/* Load calendar library */
		$classToLoad = IPSLib::loadActionOverloader( IPSLib::getAppDir( 'calendar' ) .'/modules_public/calendar/view.php', 'public_calendar_calendar_view' );
		$cal = new $classToLoad();
		$cal->makeRegistryShortcuts( $this->registry );
		
		if( !$cal->initCalendar( true ) )
		{
			return '';
		}

		/* Return calendar */
		return "<div id='hook_calendar' class='calendar_wrap'>". $cal->getMiniCalendar( date('n'), date('Y') ) . '</div><br />';
	}
}
<?php
/**
 * @file		hourly.php 	Task to remove hourly the bandwidth used by each member 
 *~TERABYTE_DOC_READY~
 * $Copyright: (c) 2001 - 2011 Invision Power Services, Inc.$
 * $License: http://www.invisionpower.com/company/standards.php#license$
 * $Author: mmecham $
 * @since		-
 * $LastChangedDate: 2011-05-13 05:01:26 -0400 (Fri, 13 May 2011) $
 * @version		v4.2.1
 * $Revision: 8756 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 *
 * @class		task_item
 * @brief		Task to remove hourly the bandwidth used by each member
 *
 */
class task_item
{
	/**
	 * Limit on the number of temporary images to delete for each call
	 *
	 * @var		$loadLimit
	 */
	protected $loadLimit = 100;
	
	/**
	 * Object that stores the parent task manager class
	 *
	 * @var		$class
	 */
	protected $class;
	
	/**
	 * Array that stores the task data
	 *
	 * @var		$task
	 */
	protected $task = array();
	
	/**
	 * Registry Object Shortcuts
	 *
	 * @var		$registry
	 * @var		$DB
	 * @var		$settings
	 * @var		$lang
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $lang;
	
	/**
	 * Constructor
	 *
	 * @param	object		$registry		Registry object
	 * @param	object		$class			Task manager class object
	 * @param	array		$task			Array with the task data
	 * @return	@e void
	 */
	public function __construct( ipsRegistry $registry, $class, $task )
	{
		/* Make registry objects */
		$this->registry	= $registry;
		$this->DB		= $this->registry->DB();
		$this->settings	=& $this->registry->fetchSettings();
		$this->lang		= $this->registry->getClass('class_localization');
		
		$this->class	= $class;
		$this->task		= $task;
	}
	
	/**
	 * Run this task
	 *
	 * @return	@e void
	 */
	public function runTask()
	{
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_gallery' ), 'gallery' );
		
		/* Time to delete some bandwidth logged */
		$bwCutoff = time() - ( $this->settings['gallery_bandwidth_period'] * 3600 );
				
		$this->DB->delete( 'gallery_bandwidth', "bdate < " . intval( $bwCutoff ) );
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_bw_trimmed'] );
		
		/* Now let's sort out the old temporary images */
		$imgCutoff = time() - 86400;
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'gallery_images_uploads',
								 'where'  => 'upload_date < ' . $imgCutoff,
								 'limit'  => array( 0, $this->loadLimit )
						 )		);
		$outer = $this->DB->execute();
		
		# Got results?
		if ( $this->DB->getTotalRows( $outer ) )
		{
			$deleteIds = array();
			
			# Load our library
			if ( !ipsRegistry::isClassLoaded('gallery') )
			{
				$classToLoad = IPSLib::loadLibrary( IPSLib::getAppDir('gallery') . '/sources/classes/gallery.php', 'ipsGallery', 'gallery' );
				$this->registry->setClass( 'gallery', new $classToLoad( $this->registry ) );
			}
			
			#Finally process removal
			while( $row = $this->DB->fetch( $outer ) )
			{
				# Remap and delete
				$row = $this->registry->gallery->helper('upload')->_remapAsImage($row);
				
				if ( $this->registry->gallery->helper('moderate')->removeImageFiles($row) )
				{
					$deleteIds[] = "'{$row['id']}'";
				}
			}
			
			if ( count($deleteIds) )
			{
				$this->DB->delete( 'gallery_images_uploads', 'upload_key IN (' . implode(',', $deleteIds) . ')' );
				
				$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_tmp_images_gone'], count($deleteIds) ) );
			}
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}
<?php

/**
 * <pre>
 * Codebit.org
 * IP.Board v3.3.0
 * @description
 * Last Updated: $Date: 14-may-2012 -006  $
 * </pre>
 * @filename            metauris.php
 * @author 		$Author: juliobarreraa@gmail.com $
 * @package		PRI
 * @subpackage	        
 * @link		http://www.codebit.org
 * @since		14-may-2012
 * @timestamp           17:00:43
 * @version		$Rev:  $
 *
 */

ini_set('display_errors', 1);
class public_portal_ajax_metauris extends ipsAjaxCommand {
	private $link;
	private $string;
    public function doExecute(ipsRegistry $registry) {
        $this->link = $this->convertAndMakeSafe($this->request['url']);
        
        $this->link = $this->checkValues();
        
       $this->string = $this->fetch_record($this->link);
       
       $response = $this->getMetaData(); 
        
       return $this->returnJsonArray(array('status' => 'success', 'response' => $response));
    }
    
	
	function checkValues()
	{
		$value = trim($this->link);
		if (get_magic_quotes_gpc()) 
		{
			$value = stripslashes($value);
		}
		$value = strtr($value, array_flip(get_html_translation_table(HTML_ENTITIES)));
		$value = strip_tags($value);
		$value = htmlspecialchars($value);
		return $value;
	}	
	
	function fetch_record($path)
	{
		$file = fopen($path, "r"); 
		if (!$file)
		{
			exit("Problem occured");
		} 
		$data = '';
		while (!feof($file))
		{
			$data .= fgets($file, 1024);
		}
		return $data;
	}
	
	function getMetaData($type='') {
		switch($type) {
			case 'title':
				return $this->getTitle();
			break;
			case 'description':
				return $this->getDescription();
			break;
			case 'images':
			 	return $this->getImages();
			break;
			default:
				$response['title'] = $this->getTitle();
				$response['description'] = $this->getDescription();
			 	$response['images'] = $this->getImages();
			 	$response['total_images'] = count($this->getImages());
			 	return $response;
		}
	}
	
	
	function getTitle() {
		/// fecth title
		$title_regex = "/<title>(.+)<\/title>/i";
		preg_match_all($title_regex, $this->string, $title, PREG_PATTERN_ORDER);
		$url_title = $title[1];
		return base64_encode($url_title[0]);
	}
	
	function getDescription() {
		/// fecth decription
		$tags = get_meta_tags($this->link);
		return base64_encode($tags['description']);

	}
	
	function getImages(){
		// fetch images
		$image_regex = '/<img[^>]*'.'src=[\"|\'](.*)[\"|\']/Ui';
		preg_match_all($image_regex, $this->string, $img, PREG_PATTERN_ORDER);
		$images_array = $img[1];
		$images = array();
		
		$k=1;
		for ($i=0;$i<=sizeof($images_array);$i++)
		{
			$img = trim(@$images_array[$i]);
			if(@$images_array[$i])
			{
				if(@getimagesize(@$images_array[$i]))
				{
					list($width, $height, $type, $attr) = getimagesize(@$images_array[$i]);
					if($width >= 50 && $height >= 50 ){
						$k++;
						$images[$i] = array("img" => $img, "width" => $width, "height" => $height, 'area' =>  ($width * $height),'offset' => '');
					}
				}
			}
		}
		return $images;
	}
	
}
?>

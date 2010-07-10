<?php
/**
* Thumbnail Helper 
*
* @author Tom Maiaroto
* @website http://www.shift8creative.com
* @modified 2010-06-04 15:50:22 
* @created 2010-06-04 14:26:21 
*
*/
namespace minerva\extensions\helper;
use \SplFileInfo;
defined('DS') ? null : define('DS', DIRECTORY_SEPARATOR); 	
		
class Thumbnail extends \lithium\template\Helper {

	var $ext;
	var $source;
	var $destination;
	var $webroot;
	
	
	/*public function _init() {		
		parent::_init();
		$this->_context->Html->applyFilter('image', function($self, $params, $chain) {
			var_dump($params); 
		});
	}*/
	
	/**
	 * @param $source String[required] Location of the source image
	 * @param $options Array[required] Options that change the size and cropping method of the image
	 * 		- size Array[optional] Size of the thumbnail. Default: 75x75
	 * 		- quality Int[optional] Quality of the thumbnail. Default: 85
	 * 		- crop Boolean[optional] Whether to crop the image (when one dimension is larger than specified $size)
	 * 		- letterbox Mixed[optional] If defined, it needs to be an array that defines the RGB background color to use. So when crop is set to false, this will fill in the rest of the image with a background color. Note: Transparent images will have a transparent letterbox unless forced.
	 *		- force_letterbox_color Boolean[optional] Whether or not to force the letterbox color on images with transparency (gif and png images). Default: false (false meaning their letterboxes will be transparent, true meaning they get a colored letterbox which also floods behind any transparent/translucent areas of the image).
	 *		- sharpen Boolean[optional] Whether to sharpen the image version or not. Default: true (note: png and gif images are not sharpened because of possible problems with transparency).	 	 
	 *
	 * @param $htmlOptions Array[optional] Options to pass to the Html helper's image() method
	 * @return String Html code to embed image returned from the Html helper's image() method
	 */
	public function image($source=null, $options = array('size'=>array(75, 75), 'quality'=>85, 'crop'=>false, 'letterbox'=>null, 'force_letterbox_color'=>false, 'sharpen'=>true), $htmlOptions = array()) {	
		$defaults = array('size' => array(75, 75), 'quality' => 85, 'crop' => false, 'letterbox' => null, 'sharpen' => true, 'force_letterbox_color' => false);
		$options += $defaults;
		if(is_string($options['letterbox'])) { $options['letterbox'] = $this->_html2rgb($options['letterbox']); }
		
		// if no source provided, don't do anything
		if(empty($source)) { 
			return false; 
		}
		
		// set webroot path
		$this->webroot = LITHIUM_APP_PATH . DS . 'webroot';		
		
		// set the size
		$thumb_size_x = $original_thumb_size_x = $options['size'][0];
		$thumb_size_y = $original_thumb_size_y = $options['size'][1];		 
						
		// round the thumbnail quality in case someone provided a decimal
		$options['quality'] = ceil($options['quality']);
		// or if a value was entered beyond the extremes
		if($options['quality'] > 100) { 
			$options['quality'] = 100; 
		}
		if($options['quality'] < 0) { 
			$options['quality'] = 0; 
		}
		
		// get full path of source file		
		$this->source = $this->webroot . $source; // absolute path			
		$source_info = new SplFileInfo($this->source);
				
		// if the source file doesn't exist, don't do anything
		if(!file_exists($this->source)) { 
			return false;
		}
				
		// Create the path // TODO: Abstract to possibly allow saving to mongodb...or amazon s3, etc.
		$this->_createPath($source_info->getPath().DS.$options['size'][0].'x'.$options['size'][1]);
		
		// get the destination where the new file will be saved (including file name)		
		$this->destination = $source_info->getPath().DS.$thumb_size_x.'x'.$thumb_size_y.DS.$source_info->getFilename();						
		
		if(preg_match('/([^\.]+$)/', $source, $matches)) {
			$this->ext = $matches[0];
		}
		
	    // First make sure it's an image that we can use (bmp support isn't added, but could be)
		switch(strtolower($this->ext)):
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'png':
			break;
			default:
				return false;
			break;
		endswitch;
		
		// Then see if the size version already exists and if so, is it older than our source image?
		if(file_exists($this->destination)) {
			$existingFile = new SplFileInfo($this->destination);
			if($existingFile->getMTime() > $source_info->getMTime()) {
				// if it's newer than the source, return the path. the source hasn't updated, so we don't need a new thumbnail.			
				//return substr($this->destination, strlen($this->webroot));				
				return $this->_context->helper('Html')->image(substr($this->destination, strlen($this->webroot)), $htmlOptions);
			}
		}
					
		// Get source image dimensions		
		list($width, $height) = getimagesize($this->source);

		// $x and $y here are the image source offsets
		$x = NULL;
		$y = NULL;
		$dx = $dy = 0;		
		
		if(($thumb_size_x > $width) && ($thumb_size_y > $height)) {
			$crop = false; // don't need to crop now do we?
		}
				
		// don't allow new width or height to be greater than the original
		if( $thumb_size_x > $width ) { $thumb_size_x = $width; }
		if( $thumb_size_y > $height ) { $thumb_size_y = $height; }		
		// generate new w/h if not provided (cool, idiot proofing)
		if( $thumb_size_x && !$thumb_size_y ) {
			$thumb_size_y = $height * ( $thumb_size_x / $width );
		}
		elseif($thumb_size_y && !$thumb_size_x) {
			$thumb_size_x = $width * ( $thumb_size_y / $height );
		}
		elseif(!$thumb_size_x && !$thumb_size_y) {
			$thumb_size_x = $width;
			$thumb_size_y = $height;
		}
		
		// set some default values for other variables we set differently based on options like letterboxing, etc.
		// TODO: clean this up and consolidate variables so the image creation process is shorter and nicer
		$new_width = $thumb_size_x;
		$new_height = $thumb_size_y;		
		$x_mid = ceil($new_width/2);  //horizontal middle // TODO: possibly add options to change where the crop is from
		$y_mid = ceil($new_height/2); //vertical middle			
				
		// If the thumbnail is square		
		if($thumbSize[0] == $thumbSize[1]) {
			if($width > $height) {
				$x = ceil(($width - $height) / 2 );
				$width = $height;
			} elseif($height > $width) {
				$y = ceil(($height - $width) / 2);
				$height = $width;
			} 	
		// else if the thumbnail is rectangular, don't stretch it
		} else {
			// if we aren't cropping then keep aspect ratio and contain image within the specified size
			if($crop === false) {
				$ratio_orig = $width/$height;
				if ($thumb_size_x/$thumb_size_y > $ratio_orig) {
				   $thumb_size_x = ceil($thumb_size_y*$ratio_orig);
				} else {
				   $thumb_size_y = ceil($thumb_size_x/$ratio_orig);
				}				
			}			
			// if we are cropping...
			if($crop === true) {		        
		        $ratio_orig = $width/$height;				    
			    if ($thumb_size_x/$thumb_size_y > $ratio_orig) {
			       $new_height = ceil($thumb_size_x/$ratio_orig);
			       $new_width = $thumb_size_x;
			    } else {
			       $new_width = ceil($thumb_size_y*$ratio_orig);
			       $new_height = $thumb_size_y;
			    }			    
			    $x_mid = ceil($new_width/2);  //horizontal middle // TODO: possibly add options to change where the crop is from
			    $y_mid = ceil($new_height/2); //vertical middle			    
			}
		}
		
		// Generate the new image
		$new_im = $this->_generateImage(array('dx' => $dx, 'dy' => $dy, 'x' => $x, 'y' => $y, 'xmid' => $x_mid, 'y_mid' => $y_mid, 'new_width' => $new_width, 'new_height' => $new_height, 'original_thumb_size_x' => $original_thumb_size_x, 'original_thumb_size_y' => $original_thumb_size_y, 'thumb_size_x' => $thumb_size_x, 'thumb_size_y' => $thumb_size_y, 'height' => $height, 'width' => $width, 'letterbox' => $letterbox, 'crop' => $crop, 'sharpen' => $sharpen, 'force_letterbox_color' => $force_letterbox_color));
				
		// Save to disk				
		switch(strtolower($this->ext)) {
			case 'png':				
				if($options['quality'] != 0) {		
					$options['quality'] = ($options['quality'] - 100) / 11.111111;
					$options['quality'] = round(abs($options['quality']));
				}
				imagepng($new_im,$this->destination,$options['quality']);	
				imagedestroy($new_im);
			break;		
			case 'gif':		
				imagegif($new_im,$this->destination); // no quality setting
				imagedestroy($new_im);
			break;		
			case 'jpg':
			case 'jpeg':			
				imagejpeg($new_im,$this->destination,$options['quality']);
				imagedestroy($new_im);				
			break;	
			default:
				return false;
			break;	
		}
		
		//return substr($this->destination, strlen($this->webroot));
		return $this->_context->helper('Html')->image(substr($this->destination, strlen($this->webroot)), $htmlOptions);
	}
	
	/**
	 * Process the image
	 *
	 * @param $options Array[required] The options to create and transform the image
	 * @return GD image object
	 */
	private function _generateImage($options = array('dx' => null, 'dy' => null, 'x' => null, 'y' => null, 'x_mid' => null, 'y_mid' => null, 'new_width' => null, 'new_height' => null, 'original_thumb_size_x' => null, 'original_thumb_size_y' => null, 'thumb_size_x' => null, 'thumb_size_y' => null, 'height' => null, 'width' => null, 'letterbox' => null, 'crop' => null, 'sharpen' => null, 'force_letterbox_color' => null)) {
		$type = strtolower($this->ext);
		switch($type) {
			case 'jpg':
			case 'jpeg':
				$im = imagecreatefromjpeg($this->source);
			break;
			case 'png':
				$im = imagecreatefrompng($this->source);
			break;
			case 'gif': 
				$im = imagecreatefromgif($this->source);
			break;
			default:
			case null:
				return false;
			break;
		}
			
		// CREATE THE NEW IMAGE		
		if(!empty($options['letterbox'])) {
			// if letterbox, use the originally passed dimensions (keeping the final image size to whatever was requested, fitting the other image inside this box)
			$new_im = ImageCreatetruecolor($options['original_thumb_size_x'],$options['original_thumb_size_y']);
			// We want to now set the destination coordinates so we center the image (take overal "box" size and divide in half and subtract by final resized image size divided in half)
			$options['dx'] = ceil(($options['original_thumb_size_x'] / 2) - ($options['thumb_size_x'] / 2));
			$options['dy'] = ceil(($options['original_thumb_size_y'] / 2) - ($options['thumb_size_y'] / 2));				
		} else {
			// otherwise, use adjusted resize dimensions
			$new_im = ImageCreatetruecolor($options['thumb_size_x'],$options['thumb_size_y']);
		}
		// If we're cropping, we need to use a different calculated width and height
		if($options['crop'] === true) {
			$cropped_im = imagecreatetruecolor(round($options['new_width']), round($options['new_height']));			
		}
		
		if(($type == 'png') || ($type == 'gif')) {
			$trnprt_indx = imagecolortransparent($im);
			// If we have a specific transparent color that was saved with the image
		      if ($trnprt_indx >= 0) {		   
		        // Get the original image's transparent color's RGB values
		        $trnprt_color = imagecolorsforindex($im, $trnprt_indx);
		        // Allocate the same color in the new image resource
		        $trnprt_indx = imagecolorallocate($new_im, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);		   		       
		        // Completely fill the background of the new image with allocated color.
		        imagefill($new_im, 0, 0, $trnprt_indx);		       
		        // Set the background color for new image to transparent
		        imagecolortransparent($new_im, $trnprt_indx);
		        if(isset($cropped_im)) { imagefill($cropped_im, 0, 0, $trnprt_indx); imagecolortransparent($cropped_im, $trnprt_indx); } // do the same for the image if cropped
		      } elseif($type == 'png') {
		    	// ...a png may, instead, have an alpha channel that determines its translucency...
				
				// Fill the (currently empty) new cropped image with a transparent background
				if(isset($cropped_im)) { 
					$transparent_index = imagecolortransparent($cropped_im); // allocate
					//imagepalettecopy($im, $cropped_im); // Don't need to copy the pallette...
					imagefill($cropped_im, 0, 0, $transparent_index);
					//imagecolortransparent($cropped_im, $transparent_index); // we need this and the next line even?? for all the trouble i went through, i'm leaving it in case it needs to be turned back on.		
					//imagetruecolortopalette($cropped_im, true, 256);
				}
			
				// Fill the new image with a transparent background
				imagealphablending($new_im, false); 
		        // Create/allocate a new transparent color for image
		        $trnprt_indx = imagecolorallocatealpha($new_im, 0, 0, 0, 127); // $trnprt_indx = imagecolortransparent($new_im, imagecolorallocatealpha($new_im, 0, 0, 0, 127)); // seems to be no difference, but why call an extra function?
		        imagefill($new_im, 0, 0, $trnprt_indx); // Completely fill the background of the new image with allocated color.		       
		        imagesavealpha($new_im, true);  // Restore transparency blending
		      }
		}
																
		// PNG AND GIF can have transparent letterbox and that area needs to be filled too (it already is though if it's transparent)		
		if(!empty($letterbox)) {
			$background_color = imagecolorallocate($new_im, 255, 255, 255); // default white
			if((is_array($options['letterbox'])) && (count($options['letterbox']) == 3)) {
				$background_color = imagecolorallocate($new_im, $options['letterbox'][0], $options['letterbox'][1], $options['letterbox'][2]);
			}
			
			// Transparent images like png and gif will show the letterbox color in their transparent areas so it will look weird
			if(($type == 'gif') || ($type == 'png')) {				
				// But we will give the user a choice, forcing letterbox will effectively "flood" the background with that color. 
				if($options['force_letterbox_color'] === true) {
					imagealphablending($new_im, true); 
					if(isset($cropped_im)) { imagefill($cropped_im, 0, 0, $background_color); }
				} else {
					// If the user doesn't force letterboxing color on gif and png, make it transaprent ($trnprt_indx from above)
					$background_color = $trnprt_indx;
				}
			}			
			imagefill($new_im, 0, 0, $background_color);			
		}
							
		// If cropping, we have to set some coordinates
		if($options['crop'] === true) {			
			imagecopyresampled($cropped_im, $im, 0, 0, 0, 0, $options['new_width'], $options['new_height'], $options['width'], $options['height']);
			// if letterbox we may have to set some coordinates as well depending on the image dimensions ($dx, $dy) unless its letterbox style
			if(empty($options['letterbox'])) {			
				imagecopyresampled($new_im, $cropped_im, 0, 0, ($options['x_mid']-($options['thumb_size_x']/2)), ($options['y_mid']-($options['thumb_size_y']/2)), $options['thumb_size_x'], $options['thumb_size_y'], $options['thumb_size_x'], $options['thumb_size_y']);
			} else {
				imagecopyresampled($new_im, $cropped_im,$options['dx'],$options['dy'], ($options['x_mid']-($options['thumb_size_x']/2)), ($options['y_mid']-($options['$thumb_size_y']/2)), $options['thumb_size_x'], $options['thumb_size_y'], $options['thumb_size_x'], $options['thumb_size_y']);
			}			
		} else {
			imagecopyresampled($new_im,$im,$options['dx'],$options['dy'],$options['x'],$options['y'],$options['thumb_size_x'],$options['thumb_size_y'],$options['width'],$options['height']);
		}
			
		// SHARPEN (optional) -- can't sharpen transparent/translucent PNG		
		if(($options['sharpen'] === true) && ($type != 'png') && ($type != 'gif')) {
				$sharpness = $this->_findSharp($options['width'], $options['thumb_size_x']);					
				$sharpenMatrix	= array(
					array(-1, -2, -1),
					array(-2, $sharpness + 12, -2),
					array(-1, -2, -1)
				);
				$divisor = $sharpness;
				$offset	= 0;
				imageconvolution($new_im, $sharpenMatrix, $divisor, $offset);
		}
		return $new_im;
	}
	
	/**
	* Computes for sharpening the image.
	*
	* function from Ryan Rud (http://adryrun.com)
	*/ 
	private function _findSharp($orig, $final) {
		$final	= $final * (750.0 / $orig);
		$a		= 52;
		$b		= -0.27810650887573124;
		$c		= .00047337278106508946;		
		$result = $a + $b * $final + $c * $final * $final;		
		return max(round($result), 0);
	}
	
	/**
	* Deletes a single thumbnail or a directory of thumbnail versions created by the component.
	* Useful during development, or when changing the crop flag or dimensions often to keep tidy.
	* Maybe say a hypothetical CMS has an admin option for a user to change the thumbnail size of
	* a profile photo...well, we might want to run this to clean out the old versions right?
	* Or when a record was deleted containing an image that has a version...afterDelete()...
	*    
	* @param $source String[required] Location of a source image.
	* @param $thumbSize Array[optional] Size of the thumbnail. Default: 75x75
	* @param $clearAll Boolean[optional] Clear all the thumbnails in the same directory. Default: false
	* 
	* @return
	*/
	public function clearCache($source=null, $thumbSize=array(75, 75), $clearAll=false) {
		if((is_null($source)) || (!is_string($source))): return false; endif;
		$webroot = LITHIUM_APP_PATH . DS . 'webroot';
		// take off any beginning slashes (webroot has a trailing one)
		if(substr($source, 0, 1) == '/') { 
			$source = substr($source, 1); 
		}
						
		$file = $webroot . DS . $source;
		$file_info = new SplFileInfo($file);
				
		// Remove the file (doesn't matter if we remove the directory too later on)
		if(file_exists($file_info->getPath().DS.$thumbSize[0].'x'.$thumbSize[1])) {
			if(@unlink($file_info->getPath().DS.$thumbSize[0].'x'.$thumbSize[1].DS.$file_info->getFilename())) {
				// deleted
			} else {
				// could not delete
			}
		}		
	
		// If specified, remove all files within that directory
		if($clearAll === true) {
			if(@unlink($file_info->getPath().DS.$thumbSize[0].'x'.$thumbSize[1])) {
				// deleted
			} else {
				// could not delete
			}
		}	
		return;	
	}
	
	/**
	 * Pass a full path like /var/www/htdocs/minerva/webroot/files
	 * Don't include trailing slash.
	 * 	 
	 * @param $path String[optional]
	 * @return String Path.
	 */
	private function _createPath($path = null) {		
		$directories = explode(DS, $path);
		// If on a Windows platform, define root accordingly (assumes <drive letter>: syntax)
		if (substr($directories[0], -1) == ':') {		
			$root = $directories[0];
			array_shift($directories);
		} else {
			// Initialize root to empty string on *nix platforms
			$root = '';
			// looks to see if a slash was included in the path to begin with and if so it removes it
			if ($directories[0] == '') {
				array_shift($directories);
			}
		}		
		foreach ($directories as $directory) {
			if (!file_exists($root.DS.$directory)) { 
				mkdir($root.DS.$directory);	
			}
			$root = $root.DS.$directory;			
		}
		// put a trailing slash on
		$root = $root.DS;
		return $root;
	}
		
	/**
	 * Converts web hex value into rgb array.
	 *
	 * @param $color String[required] The web hex string (ex. #0000 or 0000)
	 * @return array The rgb array
	 */
    private function _html2rgb($color) {
	    if ($color[0] == '#')
	        $color = substr($color, 1);
	    if (strlen($color) == 6)
	        list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
	    elseif (strlen($color) == 3)
	        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
	    else
	        return false;
	    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);
	    return array($r, $g, $b);
	}

}
?>

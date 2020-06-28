<?php
/**
 * Image class with support for GIF, JPEG and PNG images.
 */
class Image
{
    private $location;
    private $create_from;
    private $image_output;
    private $width;
    private $height;
    private $type;
    public $is_image = false;

    function __construct($location)
    {
        $this->location = $location;

        $this->analyze();
    }

	private function analyze()
	{
		$size = getimagesize($this->location);
		
		if ( $size === FALSE || ($size[2] != IMAGETYPE_GIF && $size[2] != IMAGETYPE_JPEG && $size[2] != IMAGETYPE_PNG ) )
			return;
		
        $this->is_image = true;

		$this->create_from = "imagecreatefromgif";
		$this->image_output = "imagegif";
		
		if( $size[2] == IMAGETYPE_JPEG )
		{
			$this->create_from = "imagecreatefromjpeg";
			$this->image_output = "imagejpeg";
		}
			
		else if( $size[2] == IMAGETYPE_PNG )
		{
			$this->create_from = "imagecreatefrompng";
			$this->image_output = "imagepng";
		}
		
        $this->width = $size[0];
        $this->height = $size[1];
        $this->type = $size[2];
	}
	
    private function create_from($location)
    {
        $func = $this->create_from;

        return $func($location);
    }

    private function image_output($image, $location)
    {
        $func = $this->image_output;

        return $func($image, $location);
    }

	/**
	 * Resizes an image file
	 */
	public function resize($new_location, $size = array(100,100))
	{
		WF::verify(function_exists("imagecreatetruecolor"), 'Missing imagecreatetruecolor function');
        if (file_exists($new_location))
    		WF::verify(is_writable($new_location), 'Not writable');
        else
            WF::verify(is_writable(dirname($new_location)), 'Not writable');
		
		$image = $this->create_from($this->location);
		$this->resize_resource($image, $size);

		// Save
        //
		if (!$this->image_output($image, $new_location))
			return FALSE;
		
		imagedestroy($image);
		
		return TRUE;
	}
	
	/**
	 * Resizes a GD image resource.
	 * 
	 * @param resource The GD resource of the image to resize
	 * @param array An 2D array with the new maximum width and height
	 * @return boolean
	 */
	private function resize_resource(&$image, $size = array(100,100))
	{
		WF::verify(is_resource($image), 'Not a resource');
		WF::verify(is_array($size) && count($size) == 2 && $size[0] > 0 && $size[1] > 0, 'Size not correctly structured');
		
		$width = imagesx($image);
		$height = imagesy($image);
		
		// Current image smaller?
		if( $width < $size[0] && $height < $size[1] )
		{
			$size[0] = $width;
			$size[1] = $height;
		}
		else
		{
			// Resize with the same ratio
			$ratio = $width / $height;
			
			if ($size[0] / $size[1] > $ratio)
			   $size[0] = $size[1] * $ratio;
			else
			   $size[1] = $size[0] / $ratio;
		}
		
		// Create new GD resource
		$image_p = imagecreatetruecolor($size[0], $size[1]);
		
		if ($image === FALSE)
			return FALSE;

		// Support alpha blending in GIF and PNG
		imagealphablending($image_p, FALSE);
		imagesavealpha($image_p, TRUE);
		$transparent = imagecolorallocatealpha($image_p, 255, 255, 255, 127);
		imagefilledrectangle($image_p, 0, 0, $width, $height, $transparent);

		// Copy resized image, destroy original
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $size[0], $size[1], $width, $height);
		imagedestroy($image);
		$image = $image_p;
		
		return TRUE;
	}
	
	/**
	 * Rotate an image file
	 *
	 * @param string $file An absolute filepath to the original image
	 * @param string $newFile An absolute path where the resized image will be saved
	 * @param int $degrees How many degrees to rotate
	 * @param int RGB-color
	 * @return boolean
	 */
	public function rotate($new_location, $degrees, $backgroundColor = 16777215)
	{
		WF::verify(function_exists("imagecreatetruecolor"), 'function imagecreatetruecolor() missing');
		WF::verify(is_writable($new_location), 'Not writable');
		WF::verify(is_int($degrees) && abs($degrees) <= 360, 'Invalid rotation');
		
		// Create new GD resource
		$image = $this->create_from($this->location);
		
		if ($image === FALSE)
			return FALSE;

		$this->rotate_resource($image, $degrees, $backgroundColor);

		// Save
		if( !$this->image_output($image, $new_location) )
			return FALSE;
		
		imagedestroy($image);
		
		return TRUE;
	}
	
	/**
	 * Rotate a GD image resource.
	 * 
	 * @param resource $resource The GD resource of the image to rotate
	 * @param int $degrees How many degrees to rotate
	 * @param int $backgroundColor Backgroundcolor as a raw int
	 * @return boolean
	 */
	private function rotateResource(&$image, $degrees, $backgroundColor = 16777215)
	{
		WF::verify(function_exists("imagecreatetruecolor"), 'function imagecreatetruecolor() missing');
		WF::verify(is_resource($image), 'Not a resource');
		WF::verify(is_int($degrees) && abs($degrees) <= 360, 'Invalid rotation');

		imageantialias($image, true);
		$rotate = imagerotate($image, $degrees, $backgroundColor);
		imagedestroy($image);
		$image = $rotate;
		
		return TRUE;
	}

	/**
	 * Crop an image file
	 *
	 * @param string $file An absolute filepath to the original image
	 * @param string $newFile Absolute filepath to the new location
	 * @param array $newSize The new size
	 * @param array $offset Offset 
	 * @return boolean
	 */
	public function crop($new_location, $size, $offset = array(0,0))
	{
		WF::verify(function_exists("imagecreatetruecolor"), 'function imagecreatetruecolor() missing');
		WF::verify(is_writable($new_location), 'Not writable');
		
		// Create new GD resource
		$image = $this->create_from($this->location);
		if ($image === FALSE)
			return FALSE;

		$this->crop_resource($image, $size, $offset);

		// Save
		if (!$imageoutput($image, $newFile) )
			return false;
		
		imagedestroy($image);
		
		return true;
	}

	/**
	 * Crop a GD image resource.
	 * 
	 * @param resource $resource The GD resource of the image to crop
	 * @param array $newSize The new size
	 * @param array $offset Offset 
	 * @return boolean
	 */
	public static function cropResource(&$image, $newSize, $offset = array(0,0) )
	{
		WF::verify(function_exists("imagecreatetruecolor"), 'function imagecreatetruecolor() missing');
		WF::verify(is_resource($image), 'Not a resource');
		WF::verify(is_array($newSize) && count($newSize) == 2 && $newSize[0] > 0 && $newSize[1] > 0, 'Size not correctly structured');
		WF::verify(is_array($offset) && count($offset) == 2 && $offset[0] >= 0 && $offset[1] >= 0 && $offset[0] < $newSize[0] && $offset[1] < $newSize[1], 'Offset not correctly structured');

		$dest = imagecreatetruecolor($newSize[0], $newSize[1]);
		$result = imagecopy($dest, $image, 0, 0, $offset[0], $offset[1], $newSize[0], $newSize[1]);
		imagedestroy($image);
		$image = $dest;
		
		return $result;
	}
	
	/**
	 * Convert a GIF/JPEG/PNG image to the specified format.
	 * The converted imagefile will be saved in the same directory, with a different extension
	 *
	 * @param string Absolute local imagefile
	 * @param string Absolute local path to new file
	 * @return boolean
	 */
	public function convert($new_file)
	{
		$ext = strtolower( substr(strrchr($new_file, "."), 1) );
		if( !in_array($ext, array("png", "gif", "jpeg")))
			return false;
		
		// Create new GD resource
		$image = $this->create_from($this->location);
		if ($image === FALSE)
			return FALSE;

		switch($ext)
		{
			case "png":
				imagepng($image, $new_file );
				break;
			
			case "gif":
				imagegif($image, $new_file );
				break;
			
			case "jpeg":
				imagejpeg($image, $new_file );
				break;
		}
		
		imagedestroy($image);
	}
};

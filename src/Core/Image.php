<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Image class with support for GIF, JPEG and PNG images.
 */
class Image
{
    private int $width;
    private int $height;
    private int $type;
    public bool $isImage = false;

    /**
     * Image constructor.
     *
     * @param string $location The file path of the image
     */
    public function __construct(
        private string $location,
    ) {}

    /**
     * Analyze the image file to determine its properties.
     */
    public function analyze(): void
    {
        $size = getimagesize($this->location);

        if ($size === false || ($size[2] != IMAGETYPE_GIF && $size[2] != IMAGETYPE_JPEG && $size[2] != IMAGETYPE_PNG))
        {
            return;
        }

        $this->isImage = true;
        $this->width = $size[0];
        $this->height = $size[1];
        $this->type = $size[2];
    }

    /**
     * Create a GD image resource from the file.
     *
     * @param string $location The file path of the image
     *
     * @return false|\GdImage The GD image resource or false on failure
     *
     * @throws \InvalidArgumentException If the image type is unknown
     */
    private function createFrom(string $location): false|\GdImage
    {
        if ($this->type == IMAGETYPE_GIF)
        {
            return imagecreatefromgif($location);
        }

        if ($this->type == IMAGETYPE_JPEG)
        {
            return imagecreatefromjpeg($location);
        }

        if ($this->type == IMAGETYPE_PNG)
        {
            return imagecreatefrompng($location);
        }

        throw new \InvalidArgumentException('Unknown image type');
    }

    /**
     * Output the GD image to a file.
     *
     * @param \GdImage $image    The GD image resource
     * @param string   $location The output file path
     *
     * @return bool True on success, false on failure
     *
     * @throws \InvalidArgumentException If the image type is unknown
     */
    private function imageOutput(\GdImage $image, string $location): bool
    {
        if ($this->type == IMAGETYPE_GIF)
        {
            return imagegif($image, $location);
        }

        if ($this->type == IMAGETYPE_JPEG)
        {
            return imagejpeg($image, $location);
        }

        if ($this->type == IMAGETYPE_PNG)
        {
            return imagepng($image, $location);
        }

        throw new \InvalidArgumentException('Unknown image type');
    }

    /**
     * Get the width of the image.
     *
     * @return int The image width
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get the height of the image.
     *
     * @return int The image height
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Get the size of the image.
     *
     * @return array{0: int, 1: int} An array containing the width and height
     */
    public function getSize(): array
    {
        return [$this->width, $this->height];
    }

    /**
     * Get the type of the image.
     *
     * @return int The image type constant
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Resize an image file.
     *
     * @param string                $newLocation The output file path
     * @param array{0: int, 1: int} $size        The new size [width, height]
     *
     * @return bool True on success, false on failure
     *
     * @throws \RuntimeException If the output location is not writable
     */
    public function resize(string $newLocation, array $size = [100, 100]): bool
    {
        if (file_exists($newLocation))
        {
            if (!is_writable($newLocation))
            {
                throw new \RuntimeException('Not writable');
            }
        }
        else
        {
            if (!is_writable(dirname($newLocation)))
            {
                throw new \RuntimeException('Not writable');
            }
        }

        $image = $this->createFrom($this->location);
        $this->resizeImage($image, $size);

        // Save
        if (!$this->imageOutput($image, $newLocation))
        {
            return false;
        }

        imagedestroy($image);

        return true;
    }

    /**
     * Resize a GD image.
     *
     * @param \GdImage             $image The GD image resource
     * @param array{0: int, 1:int} $size  The new size [width, height]
     *
     * @return bool True on success, false on failure
     *
     * @throws \InvalidArgumentException If the size is not correctly structured
     */
    private function resizeImage(\GdImage &$image, array $size = [100, 100]): bool
    {
        if (count($size) !== 2 || $size[0] <= 0 || $size[1] <= 0)
        {
            throw new \InvalidArgumentException('Size not correctly structured');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Current image smaller?
        if ($width < $size[0] && $height < $size[1])
        {
            $size[0] = $width;
            $size[1] = $height;
        }
        else
        {
            // Resize with the same ratio
            $ratio = $width / $height;

            if ($size[0] / $size[1] > $ratio)
            {
                $size[0] = (int) ($size[1] * $ratio);
            }
            else
            {
                $size[1] = (int) ($size[0] / $ratio);
            }
        }

        // Create new GD image
        $imageP = imagecreatetruecolor($size[0], $size[1]);

        if ($imageP === false)
        {
            return false;
        }

        // Support alpha blending in GIF and PNG
        imagealphablending($imageP, false);
        imagesavealpha($imageP, true);
        $transparent = imagecolorallocatealpha($imageP, 255, 255, 255, 127);
        if ($transparent === false)
        {
            return false;
        }
        imagefilledrectangle($imageP, 0, 0, $width, $height, $transparent);

        // Copy resized image, destroy original
        imagecopyresampled($imageP, $image, 0, 0, 0, 0, $size[0], $size[1], $width, $height);
        imagedestroy($image);
        $image = $imageP;

        return true;
    }

    /**
     * Rotate an image file.
     *
     * @param string $newLocation     The output file path
     * @param int    $degrees         How many degrees to rotate
     * @param int    $backgroundColor RGB-color for the background
     *
     * @return bool True on success, false on failure
     *
     * @throws \RuntimeException         If the output location is not writable
     * @throws \InvalidArgumentException If the rotation angle is invalid
     */
    public function rotate(string $newLocation, int $degrees, int $backgroundColor = 16777215): bool
    {
        if (!is_writable($newLocation))
        {
            throw new \RuntimeException('Not writable');
        }

        if (abs($degrees) > 360)
        {
            throw new \InvalidArgumentException('Invalid rotation');
        }

        // Create new GD image
        $image = $this->createFrom($this->location);

        if ($image === false)
        {
            return false;
        }

        $this->rotateImage($image, $degrees, $backgroundColor);

        // Save
        if (!$this->imageOutput($image, $newLocation))
        {
            return false;
        }

        imagedestroy($image);

        return true;
    }

    /**
     * Rotate a GD image.
     *
     * @param \GdImage $image           The GD image resource
     * @param int      $degrees         How many degrees to rotate
     * @param int      $backgroundColor Background color as a raw int
     *
     * @return bool True on success, false on failure
     *
     * @throws \InvalidArgumentException If the rotation angle is invalid
     */
    private function rotateImage(\GdImage &$image, int $degrees, int $backgroundColor = 16777215): bool
    {
        if (abs($degrees) > 360)
        {
            throw new \InvalidArgumentException('Invalid rotation');
        }

        imageantialias($image, true);
        $rotate = imagerotate($image, $degrees, $backgroundColor);
        imagedestroy($image);
        $image = $rotate;

        return true;
    }

    /**
     * Crop an image file.
     *
     * @param string                $newLocation Absolute filepath to the new location
     * @param array{0: int, 1: int} $size        The new size [width, height]
     * @param array{0: int, 1: int} $offset      Offset [x, y]
     *
     * @return bool True on success, false on failure
     *
     * @throws \RuntimeException If the output location is not writable
     */
    public function crop(string $newLocation, array $size, array $offset = [0, 0]): bool
    {
        if (!is_writable($newLocation))
        {
            throw new \RuntimeException('Not writable');
        }

        // Create new GD image
        $image = $this->createFrom($this->location);
        if ($image === false)
        {
            return false;
        }

        $this->cropImage($image, $size, $offset);

        // Save
        if (!$this->imageOutput($image, $newLocation))
        {
            return false;
        }

        imagedestroy($image);

        return true;
    }

    /**
     * Crop a GD image.
     *
     * @param \GdImage              $image  The GD image resource
     * @param array{0: int, 1: int} $size   The new size [width, height]
     * @param array{0: int, 1: int} $offset Offset [x, y]
     *
     * @return bool True on success, false on failure
     *
     * @throws \InvalidArgumentException If the size or offset is not correctly structured
     */
    public static function cropImage(\GdImage &$image, array $size, array $offset = [0, 0]): bool
    {
        if (count($size) != 2 || $size[0] <= 0 || $size[1] <= 0)
        {
            throw new \InvalidArgumentException('Size not correctly structured');
        }

        if (count($offset) != 2 || $offset[0] < 0 || $offset[1] < 0 || $offset[0] >= $size[0] || $offset[1] >= $size[1])
        {
            throw new \InvalidArgumentException('Offset not correctly structured');
        }

        $dest = imagecreatetruecolor($size[0], $size[1]);
        if ($dest === false)
        {
            return false;
        }

        $result = imagecopy($dest, $image, 0, 0, $offset[0], $offset[1], $size[0], $size[1]);
        imagedestroy($image);
        $image = $dest;

        return $result;
    }

    /**
     * Convert a GIF/JPEG/PNG image to the specified format.
     *
     * @param string $newFile Absolute local path to new file
     *
     * @return bool True on success, false on failure
     */
    public function convert(string $newFile): bool
    {
        $ext = strtolower(substr(strrchr($newFile, '.'), 1));
        if (!in_array($ext, ['png', 'gif', 'jpeg']))
        {
            return false;
        }

        // Create new GD image
        $image = $this->createFrom($this->location);
        if ($image === false)
        {
            return false;
        }

        $result = false;

        switch ($ext)
        {
            case 'png':
                $result = imagepng($image, $newFile);

                break;

            case 'gif':
                $result = imagegif($image, $newFile);

                break;

            case 'jpeg':
                $result = imagejpeg($image, $newFile);

                break;
        }

        imagedestroy($image);

        return $result;
    }
}

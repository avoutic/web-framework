<?php

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

    public function __construct(
        private AssertService $assertService,
        private string $location,
    ) {
    }

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

    private function createFrom(string $location): \GdImage|false
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

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * @return array{0: int, 1: int}
     */
    public function getSize(): array
    {
        return [$this->width, $this->height];
    }

    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Resizes an image file.
     *
     * @param array{0: int, 1: int} $size
     */
    public function resize(string $newLocation, array $size = [100, 100]): bool
    {
        if (file_exists($newLocation))
        {
            $this->assertService->verify(is_writable($newLocation), 'Not writable');
        }
        else
        {
            $this->assertService->verify(is_writable(dirname($newLocation)), 'Not writable');
        }

        $image = $this->createFrom($this->location);
        $this->resizeImage($image, $size);

        // Save
        //
        if (!$this->imageOutput($image, $newLocation))
        {
            return false;
        }

        imagedestroy($image);

        return true;
    }

    /**
     * Resizes a GD image.
     *
     * @param \GdImage             $image The GD image of the image to resize
     * @param array{0: int, 1:int} $size  An 2D array with the new maximum width and height
     *
     * @return bool
     */
    private function resizeImage(\GdImage &$image, array $size = [100, 100])
    {
        $this->assertService->verify(count($size) == 2 && $size[0] > 0 && $size[1] > 0, 'Size not correctly structured', \InvalidArgumentException::class);

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
                $size[0] = $size[1] * $ratio;
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
     * @param string $newLocation     An absolute path where the resized image will be saved
     * @param int    $degrees         How many degrees to rotate
     * @param int    $backgroundColor RGB-color
     */
    public function rotate(string $newLocation, int $degrees, int $backgroundColor = 16777215): bool
    {
        $this->assertService->verify(is_writable($newLocation), 'Not writable');
        $this->assertService->verify(abs($degrees) <= 360, 'Invalid rotation', \InvalidArgumentException::class);

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
     * Rotate a GD image image.
     *
     * @param \GdImage $image           The GD image of the image to rotate
     * @param int      $degrees         How many degrees to rotate
     * @param int      $backgroundColor Backgroundcolor as a raw int
     */
    private function rotateImage(\GdImage &$image, int $degrees, int $backgroundColor = 16777215): bool
    {
        $this->assertService->verify(abs($degrees) <= 360, 'Invalid rotation', \InvalidArgumentException::class);

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
     * @param array{0: int, 1: int} $size        The new size
     * @param array{0: int, 1: int} $offset      Offset
     */
    public function crop(string $newLocation, array $size, array $offset = [0, 0]): bool
    {
        $this->assertService->verify(is_writable($newLocation), 'Not writable');

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
     * Crop a GD image image.
     *
     * @param \GdImage              $image  The GD image of the image to crop
     * @param array{0: int, 1: int} $size   The new size
     * @param array{0: int, 1: int} $offset Offset
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
     * The converted imagefile will be saved in the same directory, with a different extension.
     *
     * @param string $newFile Absolute local path to new file
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

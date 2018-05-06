<?php declare(strict_types=1);

namespace Thumbnail;

use Nette\StaticClass;
use Nette\Utils\Finder;
use Nette\Utils\Image;


/**
 * Class Thumbnail
 *
 * @author  geniv
 * @package Thumbnail
 */
class Thumbnail
{
    use StaticClass;

    /** @var array */
    private static $parameters = [];


    /**
     * Thumbnail constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        self::$parameters = $parameters;
    }


    //TODO metodu statickou na synchronizaci konktretni slozky s filesystemem!


    /**
     * Clean thumbnail.
     *
     * @return int
     */
    public static function cleanThumbnail(): int
    {
        $poc = 0;
        $files = Finder::findFiles('*')->in(self::$parameters['thumbPath']);
        foreach ($files as $file) {
            if (unlink($file->getPathname())) {
                $poc++;
            }
        }
        return $poc;
    }


    /**
     * Get src path.
     *
     * @param string      $path
     * @param string|null $file
     * @param int|null    $width
     * @param int|null    $height
     * @param array       $flags
     * @param int|null    $quality
     * @return string
     * @throws \Nette\Utils\UnknownImageFileException
     */
    public static function getSrcPath(string $path, string $file = null, int $width = null, int $height = null, array $flags = [], int $quality = null): string
    {
        // create thumbnail dir
        if (!file_exists(self::$parameters['thumbPath'])) {
            mkdir(self::$parameters['thumbPath'], 0777, true);
        }

        $template = self::$parameters['template'];
        if (isset($template[$path])) {
            // resize image by template
            $conf = $template[$path];
            $destination = self::resizeImage($conf['path'], $file, $conf['width'] ?? null, $conf['height'] ?? null, $conf['flags'] ?? [], $conf['quality'] ?? null);
        } else {
            // resize image by path
            $destination = self::resizeImage($path, $file, $width, $height, $flags, $quality);
        }
        return substr($destination, strlen(realpath(self::$parameters['dir'])) + 1);
    }


    /**
     * Get image flag.
     *
     * @param $flags
     * @return int
     */
    private static function getImageFlag($flags): int
    {
        $res = 0;
        foreach ($flags as $flag) {
            $res |= constant(Image::class . '::' . $flag);
        }
        return $res;
    }


    /**
     * Resize image.
     *
     * @param string      $path
     * @param string|null $file
     * @param int|null    $width
     * @param int|null    $height
     * @param array       $flags
     * @param int|null    $quality
     * @return string
     * @throws \Nette\Utils\UnknownImageFileException
     */
    private static function resizeImage(string $path, string $file = null, int $width = null, int $height = null, array $flags = [], int $quality = null)
    {
        if ($flags) {
            $flag = self::getImageFlag($flags);
        } else {
            $flag = Image::SHRINK_ONLY;
        }

        $src = self::$parameters['dir'] . $path . $file;
        if (!is_file($src) || !file_exists($src)) {
            // create no image path - if no file or no exists
            $src = self::$parameters['dir'] . self::$parameters['noImage'];
        }
        // get path name from src
        $pathInfo = pathinfo($src);

        $destination = self::$parameters['thumbPath'] . $pathInfo['filename'] . 'w' . $width . 'h' . $height . 'f' . $flag . 'q' . $quality . '.' . $pathInfo['extension'];
        if (file_exists($src) && !file_exists($destination)) {
            $image = Image::fromFile($src);
            if ($width || $height) {
                $image->resize($width, $height, $flag);
            }
            $image->save($destination, $quality);
        }
        return $destination;
    }
}

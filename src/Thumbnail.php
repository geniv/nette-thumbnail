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


    /**
     * Synchronize thumbnail.
     *
     * @param array $path
     * @return array
     */
    public static function synchronizeThumbnail(array $path): array
    {
        $result = [];
        // load thumbnail files
        $thumbFiles = [];
        $thumbFinder = Finder::findFiles('*')->in(self::$parameters['thumbPath']);
        foreach ($thumbFinder as $file) {
            $basename = $file->getBaseName();
            $lastDelimiter = strrpos($basename, '_');
            $lastDot = strrpos($basename, '.');
            // restore old name
            $thumbFiles[$file->getPathname()] = substr($basename, 0, $lastDelimiter) . substr($basename, $lastDot);
        }

        // remove duplicate files
        $counts = array_count_values($thumbFiles);
        $duplicate = array_filter($counts, function ($row) { return $row > 1; });
        foreach ($duplicate as $item => $count) {
            $pathInfo = pathinfo($item);
            $duplicateFinder = Finder::findFiles($pathInfo['filename'] . '*')->in(self::$parameters['thumbPath']);
            foreach ($duplicateFinder as $duplicateItem) {
                if (unlink($duplicateItem->getPathname())) {
                    $result[] = $duplicateItem->getPathname();
                }
            }
        }

        // load external files
        $pathFiles = [];
        $pathFinder = Finder::findFiles('*')->in($path);
        foreach ($pathFinder as $file) {
            $pathFiles[$file->getPathname()] = $file->getBaseName();
        }

        // remove different files
        $diff = array_diff($thumbFiles, $pathFiles);
        foreach ($diff as $oldName => $file) {
            if (unlink($oldName)) {
                $result[] = $oldName;
            }
        }
        return $result;
    }


    /**
     * Clean thumbnail.
     *
     * @return array
     */
    public static function cleanThumbnail(): array
    {
        $result = [];
        $files = Finder::findFiles('*')->in(self::$parameters['thumbPath']);
        foreach ($files as $file) {
            if (unlink($file->getPathname())) {
                $result[] = $file->getPathname();
            }
        }
        return $result;
    }


    /**
     * Get src path.
     *
     * @param string      $path
     * @param string|null $file
     * @param string|null $width
     * @param string|null $height
     * @param array       $flags
     * @param int|null    $quality
     * @return string
     * @throws \Nette\Utils\UnknownImageFileException
     * @throws \Exception
     */
    public static function getSrcPath(string $path, string $file = null, string $width = null, string $height = null, array $flags = [], int $quality = null): string
    {
        // create thumbnail dir
        if (!file_exists(self::$parameters['thumbPath'])) {
            throw new \Exception('Path: ' . self::$parameters['thumbPath'] . ' does not exist!');
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
     * @param null        $width
     * @param null        $height
     * @param array       $flags
     * @param int|null    $quality
     * @return string
     * @throws \Nette\Utils\UnknownImageFileException
     */
    private static function resizeImage(string $path, string $file = null, $width = null, $height = null, array $flags = [], int $quality = null)
    {
        if ($flags) {
            $flag = self::getImageFlag($flags);
        } else {
            $flag = Image::SHRINK_ONLY;
        }

        $src = self::$parameters['dir'] . $path . $file;
        if (!is_file($src) || !file_exists($src)) {
            // if no file or no exists
            return self::$parameters['dir'] . self::$parameters['noImage'];
        }
        // get path name from src
        $pathInfo = pathinfo($src);

        // sanitize name
        $replace = [
            '%' => 'P', // percent
            '/' => 'S', // slash
        ];
        $specialName = str_replace(array_keys($replace), $replace, 'p' . $path . 'w' . $width . 'h' . $height . 'f' . $flag . 'q' . $quality);
        $destination = self::$parameters['thumbPath'] . $pathInfo['filename'] . '_' . $specialName . '.' . $pathInfo['extension'];
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

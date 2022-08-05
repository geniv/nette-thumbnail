<?php declare(strict_types=1);

namespace Thumbnail;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\StaticClass;
use Nette\Utils\Finder;
use Nette\Utils\Image;
use Nette\Utils\UnknownImageFileException;


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
    /** @var Cache */
    private static $cache;


    /**
     * Thumbnail constructor.
     *
     * @param array    $parameters
     * @param IStorage $storage
     */
    public function __construct(array $parameters, IStorage $storage)
    {
        self::$parameters = $parameters;
        self::$cache = new Cache($storage, 'Thumbnail');
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
     * Get thumb files.
     *
     * @return array
     */
    private static function getThumbFiles(): array
    {
        $result = [];
        $thumbFinder = Finder::findFiles('*')->in(self::$parameters['thumbPath']);
        foreach ($thumbFinder as $file) {
            $basename = $file->getBaseName();
            $specialDelimiter = strrpos($basename, '_');
//            $pathEnd = strrpos($basename, 'pew');
//            $prefixPath = null;
//            if ($pathEnd) {
//                $prefixPath = str_replace('S', '/', substr($basename, $specialDelimiter + 2, $pathEnd - strlen($basename)));
//            }
            $lastDot = strrpos($basename, '.');
            // restore old name
            $result[$file->getRealPath()] = substr($basename, 0, $specialDelimiter) . substr($basename, $lastDot);
        }
        return $result;
    }


    /**
     * Get path files.
     *
     * @param array $path
     * @return array
     */
    private static function getPathFiles(array $path): array
    {
        // load external files
        $result = [];
        $pathFinder = Finder::findFiles('*')->in($path);
        foreach ($pathFinder as $file) {
            $result[$file->getRealPath()] = $file->getBaseName();
        }
        return $result;
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

        //TODO rebuild or removed - it was be lite check
//        // remove duplicate files
//        $counts = array_count_values($thumbFiles);
//        $duplicate = array_filter($counts, function ($row) { return $row > 1; });
//        foreach ($duplicate as $item => $count) {
//            $pathInfo = pathinfo($item);
//            $duplicateFinder = Finder::findFiles($pathInfo['filename'] . '*')->in(self::$parameters['thumbPath']);
//            foreach ($duplicateFinder as $duplicateItem) {
//                if (unlink($duplicateItem->getPathname())) {
//                    $result[] = $duplicateItem->getPathname();
//                }
//            }
//        }

        // load thumbnail files
        $thumbFiles = self::getThumbFiles();

        // load path files
        $pathFiles = self::getPathFiles($path);

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
     * Get unused files.
     *
     * @param array $path
     * @return array
     */
    public static function getUnusedFiles(array $path): array
    {
        // load thumbnail files
        $thumbFiles = self::getThumbFiles();

        // load path files
        $pathFiles = self::getPathFiles($path);

        return $diff = array_diff($pathFiles, $thumbFiles);
    }


    /**
     * Is src path exists.
     *
     * @param string      $path
     * @param string|null $file
     * @return bool
     */
    public static function isSrcPathExists(string $path, string $file = null): bool
    {
        $src = self::$parameters['dir'] . $path . $file;
        return file_exists($src) && is_file($src);
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
     * @throws \Exception
     */
    public static function getSrcPath(string $path, string $file = null, string $width = null, string $height = null, array $flags = [], int $quality = null): string
    {
        $cacheName = 'getSrcPath' . $path . $file . $width . $height . implode($flags) . $quality;
        $destination = (self::$parameters['cache'] ? self::$cache->load($cacheName) : null);
        if ($destination === null) {
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

            if (self::$parameters['cache']) {   // only for cache
                try {
                    self::$cache->save($cacheName, $destination, [Cache::FILES => [self::$parameters['dir'] . $path . $file]]);
                } catch (\Throwable $e) {
                }
            }
        }
        $destination = substr($destination, strlen(realpath(self::$parameters['dir'])) + 1);

        if(isset(self::$parameters['thumbUrl'])) {
            $destination = self::$parameters['thumbUrl'] . str_replace('www', '', $destination);
        }
        return $destination;
    }


    /**
     * Get image flag.
     *
     * @param array $flags
     * @return int
     */
    private static function getImageFlag(array $flags): int
    {
        $res = 0;
        foreach ($flags as $flag) {
            $res |= constant(Image::class . '::' . $flag);
        }
        return $res;
    }


    /**
     * Set default image flag.
     *
     * @param int $flag
     */
    public function setDefaultImageFlag(int $flag)
    {
        self::$parameters['defaultFlag'] = $flag;
    }


    /**
     * Set no image.
     *
     * @param string $path
     */
    public function setNoImage(string $path)
    {
        self::$parameters['noImage'] = $path;
    }


    /**
     * Set wait image.
     *
     * @param string|null $path
     */
    public static function setWaitImage(string $path = null)
    {
        self::$parameters['waitImage'] = $path;
    }


    /**
     * Set lazy load.
     *
     * @param bool $state
     */
    public function setLazyLoad(bool $state)
    {
        self::$parameters['lazyLoad'] = $state;
    }


    /**
     * Set cache.
     *
     * @param bool $state
     */
    public function setCache(bool $state)
    {
        self::$parameters['cache'] = $state;
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
     */
    private static function resizeImage(string $path, string $file = null, $width = null, $height = null, array $flags = [], int $quality = null)
    {
        $parameters = self::$parameters;
        if ($flags) {
            $flag = self::getImageFlag($flags);
        } else {
            $flag = $parameters['defaultFlag'];
        }

        $dir = $parameters['dir'];
        $src = $dir . $path . $file;
        if (!is_file($src) || !file_exists($src)) {
            // if no file or no exists
            return $dir . $parameters['noImage'];
        }
        // get path name from src
        $pathInfo = pathinfo($src);

        // sanitize name
        $replace = [
            '%' => 'P', // percent
            '/' => 'S', // slash
        ];
        $mt = filemtime($src);  // modify time source image
        // path, width, height, flag, quality
        $specialName = str_replace(array_keys($replace), $replace, 'p' . $path . 'w' . $width . 'h' . $height . 'f' . $flag . 'q' . $quality . 'mt' . $mt);
        $destination = $parameters['thumbPath'] . $pathInfo['filename'] . '_' . $specialName . '.' . $pathInfo['extension'];

        // quick render src, if width and height are not defined (tip by @hermajan)
        if (file_exists($src) && !$width && !$height) {
            return $src;
        }

        // prepare resize
        if (!file_exists($destination) && file_exists($src)) {
            try {
                $image = Image::fromFile($src);
                if ($width || $height) {
                    $image->resize($width, $height, $flag);
                }
                $image->save($destination, $quality);
            } catch (UnknownImageFileException $e) {
                // if invalid file
                return $dir . $parameters['noImage'];
            }

            // lazy loading - for big count pictures
            if ($parameters['lazyLoad'] && $parameters['waitImage']) {
                // complete image for <img src="...
                die($dir . $parameters['waitImage'] . '">');
            }

            // wait image
            if ($parameters['waitImage']) {
                return $dir . $parameters['waitImage'];
            }
        }
        return $destination;
    }
}

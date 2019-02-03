Thumbnail
=========

Installation
------------

```sh
$ composer require geniv/nette-thumbnail
```
or
```json
"geniv/nette-thumbnail": "^1.0"
```

require:
```json
"php": ">=7.0",
"nette/caching": ">=2.5",
"nette/di": ">=2.4",
"nette/php-generator": ">=2.4",
"nette/utils": ">=2.4",
"latte/latte": ">=2.4"
```

Include in application
----------------------

default resize flag: `SHRINK_ONLY`, via: https://doc.nette.org/cs/2.4/images

Quality
-------
via: https://api.nette.org/2.4/source-Utils.Image.php.html#512-549
- JPEG - 0-100; default: 85
- PNG - 0-9; default: 9
- GIF - nothing
- WEBP - 0-100; default: 80

`lazyLoad: true` is only for `<img src="...">` because it is terminated php after generate one picture. `waitImage` must be defined!

neon configure:
```neon
# thumbnail
thumbnail:
    dir: %wwwDir%/../
    thumbPath: %wwwDir%/files/image/thumbnail/
    noImage: www/images/no-image.svg
#    waitImage: www/images/wait-image.gif
#    lazyLoad: false
#    defaultFlag: Nette\Utils\Image::SHRINK_ONLY
#    cache: false
    template:
        projectBlock:
            path: www/images/
            width: 250
            height: 150
            flags: [FILL,SHRINK_ONLY]
            quality: 75
```

neon configure extension:
```neon
extensions:
    thumbnail: Thumbnail\Bridges\Nette\Extension
```

usage:
```latte
{* path, image, [width], [height], [image flag], [quality] *}
or
{* template, image *}

{* path/to/image.png *}
{thumb 'www/images/', '1920x1080.png', 200, 150, ['FIT'], 75}
{thumb 'www/images/', '1920x1080.png', 200, 150, [], 6}
{thumb 'www/images/', '1920x1080.png', 200, 150, ['FILL']}
{thumb 'www/images/', '1920x1080.png', 200, 150}
{thumb 'www/images/', '1920x1080.png', '50%', '75%'}
{thumb 'www/images/', '1920x1080.png', 200}
{thumb projectBlock, '1920x1080.png'}
{thumb projectBlock, $item['image']}

{thumb $presenter->context->parameters['gallery']['pathToImage'], $item['image'], 120, 121}

{* combine usage *}
<img src="{thumb projectBlock, $item['image']}">

{* accept modifier dataStream for base64 *}
{thumb projectBlock, $item['image']|dataStream}

{* example output: *}
{* output is not contains absolute url! eg: *}
{* www/images/1920x1080-131745-2019-01-28-00-37-50_p..SwwwSfilesSfileSwh64f1qmt1548632270.jpg *}
```

presenters:
```php
Thumbnail::setDefaultImageFlag(int $flag)
Thumbnail::setNoImage(string $path)
Thumbnail::setWaitImage(string $path)
Thumbnail::setLazyLoad(bool $state)
Thumbnail::setCache(bool $state)

Thumbnail::cleanThumbnail(): array
Thumbnail::synchronizeThumbnail([__DIR__.'/../../www/images/']) : array
Thumbnail::getUnusedFiles([__DIR__.'/../../www/images/']) : array
Thumbnail::isSrcPathExists(string $path, string $file = null): bool
Thumbnail::getSrcPath(string $path, string $file = null, string $width = null, string $height = null, array $flags = [], int $quality = null): string
```

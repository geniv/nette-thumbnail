Thumbnail
=========

Installation
------------

```sh
$ composer require geniv/nette-thumbnail
```
or
```json
"geniv/nette-thumbnail": ">=1.0.0"
```

require:
```json
"php": ">=7.0.0",
"nette/nette": ">=2.4.0",
"nette/finder": ">=2.4.0"
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

neon configure:
```neon
# thumbnail
thumbnail:
    dir: %wwwDir%/../
    thumbPath: %wwwDir%/files/image/thumbnail/
    noImage: www/images/error-small.png
    template:
        sablonaA:
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
<img {src 'www/images/', '1920x1080.png', 200, 150, ['FILL']}">
<img  n:src="'www/images/', '1920x1080.png', 200, 150, ['FILL']">
<img {src 'www/images/', '1920x1080.png', 200, 150, [], 8}>
<img  n:src="'www/images/', '1920x1080.png', 200, 150, [], 8">
<img {src 'www/images/', '1920x1080.png', 200, 150}>
<img  n:src="'www/images/', '1920x1080.png', 200, 150">
<img {src 'www/images/', '1920x1080.png', 200}>
<img  n:src="'www/images/', '1920x1080.png', 200">
<img {src sablonaA, '1920x1080.png'}>
<img  n:src="sablonaA, '1920x1080.png'">
```

presenters:
```php
Thumbnail::cleanThumbnail(): int
```

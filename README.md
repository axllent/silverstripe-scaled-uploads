# Automatically scale down uploaded images for Silverstripe

Reduce your footprint!

For all newly uploaded images in Silverstripe, this extension will automatically scale down (reduce width / height), compress, and convert them to webp to ensure your images are as light as possible, without significantly affecting quality. 

## Requirements

- Silverstripe ^4.0 || ^5.0

For Silverstripe 3, please refer to the [Silverstripe3 branch](https://github.com/axllent/silverstripe-scaled-uploads/tree/silverstripe3).

## Usage

Simply install the module and then set your own limits. For setting your limtis please refer to the [Configuration.md](docs/en/Configuration.md) file.

To use the functionality somewhere else, you can do something like this:
```php

use Axllent\ScaledUploads\Api\Resizer;
use SilverStripe\Assets\Image;

$runner = Resizer::create()
    ->setMaxHeight(100)
    ->setMaxFileSizeInMb(0.6)
    ->setDryRun(true)
    ->setVerbose(true);

$imagesIds = Image::get()->sort(['ID' => 'DESC'])->columnUnique();
foreach ($imagesIds as $imageID) {
    $image = Image::get()->byID($imageID);
    if ($image->exists()) {
        $runner->runFromDbFile($image);
    }
}

```

## Installation

```shell
composer require axllent/silverstripe-scaled-uploads
```

## Batch process existing images

If you would like to batch process existing images then you can use the [Resize All Images Module](https://github.com/sunnysideup/silverstripe-resize-all-images/) that extends this module. 

## Rotation

This extension no longer supports auto-rotation of JPG images (i.e. portrait images taken with digital cameras or cellphones). 
However, this should now also be part of Silverstripe core functionality - see  `vendor/silverstripe/assets/src/InterventionBackend.php:278` (not sure if or how this works). 


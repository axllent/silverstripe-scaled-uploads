# Automatically scale down uploaded images for Silverstripe

An extension to automatically scale down all new uploaded images in Silverstripe and (optionally) compress all uploaded images (resample) and converts them to webp. 
If the uploaded image is larger than a pre-configured size (width, height, filesies) then it will be scaled down. 

Note: The extension no longer supports auto-rotation of JPG images eg: portrait images taken with digital cameras or cellphones. 
However, this should be done around here:  `vendor/silverstripe/assets/src/InterventionBackend.php:278` (untested). 

## Requirements

- Silverstripe ^4.0 || ^5.0

For Silverstripe 3, please refer to the [Silverstripe3 branch](https://github.com/axllent/silverstripe-scaled-uploads/tree/silverstripe3).

## Usage

Simply install the module and then set your own limits. For this, Please refer to the [Configuration.md](docs/en/Configuration.md) file for options.

## Installation

```shell
composer require axllent/silverstripe-scaled-uploads
```

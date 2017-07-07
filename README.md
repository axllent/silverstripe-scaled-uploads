# Automatically scale down uploaded images for SilverStripe

An extension to automatically scale down all new uploaded images in SilverStripe and (optionally) compress
all uploaded images (resample). If the uploaded image is larger than a preconfigured size, it will be scaled down.
The extension also supports auto-rotation of JPG images eg: portrait images taken with digital cameras or cellphones.

## Requirements

- SilverStripe 4+
- Exif support for auto-rotation

For SilverStripe 3, please refer to the [SilverStripe3 branch](https://github.com/axllent/silverstripe-scaled-uploads/tree/silverstripe3).

## Usage

Simply install the module. All images are (by default) scaled to a maximum size of 960px (width) X 800px (height),
and auto-rotation (based on EXIF data) for JPG images is by default **on**.

## Configuration

Please refer to the [Configuration.md](docs/en/Configuration.md) file for options.

## Installation

```shell
composer require axllent/silverstripe-scaled-uploads
```

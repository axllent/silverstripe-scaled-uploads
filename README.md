# Automatically scale down uploaded images for Silverstripe

An extension to automatically scale down all new uploaded images in Silverstripe and (optionally) compress
all uploaded images (resample). If the uploaded image is larger than a pre-configured size, it will be scaled down.
The extension also supports auto-rotation of JPG images eg: portrait images taken with digital cameras or cellphones.

It also supports custom folder configurations to allow for different settings based on the folder they are uploaded into.

## Requirements

- Silverstripe ^4.0 || ^5.0
- EXIF support for auto-rotation

For Silverstripe 3, please refer to the [Silverstripe3 branch](https://github.com/axllent/silverstripe-scaled-uploads/tree/silverstripe3).

## Usage

Simply install the module. All images are (by default) scaled to a maximum size of 960px (width) X 800px (height),
and auto-rotation (based on EXIF data) for JPG images is by default **on**.

## Configuration

Please refer to the [Configuration.md](docs/en/Configuration.md) file for options.

## Installation

```shell
composer require axllent/silverstripe-scaled-uploads
```

# Automatically scale down uploaded images for SilverStripe

An extension to automatically scale down all new uploaded images in SilverStripe. If the uploaded image is larger than a preconfigured size, it will be scaled down. The extension also supports auto-rotation of JPG images eg: portrait images taken with digital cameras or cellphones.

## Requirements

- SilverStripe 4+
- GD support in PHP
- Exif support for auto-rotation

For SilverStripe 3, please refer to the [SilverStripe3 branch](https://github.com/axllent/silverstripe-scaled-uploads/tree/silverstripe3).


## Usage

Simply install the module. All images are (by default) scaled to a maximum size of 960px (width) X 800px (height), and auto-rotation (based on EXIF data) for JPG images is by default **on**.

## Configuration

Create or edit a *.yml file in your mysite/_config/ folder (eg: mysite/_config/scaleduploads.yml) and add & edit the following:

```
Axllent\ScaledUploads\ScaledUploads:
  max-width: 960
  max-height: 800
  auto-rotate: false
```

Please refer to the [Configuration docs](docs/en/Configuration.md) for more options.

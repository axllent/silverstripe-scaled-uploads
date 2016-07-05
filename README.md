# Automatically scale down uploaded images for SilverStripe 3

An extension to automatically scale down all new uploaded images in SilverStripe 3\. If the uploaded image is larger than a preconfigured size, it will be scaled down. The extension also supports auto-rotation of JPG images eg: portrait images taken with digital cameras or cellphones.

## Requirements

- SilverStripe 3+
- GD support in PHP

## Usage

Simply install the module. All images are (by default) scaled to a maximum size of 960px (width) X 800px (height), and auto-rotation (based on EXIF data) for JPG images is by default **on**. Please note that EXIF rotation only works if the uploaded image is larger than the specified values, and of course is present.

## Configuration

Create or edit a *.yml file in your mysite/_config/ folder (eg: mysite/_config/config.yml) and add & edit the following:

```
ScaledUploads:
  max-width: 960
  max-height: 800
  auto-rotate: 0
```

If you require larger images for a particular DataObject (such as full-page slideshows), but wish to keep all other uploads scaled to a pre-set default, you can simply add something like this to your DataObject:

```
public function onBeforeWrite()
{
    Config::inst()->update('ScaledUploads', 'max-width', 1600);
    Config::inst()->update('ScaledUploads', 'max-height', 1600);
    parent::onBeforeWrite();
}
```

If you need to bypass (skip) ScaledUploads for any particular reason, use:

```
Config::inst()->update('ScaledUploads', 'bypass', true);
```

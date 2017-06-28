# Configuration

To set your own configuration, simply create a `mysite/_config/scaled-uploads.yml`.

```yaml
Axllent\ScaledUploads\ScaledUploads:
  max-width: 960            # Maximum width - default 960
  max-height: 800           # Maximum height - default 800
  auto-rotate: true         # Automatically rotate images that rely on exif information for rotation - default true
  bypass: false             # Bypass (skip) this plugin when uploading - default false
  force-resampling: false   # Force re-saving (compressing) the image even if it is smaller - default true
```

The quality of the resampled images is determined by the `default_quality` setting of `Silverstripe\Assets\GDBackend`.
To change that simply add the following to your site's configuration:

```yaml
SilverStripe\Assets\GDBackend:
  default_quality: 80
```

If you require larger images for a particular DataObject (such as full-width banner), but wish to keep all other uploads scaled
to a pre-set default, you can simply add something like this to your DataObject:

```php
<?php
use SilverStripe\Assets\Image;

class BannerImage extends Image
{
    public function onBeforeWrite()
    {
        Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'max-width', 1600);
        Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'max-height', 1600);
        parent::onBeforeWrite();
    }
}
```

If you need to bypass (skip) ScaledUploads for any particular reason, use:

```php
Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'bypass', true);
```

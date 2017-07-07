# Configuration

To set your own configuration, simply create a `mysite/_config/scaled-uploads.yml`.

```yaml
Axllent\ScaledUploads\ScaledUploads:
  max_width: 960            # Maximum width - default 960
  max_height: 800           # Maximum height - default 800
  auto_rotate: true         # Automatically rotate images that rely on exif information for rotation - default true
  bypass: false             # Bypass (skip) this plugin when uploading - default false
  force_resampling: true    # Force re-saving the image even if it is smaller - default false
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
        Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'max_width', 1600);
        Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'max_height', 1600);
        parent::onBeforeWrite();
    }
}
```

If you need to bypass (skip) ScaledUploads for any particular reason, use:

```php
Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'bypass', true);
```

# Configuration

To set your own configuration, simply create a `app/_config/scaled-uploads.yml`.

```yaml
Axllent\ScaledUploads\ScaledUploads:
  max_width: 960            # Maximum width - default 960
  max_height: 800           # Maximum height - default 800
  auto_rotate: true         # Automatically rotate images that rely on exif information for rotation - default true
  bypass: false             # Bypass (skip) this plugin when uploading - default false
  force_resampling: true    # Force re-saving the image even if it is smaller - default false
  custom_folders:
    Gallery:                 # Custom upload folder and configuration
      max_width: 1600
      max_height: 1200
    ProfileImages:           # Custom upload folder and configuration
      max_width: 400
      max_height: 400
```

## Custom Folders

Custom folders will overwrite your default configuration if the parent path (of the uploaded image) matches one of the `custom_folders`.

**Note**: your configuration folders should not contain a trailing slash.

These settings are then merged into the ScaledUploads configuration overwriting the configurations set. In the above example, images uploaded to `Gallery` will inherit the `auto_rotate`, `bypass` & `force_resampling` settings, however will be set to a maximum of 1600x1200px.

## Extending Image

Another way of setting custom configurations is by extending `Image`, however the above custom folder configuration ensures that all images remain as `SilverStripe\Assets\Image` which is less prone to issues.

```php
<?php
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;

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

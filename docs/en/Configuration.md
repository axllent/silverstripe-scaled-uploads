# Configuration

To set your own configuration, simply create a `app/_config/scaled-uploads.yml`.

```yaml
Axllent\ScaledUploads\ScaledUploads:
  max_width: 960 # default maximum width - default 960
  max_height: 800 # default maximum height - default 800
  auto_rotate: true # automatically rotate images that rely on exif information for rotation - default true
  bypass: false # bypass (skip) this plugin when uploading - default false
  force_resampling: true # force re-saving the image even if it is smaller - default false
  memory_limit: 768M # optionally increase the PHP memory limit to handle large images
  custom_folders:
    Gallery: # custom folder path and configuration
      max_width: 1600
      max_height: 1200
    ProfileImages: # custom folder path and configuration
      max_width: 400
      max_height: 400
```

## Memory limits

The module is initially restricted to the PHP memory limit configured for your website. This limitation may lead to errors when processing excessively large images, so you might need to temporarily raise the memory limit.

You have the option to set the `memory_limit` to a value of your choice (as long as your server has sufficient memory available). This adjustment will temporarily increase Silverstripe's memory limit for each upload request only. It will not affect the maximum memory allowed for the rest of the website. Setting this to either `0` or `-1` will disable all limits, however extreme caution should be taken before considering this approach, as it is far safer to set restrictions.

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

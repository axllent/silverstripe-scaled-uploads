# Configuration

To set your own configuration, simply create a `app/_config/scaled-uploads.yml`.

For defaults, please refer to the [ScaledUploads.php](/src/Api/Resizer.php) file.

```yaml
Axllent\ScaledUploads\Api\Resizer:
  max_width: 960            # Maximum width - s
  max_height: 800           # Maximum height - default 800
  max_size_in_mb: 0.5       # The maximum size of the image in MB
  default_quality: 0.9      # The default quality of the image conversion (0-1)
  bypass: false             # Bypass (skip) this plugin when uploading - default false
  force_resampling: false   # Force re-saving the image even if it is smaller - default false
  patterns_to_skip:         # Patterns to skip (eg: *.svg)
    - '.svg'                # this is not necessary as SVGs are not resized
    - '__resampled'         # find in string
    - '/[^a-zA-Z0-9]/'      # supports basic regex
  custom_folders:
    Gallery:                # Custom upload folder and configuration
      maxWidth: 1600
      maxHeight: 1200
      useWebp: false
      quality: 55
      forceResampling: true
      maxSizeInMb: 0.1
    My/Other/Folder:         # Custom upload folder and configuration
      bypass: true
```

## Custom Folders

Custom folders will overwrite your default configuration if the folder of the image at hand matches one of the `custom_folders` listed.

**Note**: your configuration folders should not contain a starting or trailing slash.

## Extending Image

Another way of setting custom configurations is by extending `Image`, however the above custom folder configuration ensures that all images remain as `SilverStripe\Assets\Image` which is less prone to issues (you could also use an `Extension` of course).

```php
<?php
use SilverStripe\Assets\Image;
use SilverStripe\Core\Config\Config;

class BannerImage extends Image
{
    public function onBeforeWrite()
    {
        Config::modify()->set('Axllent\\ScaledUploads\\Api\\Resizer', 'max_width', 1600);
        parent::onBeforeWrite();
    }
}



```

If you need to bypass (skip) ScaledUploads for any particular reason, use:

```php
Config::modify()->set('Axllent\\ScaledUploads\\Api\\Resizer', 'bypass', true);
```

## Allowing webp

You may need to add this to your config files:
```yml

SilverStripe\Assets\File:
  file_types:
    webp: 'Webp Image'
  allowed_extensions:
    - webp
  app_categories:
    image:
      - webp
    image/supported:
      - webp
  class_for_file_extension:
    webp: SilverStripe\Assets\Image

SilverStripe\Assets\Storage\DBFile:
  supported_images:
    - image/webp

SilverStripe\MimeValidator\MimeUploadValidator:
  MimeTypes:
    webp:
      - 'image/webp'

```

# Configuration

Scaled uploads has a default configuration of:
```yaml
Axllent\ScaledUploads\ScaledUploads:
  max-width: 960        # Maximum width
  max-height: 800       # Maximum height
  auto-rotate: true     # Automatically rotate images that rely on exif information for rotation
  bypass: false         # Bypass (skip) this plugin when uploading
```

To set your own configuration, simply create a `mysite/_config/scaled-uploads.yml`. If you wish to only scaled by the height or width, you can set a false value, for instance:

```yaml
Axllent\ScaledUploads\ScaledUploads:
  max-width: 960
  max-height: false
```

If you require larger images for a particular DataObject (such as full-page slideshows), but wish to keep all other uploads scaled to a pre-set default, you can simply add something like this to your DataObject:

```php
public function onBeforeWrite()
{
    Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'max-width', 1600);
    Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'max-height', 1600);
    parent::onBeforeWrite();
}
```

If you need to bypass (skip) ScaledUploads for any particular reason, use:

```
Config::inst()->update('Axllent\\ScaledUploads\\ScaledUploads', 'bypass', true);
```

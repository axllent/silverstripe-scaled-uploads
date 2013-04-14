# Automatically scale down uploaded images for SilverStripe 3
An extension to automatically scale down all uploaded images in SilverStripe 3. If the uploaded
image is larger than a pre-configured size, it will be scaled down. The extension also supports
auto-rotation of JPG images eg: portrait images taken with digital cameras or cellphones.

## Requirements
* SilverStripe 3+
* GD support in PHP

## Usage
In your mysite/_config.php you may set different defaults, eg:
<pre>
ScaledUploads::$max_width = 800; // set max height to 800px
ScaledUploads::$max_height = 600; // set max width to 600px
ScaledUploads::$exif_rotation = false; // turn off auto-rotation
</pre>

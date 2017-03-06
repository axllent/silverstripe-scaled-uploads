<?php

namespace Axllent\ScaledUploads;

use SilverStripe\Assets\GDBackend;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataExtension;

/**
 * Automatically scale down uploaded images
 * ========================================
 *
 * Extension to automatically scale down uploaded images to a maximum
 * of pre-determined values or defaults. It also includes auto-rotation
 * based on EXIF data (eg: images from digital cameras).
 *
 * Options:
 * Please refer to the README.md
 *
 * @license: MIT-style license http://opensource.org/licenses/MIT
 * @author: Techno Joy development team (www.technojoy.co.nz)
 */

class ScaledUploads extends DataExtension
{

    public static $max_width = 960;

    public static $max_height = 800;

    public static $bypass = false;

    public static $exif_rotation = true;

    public function onBeforeWrite()
    {
        /**
         * Only run if new image
         * don't use Image->exists() as it is implemented differently for Image
         */
        if ($this->owner->ID || $this->getBypass()) {
            return; // only run with new images
        }

        $extension = $this->owner->getExtension();

        if (
            $this->owner->getHeight() > $this->getMaxHeight() ||
            $this->owner->getWidth() > $this->getMaxWidth() ||
            ($this->getAutoRotate() && preg_match('/jpe?g/i', $this->owner->getExtension()))
        ) {
            $this->ScaleUploadedImage();
        }
    }

    public function ScaleUploadedImage()
    {
        /* temporary location for image manipulation */
        $tmp_image = TEMP_FOLDER .'/resampled-' . mt_rand(100000, 999999) . '.' . $this->owner->getExtension();

        $tmp_contents = $this->owner->getString();

        // write to tmp file
        @file_put_contents($tmp_image, $tmp_contents);

        $gd = new GDBackend();

        $gd->loadFrom($tmp_image);

        if ($gd->getImageResource()) {

            /* Clone original */
            $transformed = $gd;

            /* If rotation allowed & JPG, test to see if orientation needs switching */
            if ($this->getAutoRotate() && preg_match('/jpe?g/i', $this->owner->getExtension())) {
                $switch_orientation = $this->exifRotation($tmp_image);
                if ($switch_orientation) {
                    $transformed = $transformed->rotate($switch_orientation);
                }
            }

            /* Resize to max values */
            if (
                $transformed && (
                    $transformed->getWidth() > $this->getMaxWidth() ||
                    $transformed->getHeight() > $this->getMaxHeight()
                )
            ) {
                $transformed = $transformed->resizeRatio($this->getMaxWidth(), $this->getMaxHeight());
            }

            /* Write to tmp file and then overwrite original */
            if ($transformed) {
                $orig_hash = $this->owner->getHash();

                $transformed->writeTo($tmp_image);

                $this->owner->File->deleteFile(); // delete original else a rogue copy is left

                $this->owner->setFromLocalFile($tmp_image, $this->owner->FileName); // set new image

                unlink($tmp_image); // delete tmp file
            }
        }
    }

    public function getBypass()
    {
        $w = Config::inst()->get('Axllent\\ScaledUploads\\ScaledUploads', 'bypass');
        return ($w) ? $w : self::$bypass;
    }

    public function getMaxWidth()
    {
        $w = Config::inst()->get('Axllent\\ScaledUploads\\ScaledUploads', 'max-width');
        return ($w) ? $w : self::$max_width;
    }

    public function getMaxHeight()
    {
        $h = Config::inst()->get('Axllent\\ScaledUploads\\ScaledUploads', 'max-height');
        return ($h) ? $h : self::$max_height;
    }

    public function getAutoRotate()
    {
        $r = Config::inst()->get('Axllent\\ScaledUploads\\ScaledUploads', 'auto-rotate');
        if ($r === 0 || $r == 'false') {
            return false;
        }
        return self::$exif_rotation;
    }

    /**
     * exifRotation - return the exif rotation
     * @param String $FileName
     * @return Int false|angle
     */
    public function exifRotation($file)
    {
        $exif = @exif_read_data($file);
        if (!$exif) {
            return false;
        }
        $ort = @$exif['IFD0']['Orientation'];
        if (!$ort) {
            $ort = @$exif['Orientation'];
        }
        switch ($ort) {
            case 3: // image upside down
                return '180';
            break;
            case 6: // 90 rotate right & switch max sizes
                return '-90';
            break;
            case 8: // 90 rotate left & switch max sizes
                return '90';
            break;
            default:
                return false;
        }
    }
}

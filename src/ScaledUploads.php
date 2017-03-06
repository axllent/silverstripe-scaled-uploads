<?php

namespace Axllent\ScaledUploads;

use SilverStripe\Assets\GDBackend;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;

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

class ScaledUploads extends Extension
{

    public function __construct()
    {
        $this->config = Config::inst();
    }

    public function onAfterLoadIntoFile($file)
    {
        if ($this->getBypass() || !$file->IsImage) {
            return;
        }

        $extension = $file->getExtension();

        if (
            ($this->getMaxHeight() && $file->getHeight() > $this->getMaxHeight()) ||
            ($this->getMaxWidth() && $file->getWidth() > $this->getMaxWidth()) ||
            ($this->getAutoRotate() && preg_match('/jpe?g/i', $file->getExtension()))
        ) {
            $this->ScaleUploadedImage($file);
        }
    }

    private function ScaleUploadedImage($file)
    {
        /* temporary location for image manipulation */
        $tmp_image = TEMP_FOLDER .'/resampled-' . mt_rand(100000, 999999) . '.' . $file->getExtension();

        $tmp_contents = $file->getString();

        // write to tmp file
        @file_put_contents($tmp_image, $tmp_contents);

        $gd = new GDBackend();

        $gd->loadFrom($tmp_image);

        if ($gd->getImageResource()) {
            $modified = false;

            /* Clone original */
            $transformed = $gd;

            /* If rotation allowed & JPG, test to see if orientation needs switching */
            if ($this->getAutoRotate() && preg_match('/jpe?g/i', $file->getExtension())) {
                $switch_orientation = $this->exifRotation($tmp_image);
                // die('rotating?: ' . $switch_orientation);
                if ($switch_orientation) {
                    $modified = true;
                    $transformed = $transformed->rotate($switch_orientation);
                }
            }

            /* Resize to max values */
            if (
                $transformed &&
                (
                    ($this->getMaxWidth() && $transformed->getWidth() > $this->getMaxWidth()) ||
                    ($this->getMaxHeight() && $transformed->getHeight() > $this->getMaxHeight())
                )
            ) {
                if ($this->getMaxWidth() && $this->getMaxHeight()) {
                    $transformed = $transformed->resizeRatio($this->getMaxWidth(), $this->getMaxHeight());
                } elseif ($this->getMaxWidth()) {
                    $transformed = $transformed->resizeByWidth($this->getMaxWidth());
                } else {
                    $transformed = $transformed->resizeByHeight($this->getMaxHeight());
                }
                $modified = true;
            }

            /* Write to tmp file and then overwrite original */
            if ($transformed && $modified) {
                $orig_hash = $file->getHash();

                $transformed->writeTo($tmp_image);

                $file->File->deleteFile(); // delete original else a rogue copy is left

                $file->setFromLocalFile($tmp_image, $file->FileName); // set new image

            }
        }

        @unlink($tmp_image); // delete tmp file
    }

    public function getBypass()
    {
        return $this->config->get('Axllent\\ScaledUploads\\ScaledUploads', 'bypass');
    }

    public function getMaxWidth()
    {
        return $this->config->get('Axllent\\ScaledUploads\\ScaledUploads', 'max-width');
    }

    public function getMaxHeight()
    {
        return $this->config->get('Axllent\\ScaledUploads\\ScaledUploads', 'max-height');
    }

    public function getAutoRotate()
    {
        return $this->config->get('Axllent\\ScaledUploads\\ScaledUploads', 'auto-rotate');
    }

    /**
     * exifRotation - return the exif rotation
     * @param String $FileName
     * @return Int false|angle
     */
    public function exifRotation($file)
    {
        $exif = @exif_read_data($file);
        // var_dump($exif);
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
            case 6: // 90 rotate right
                return '-90';
            break;
            case 8: // 90 rotate left
                return '90';
            break;
            default:
                return false;
        }
    }
}

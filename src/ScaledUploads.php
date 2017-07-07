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
    protected $max_width;
    protected $max_height;
    protected $auto_rotate;
    protected $bypass;
    protected $force_resampling;

    public function __construct()
    {
        $this->config = Config::inst();
        $this->max_width = $this->currConfig('max-width', 960);
        $this->max_height = $this->currConfig('max-height', 800);
        $this->auto_rotate = $this->currConfig('auto-rotate', true);
        $this->bypass = $this->currConfig('bypass', false);
        $this->force_resampling = $this->currConfig('force-resampling', false);
    }

    public function onAfterLoadIntoFile($file)
    {
        if ($this->bypass || !$file->IsImage) {
            return;
        }

        $extension = $file->getExtension();

        if (
            $this->force_resampling ||
            ($this->max_height && $file->getHeight() > $this->max_height) ||
            ($this->max_width && $file->getWidth() > $this->max_width) ||
            ($this->auto_rotate && preg_match('/jpe?g/i', $file->getExtension()))
        ) {
            $this->ScaleUploadedImage($file);
        }
    }

    private function ScaleUploadedImage($file)
    {

        $backend = $file->getImageBackend();

        /* temporary location for image manipulation */
        $tmp_image = TEMP_FOLDER .'/resampled-' . mt_rand(100000, 999999) . '.' . $file->getExtension();

        $tmp_contents = $file->getString();

        // write to tmp file
        @file_put_contents($tmp_image, $tmp_contents);

        $backend->loadFrom($tmp_image);

        if ($backend->getImageResource()) {
            $modified = false;

            /* Clone original */
            $transformed = $backend;

            /* If rotation allowed & JPG, test to see if orientation needs switching */
            if ($this->auto_rotate && preg_match('/jpe?g/i', $file->getExtension())) {
                $switch_orientation = $this->exifRotation($tmp_image);
                if ($switch_orientation) {
                    $modified = true;
                    $transformed->setImageResource($transformed->getImageResource()->orientate());
                }
            }

            /* Resize to max values */
            if (
                $transformed &&
                (
                    ($this->max_width && $transformed->getWidth() > $this->max_width) ||
                    ($this->max_height && $transformed->getHeight() > $this->max_height)
                )
            ) {
                if ($this->max_width && $this->max_height) {
                    $transformed = $transformed->resizeRatio($this->max_width, $this->max_height);
                } elseif ($this->max_width) {
                    $transformed = $transformed->resizeByWidth($this->max_width);
                } else {
                    $transformed = $transformed->resizeByHeight($this->max_height);
                }
                $modified = true;
            } elseif ($transformed && $this->force_resampling) {
                $modified = true;
            }

            /* Write to tmp file and then overwrite original */
            if ($transformed && $modified) {
                $orig_hash = $file->getHash();
                $transformed->writeTo($tmp_image);
                $file->File->deleteFile(); // delete original else a rogue copy is left
                $file->setFromLocalFile($tmp_image, $file->FileName); // set new image
                $file->write();
            }
        }

        @unlink($tmp_image); // delete tmp file
    }


    /**
     * Check current config else return a default
     * @param String, value
     * @return value
     */
    protected function currConfig($key, $default = false)
    {
        $val = $this->config->get('Axllent\\ScaledUploads\\ScaledUploads', $key);
        return (isset($val)) ? $val : $default;
    }

    /**
     * exifRotation - return the exif rotation
     * @param String $FileName
     * @return Int false|angle
     */
    public function exifRotation($file)
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }

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

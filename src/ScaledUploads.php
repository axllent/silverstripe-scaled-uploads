<?php

namespace Axllent\ScaledUploads;

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
    /**
     * @config
     */
    private static $max_width = 960;
    private static $max_height = 800;
    private static $auto_rotate = true;
    private static $bypass = false;
    private static $force_resampling = false;
    private static $custom_folders = [];

    public function onAfterLoadIntoFile($file)
    {
        // return if not an image
        if (!$file->getIsImage()) {
            return;
        }

        // get parent folder path
        $folder = rtrim($file->Parent()->getFilename(), '/');

        $custom_folders = $this->currConfig('custom_folders');

        if (!empty($custom_folders[$folder]) && is_array($custom_folders[$folder])) {
            foreach ($custom_folders[$folder] as $key => $val) {
                $this->setConfig($key, $val);
            }
        }

        if ($this->currConfig('bypass')) {
            return;
        }

        $this->config_max_width = $this->currConfig('max_width');
        $this->config_max_height = $this->currConfig('max_height');
        $this->config_auto_rotate = $this->currConfig('auto_rotate');
        $this->config_force_resampling = $this->currConfig('force_resampling');

        $extension = $file->getExtension();

        if ($this->config_force_resampling ||
            ($this->config_max_height && $file->getHeight() > $this->config_max_height) ||
            ($this->config_max_width && $file->getWidth() > $this->config_max_width) ||
            ($this->config_auto_rotate && preg_match('/jpe?g/i', $file->getExtension()))
        ) {
            $this->scaleUploadedImage($file);
        }
    }

    private function scaleUploadedImage($file)
    {
        $backend = $file->getImageBackend();

        // temporary location for image manipulation
        $tmp_image = TEMP_FOLDER . '/resampled-' . mt_rand(100000, 999999) . '.' . $file->getExtension();

        $tmp_contents = $file->getString();

        // write to tmp file
        @file_put_contents($tmp_image, $tmp_contents);

        $backend->loadFrom($tmp_image);

        if ($backend->getImageResource()) {
            $modified = false;

            // clone original
            $transformed = $backend;

            /* If rotation allowed & JPG, test to see if orientation needs switching */
            if ($this->config_auto_rotate && preg_match('/jpe?g/i', $file->getExtension())) {
                $switch_orientation = $this->exifRotation($tmp_image);
                if ($switch_orientation) {
                    $modified = true;
                    $transformed->setImageResource($transformed->getImageResource()->orientate());
                }
            }

            // resize to max values
            if ($transformed &&
                (
                    ($this->config_max_width && $transformed->getWidth() > $this->config_max_width) ||
                    ($this->config_max_height && $transformed->getHeight() > $this->config_max_height)
                )
            ) {
                if ($this->config_max_width && $this->config_max_height) {
                    $transformed = $transformed->resizeRatio($this->config_max_width, $this->config_max_height);
                } elseif ($this->config_max_width) {
                    $transformed = $transformed->resizeByWidth($this->config_max_width);
                } else {
                    $transformed = $transformed->resizeByHeight($this->config_max_height);
                }
                $modified = true;
            } elseif ($transformed && $this->config_force_resampling) {
                $modified = true;
            }

            // write to tmp file and then overwrite original
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
     * @param  String, value
     * @return value
     */
    protected function currConfig($key)
    {
        if (empty($this->config)) {
            $this->config = Config::inst();
        }
        return $this->config->get('Axllent\\ScaledUploads\\ScaledUploads', $key);
    }

    /**
     * Set current config
     * @param String, String
     */
    protected function setConfig($key, $val)
    {
        if (empty($this->config)) {
            $this->config = Config::inst();
        }
        $this->config->set('Axllent\\ScaledUploads\\ScaledUploads', $key, $val);
    }

    /**
     * exifRotation - return the exif rotation
     * @param  String $FileName
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

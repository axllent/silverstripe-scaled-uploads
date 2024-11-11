<?php

namespace Axllent\ScaledUploads;

use Axllent\ScaledUploads\Api\Resizer;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
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
 *
 * @author: Techno Joy development team (www.technojoy.co.nz)
 */
class ScaledUploads extends Extension
{
    /**
     * Post data manipulation
     *
     * @param $file File Silverstripe file object
     *
     * @return null
     */
    public function onAfterLoadIntoFile($file)
    {
        // return if not an image
        if (!$file->getIsImage()) {
            return;
        }

        $file = Resizer::create()->runFromDbFile($file);
    }


}

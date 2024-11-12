<?php

namespace Axllent\ScaledUploads\Extensions;

use Axllent\ScaledUploads\Api\Resizer;
use SilverStripe\Core\Extension;

class ScaledUploadsExtension extends Extension
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

        $file = Resizer::create()
            ->runFromDbFile($file);
    }


}

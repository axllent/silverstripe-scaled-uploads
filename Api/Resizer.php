<?php

namespace Axllent\ScaledUploads\Api;

use Axllent\ScaledUploads\ScaledUploads;
use SilverStripe\Assets\Flysystem\FlysystemAssetStore;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Image_Backend;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SplFileInfo;

class Resizer
{
    use Injectable;
    use Configurable;

    /**
     *
     * file patterns to skip - e.g. '__resampled'
     * @var array
     */
    private static array $patterns_to_skip;

    /**
     * names of folders that should be treated differently
     *
     * @var array
     */
    private static array $custom_folders;

    /**
     * Maximum width
     *
     * @config
     */
    private static int $max_width = 2800;

    /**
     * Maximum height
     *
     * @config
     */
    private static int $max_height = 1200;

    /**
     * Maximum size of the file in megabytes
     *
     * @config
     */
    private static float $max_size_in_mb = 1;

    /**
     * Default resize quality
     *
     * @config
     */
    private static float $default_quality = 0.77;

    /**
     * Replace images with WebP format
     *
     * @config
     */
    private static bool $use_webp = true;


    /**
     * Auto-rotate scaled images
     *
     * @config
     */
    private static bool $auto_rotate = true;


    /**
     * Force resampling of images even if not stricly necessary
     *
     * @config
     */
    private static bool $force_resampling = false;

    private static array $image_extensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ];


    protected array $imageExtensions;

    protected array $patternsToSkip;
    protected array $customFolders;

    protected int|null $maxWidth;

    protected int|null $maxHeight;

    protected float|null $maxSizeInMb;

    protected float $quality;

    protected bool $useWebp;

    protected bool|null $autoRotate;

    protected bool|null $forceResampling;

    protected Image_Backend $transformed;

    protected $file;

    protected $tmpImagePath;

    protected $tmpImageContent;

    protected $switchOrientation;

    /**
     * When trying to get in range for size, we keep reducing the quality by this step.
     * Until the image is small enough.
     * @var float
     */
    protected float $qualityReductionIncrement = 0.1;

    public function setImageExtensions(array $array): static
    {
        $this->imageExtensions = $array;
        return $this;
    }

    public function setPatternsToSkip(array $array): static
    {
        $this->patternsToSkip = $array;
        return $this;
    }

    public function setCustomFolders(array $array): static
    {
        $this->customFolders = $array;
        return $this;
    }

    public function setMaxFileSizeInMb(null|float|int $maxSizeInMb = 2): static
    {
        $this->maxSizeInMb = $maxSizeInMb;
        return $this;
    }

    public function setMaxWidth(?float $maxWidth = 2800): static
    {
        $this->maxWidth = $maxWidth;
        return $this;
    }
    public function setMaxHeight(?float $maxHeight = 1200): static
    {
        $this->maxHeight = $maxHeight;
        return $this;
    }

    public function setQuality(?float $quality = 0.77): static
    {
        $this->quality = $quality;
        return $this;
    }

    public function setUseWebp(?bool $useWebp): static
    {
        $this->useWebp = $useWebp;
        return $this;
    }

    public function setQualityReductionIncrement(?float $qualityReductionIncrement = 0.1): static
    {
        $this->qualityReductionIncrement = $qualityReductionIncrement;
        return $this;
    }


    public function __construct()
    {
        $this->patternsToSkip  = $this->config()->get('patterns_to_skip');
        $this->customFolders   = $this->config()->get('custom_folders');
        $this->maxWidth        = $this->config()->get('max_width');
        $this->maxHeight       = $this->config()->get('max_height');
        $this->maxSizeInMb     = $this->config()->get('max_size_in_mb');
        $this->quality         = $this->config()->get('default_quality');
        $this->useWebp         = $this->config()->get('use_webp');
        $this->autoRotate      = $this->config()->get('auto_rotate');
        $this->forceResampling = $this->config()->get('force_resampling');
        $this->imageExtensions = $this->config()->get('image_extensions');
    }

    /**
     * Scale an image
     *
     *
     * @return null
     */
    public function runFromDbFile(Image $file): Image
    {
        $this->file = $file;

        // get parent folder path
        if (! $this->canBeConverted($this->file->getFilename(), $this->file->getExtension())) {
            return $this->file;
        }
        if (
            $this->forceResampling
            || $this->needsRotating()
            || $this->needsResizing()
            || $this->needsConvertingToWebp()
            || $this->needsCompressing()
            // check if not webp and use webp
        ) {
            if ($this->loadBackend()) {
                $modified = false;
                // clone original

                // If rotation allowed & JPG, test to see if orientation needs switching
                $modified = $this->rotate() ? true : $modified;
                $modified = $this->resize() ? true : $modified;
                $modified = $this->convertToWebp() ? true : $modified;
                $modified = $this->compress() ? true : $modified;
                if ($modified || $this->forceResampling) {
                    $this->writeToFile();
                }

            }

            @unlink($this->tmpImagePath); // delete tmp file
        }
        return $file;
    }

    protected function loadBackend(): bool
    {

        $this->transformed = $this->file->getImageBackend();

        // temporary location for image manipulation
        $this->tmpImagePath = TEMP_FOLDER . '/resampled-' . mt_rand(100000, 999999) . '.' . $this->file->getExtension();

        $this->tmpImageContent = $this->file->getString();

        // write to tmp file
        @file_put_contents($this->tmpImagePath, $this->tmpImageContent);

        $this->transformed->loadFrom($this->tmpImagePath);

        if ($this->transformed->getImageResource()) {
            return true;
        }
        return false;
    }

    protected function canBeConverted(string $filePath, string $extension): bool
    {
        $extension = strtolower($extension);
        $folder = rtrim(strval(dirname($filePath)), '/');
        if (!empty($customFolders[$folder]) && is_array($this->customFolders[$folder])) {
            foreach ($this->customFolders[$folder] as $key => $val) {
                $this->config()->set($key, $val);
            }
        }
        if ($this->config()->get('bypass')) {
            return false;
        }
        if (!in_array($extension, $this->imageExtensions)) {
            return false;
        }
        $filePath = $this->file->getFilename();
        foreach ($this->patternsToSkip as $pattern) {
            if (strpos($filePath, $pattern) !== false) {
                return false;
            }
        }
        return true;
    }

    public function needsResizing(): bool
    {
        return ($this->maxWidth && $this->file->getWidth() > $this->maxWidth)
            || ($this->maxHeight && $this->file->getHeight() > $this->maxHeight);
    }

    public function needsConvertingToWebp(): bool
    {
        return $this->useWebp && $this->file->getExtension() !== 'webp';
    }

    public function needsRotating(): bool
    {
        return ($this->autoRotate && preg_match('/jpe?g/i', $this->file->getExtension()));
    }

    public function needsCompressing(): bool
    {
        return ($this->maxSizeInMb && $this->file->getAbsoluteSize() > $this->maxSizeInMb * 1024 * 1024);
    }

    protected function rotate(): bool
    {
        $modified = false;
        // If rotation allowed & JPG, test to see if orientation needs switching
        if ($this->transformed && $this->needsRotating()) {
            $switchOrientation = $this->exifRotation();
            if ($switchOrientation) {
                $modified = true;
                $this->transformed->setImageResource($this->transformed->getImageResource()->orientate());
            }
        }
        return $modified;
    }

    protected function resize(): bool
    {
        $modified = false;
        // resize to max values
        if ($this->transformed && $this->needsResizing()) {
            if ($this->maxWidth && $this->maxHeight) {
                $this->transformed = $this->transformed->resizeRatio($this->maxWidth, $this->maxHeight);
            } elseif ($this->maxWidth) {
                $this->transformed = $this->transformed->resizeByWidth($this->maxWidth);
            } else {
                $this->transformed = $this->transformed->resizeByHeight($this->maxHeight);
            }
            $modified = true;
        }
        return $modified;
    }

    protected function convertToWebp(): bool
    {
        $modified = false;
        // Convert to WebP and save
        if ($this->transformed && $this->needsConvertingToWebp()) {
            $tmpFile = $this->file->Convert('webp');
            $this->file->setFromLocalFile($tmpFile->getFilename(), $tmpFile->FileName);
            $tmpFile->delete();
            $tmpFile->File->deleteFile();
            //todo: maybe write???
            $this->saveAndPublish($this->file);
            $this->loadBackend();
            $modified = true;

        }
        return $modified;
    }

    protected function compress(): bool
    {
        $modified = false;
        // Check if WebP is smaller
        if ($this->transformed && $this->needsCompressing()) {
            $this->transformed->writeTo($this->tmpImagePath);
            $sizeCheck = $this->isFileSizeGreaterThan($this->tmpImagePath);
            $step = 1;
            while ($sizeCheck && $step > 0) {
                // reduce quality
                $modified = true;
                $this->transformed->setQuality($this->quality * $step);
                $this->transformed->writeTo($this->tmpImagePath);
                // new round
                $sizeCheck = $this->isFileSizeGreaterThan($this->tmpImagePath);
                $step -= $this->qualityReductionIncrement;
            }
        }
        return $modified;
    }

    protected function writeToFile()
    {
        // write to tmp file and then overwrite original
        if ($this->transformed) {
            $this->transformed->writeTo($this->tmpImagePath);
            // if !legacy_filenames then delete original, else rogue copies are left on filesystem
            if (file_exists($this->tmpImagePath)) {
                if (!Config::inst()->get(FlysystemAssetStore::class, 'legacy_filenames')) {
                    $this->file->File->deleteFile();
                }
                $this->file->setFromLocalFile($this->tmpImagePath, $this->file->FileName); // set new image
                $this->saveAndPublish($this->file);
            }
        }
    }


    /**
     * exifRotation - return the exif rotation
     *
     * @return int false|angle
     */
    protected function exifRotation(): bool|string
    {
        if (!function_exists('exif_read_data')) {
            return false;
        }

        $exif = @exif_read_data($this->file);

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
            case 6: // 90 rotate right
                return '-90';
            case 8: // 90 rotate left
                return '90';
            default:
                return false;
        }
    }


    protected function isFileSizeGreaterThan(string $filePath): ?float
    {
        $fileSize = filesize($filePath);
        $maxSize = $this->maxSizeInMb * 1024 * 1024;
        if ($fileSize > $maxSize) {
            return round(($fileSize - $maxSize) / $maxSize * 100);
        }
        return null;
    }

    protected function saveAndPublish(Image $image)
    {
        $isPublished = $image->isPublished();
        $image->write();
        if ($isPublished) {
            $image->publishSingle();
        }
    }

}

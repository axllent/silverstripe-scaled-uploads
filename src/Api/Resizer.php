<?php

namespace Axllent\ScaledUploads\Api;

use SilverStripe\Assets\Flysystem\FlysystemAssetStore;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Image_Backend;
use SilverStripe\Assets\Storage\DBFile;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

class Resizer
{
    use Injectable;
    use Configurable;


    private static $bypass_all = false;

    /**
     *
     * file patterns to skip - e.g. '__resampled'
     * @var array
     */
    private static array $patterns_to_skip = [];

    /**
     * names of folders that should be treated differently
     *
     * @var array
     */
    private static array $custom_folders = [];

    /**
     * Maximum width
     *
     * @config
     */
    private static int $max_width = 3600;

    /**
     * Maximum height
     *
     * @config
     */
    private static int $max_height = 2160; // 0.6y of width

    /**
     * Maximum size of the file in megabytes
     *
     * @config
     */
    private static float $max_size_in_mb = 0.4;

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
     * Force resampling of images even if not stricly necessary
     *
     * @config
     */
    private static bool $force_resampling = false;
    protected bool $dryRun = false;
    protected bool $verbose = false;
    protected bool $bypass;
    protected array $patternsToSkip;
    protected array $customFolders;
    protected int|null $maxWidth;
    protected int|null $maxHeight;
    protected float|null $maxSizeInMb;
    protected float $quality;
    protected bool $useWebp;
    protected bool|null $forceResampling;
    protected Image_Backend $transformed;
    protected $file;
    protected string $tmpImagePath;
    protected string $tmpImageContent;
    protected array $originalValues = [];
    private const CUSTOM_VALUES_ALLOWED = [
        'maxWidth',
        'maxHeight',
        'maxSizeInMb',
        'quality',
        'useWebp',
        'forceResampling',
    ];

    /**
     * When trying to get in range for size, we keep reducing the quality by this step.
     * Until the image is small enough.
     * @var float
     */
    protected float $qualityReductionIncrement = 0.1;

    public function setDryRun(?bool $dryRun = true): static
    {
        $this->dryRun = $dryRun;
        return $this;
    }

    public function setVerbose(?bool $verbose = true): static
    {
        $this->verbose = $verbose;
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

    public function setMaxWidth(?int $maxWidth = 2800): static
    {
        $this->maxWidth = $maxWidth;
        return $this;
    }
    public function setMaxHeight(?int $maxHeight = 1200): static
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
        $this->bypass          = $this->config()->get('bypass_all');
        $this->patternsToSkip  = $this->config()->get('patterns_to_skip');
        $this->customFolders   = $this->config()->get('custom_folders');
        $this->maxWidth        = $this->config()->get('max_width');
        $this->maxHeight       = $this->config()->get('max_height');
        $this->maxSizeInMb     = $this->config()->get('max_size_in_mb');
        $this->quality         = $this->config()->get('default_quality');
        $this->useWebp         = $this->config()->get('use_webp');
        $this->forceResampling = $this->config()->get('force_resampling');
    }

    /**
     * Scale an image
     *
     *
     * @return null
     */
    public function runFromDbFile(Image $file): Image
    {
        if ($this->dryRun) {
            $this->verbose = true;
        }
        $this->file = $file;
        if ($this->verbose) {
            echo '---' . PHP_EOL;
            if ($this->dryRun) {
                echo 'DRY RUN' . PHP_EOL;
            } else {
                echo 'REAL RUN' . PHP_EOL;
            }
        }
        $path = $this->file->getFilename();
        if (!$path) {
            if ($this->verbose) {
                echo 'Cannot convert image with ID ' . $file->ID. ' as Filename is empty.' . PHP_EOL;
            }
            return $this->file;
        }
        // we do this first as it may contain the bypass flag
        $this->applyCustomFolders($path);

        if (! $this->canBeConverted($path, $this->file->getExtension())) {
            if ($this->verbose) {
                echo 'Cannot convert ' . $path . PHP_EOL;
            }
            return $this->file;
        }
        if (
            $this->forceResampling
            // || $this->needsRotating()
            || $this->needsResizing()
            || $this->needsConvertingToWebp()
            || $this->needsCompressing()
            // check if not webp and use webp
        ) {
            if ($this->loadBackend()) {
                $modified = false;
                // clone original

                // If rotation allowed & JPG, test to see if orientation needs switching
                $modified = $this->convertToWebp() ? true : $modified;
                $modified = $this->resize() ? true : $modified;
                $modified = $this->compress() ? true : $modified;
                if ($modified || $this->forceResampling) {
                    $this->writeToFile();
                }

                @unlink($this->tmpImagePath); // delete tmp file
            } else {
                if ($this->verbose) {
                    echo 'ERROR: Cannot load backend for ' . $this->file->getFilename() . PHP_EOL;
                }
            }

        } else {
            if ($this->verbose) {
                echo 'No need to resize / convert ' . $this->file->getFilename() . PHP_EOL;
            }
        }
        return $file;
    }


    protected function canBeConverted(string $filePath, string $extension): bool
    {

        if ($this->bypass) {
            return false;
        }
        if (! $this->file->getIsImage()) {
            return false;
        }
        foreach ($this->patternsToSkip as $pattern) {
            // Detect if the pattern is likely a regex
            if ($this->looksLikeRegex($pattern)) {
                // Treat it as a regex
                if (preg_match($pattern, $filePath)) {
                    return false;
                }
            } else {
                if (strpos($filePath, $pattern) !== false) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     *
     * Allows you to add custom settings at runtime without changing the config layer
     * @return void
     */
    public function applyCustomFolders(string $filePath, ?array $moreCustomValues = []): void
    {
        $folder = trim(strval(dirname($filePath)), '/');
        // Check if original values need to be restored
        if (!empty($this->originalValues)) {
            foreach ($this->originalValues as $key => $value) {
                $this->$key = $value; // Restore original values
            }
            $this->originalValues = []; // Clear after restoration
        }

        // Apply custom folder settings if available
        if (!empty($this->customFolders[$folder]) && is_array($this->customFolders[$folder])) {
            $this->applyCustomFoldersInner($this->customFolders[$folder]);
            $this->applyCustomFoldersInner($moreCustomValues);
        }
    }

    protected function applyCustomFoldersInner(array $toApply)
    {
        foreach ($toApply as $key => $val) {
            if (!in_array($key, self::CUSTOM_VALUES_ALLOWED)) {
                user_error(
                    'Invalid custom folder setting: ' . $key. '.' .
                    'Allowed values are: '.print_r(self::CUSTOM_VALUES_ALLOWED, 1),
                    E_USER_WARNING
                );
            }
            // Store the original value if not already stored
            if (!isset($this->originalValues[$key])) {
                $this->originalValues[$key] = $this->$key ?? null;
            }
            // Apply the custom value
            $this->$key = $val;
        }
    }

    protected function looksLikeRegex(string $pattern): bool
    {
        $delimiters = ['/', '#', '~', '%']; // Common regex delimiters
        $firstChar = $pattern[0] ?? '';
        $lastChar = substr($pattern, -1);

        // Check if the first and last characters are the same and part of known delimiters
        return in_array($firstChar, $delimiters, true) && $firstChar === $lastChar;
    }

    protected function loadBackend(?Image $file = null): bool
    {
        if (!$file) {
            $file = $this->file;
        }
        $this->transformed = $file->getImageBackend();

        // temporary location for image manipulation
        $this->tmpImagePath = TEMP_FOLDER . '/resampled-' . mt_rand(100000, 999999) . '.' . $file->getExtension();

        $this->tmpImageContent = $this->transformed->getImageResource();

        // write to tmp file
        @file_put_contents($this->tmpImagePath, $this->tmpImageContent);

        $this->transformed->loadFrom($this->tmpImagePath);

        if ($this->transformed->getImageResource()) {
            return true;
        }
        return false;
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

    public function needsCompressing(): bool
    {
        return ($this->maxSizeInMb && $this->file->getAbsoluteSize() > $this->maxSizeInMb * 1024 * 1024);
    }

    protected function resize(): bool
    {
        $modified = false;
        // resize to max values
        if ($this->transformed && $this->needsResizing()) {
            if ($this->verbose) {
                echo 'Resizing ' . $this->file->getFilename() . ' to ' . $this->maxWidth . 'x' . $this->maxHeight . PHP_EOL;
            }
            if ($this->dryRun) {
                return false;
            }
            $modified = true;
            if ($this->maxWidth && $this->maxHeight) {
                $this->transformed = $this->transformed->resizeRatio($this->maxWidth, $this->maxHeight);
            } elseif ($this->maxWidth) {
                $this->transformed = $this->transformed->resizeByWidth($this->maxWidth);
            } else {
                $this->transformed = $this->transformed->resizeByHeight($this->maxHeight);
            }
        }
        return $modified;
    }

    protected function convertToWebp(): bool
    {
        $modified = false;
        // Convert to WebP and save
        if ($this->transformed && $this->needsConvertingToWebp()) {
            if ($this->verbose) {
                echo 'Converting ' . $this->file->getFilename() . ' to webp'. PHP_EOL;
            }
            if ($this->dryRun) {
                return false;
            }
            $modified = true;
            /**
             * @var  DBFile $tmpFile $tmpFile
             */
            $tmpFile = $this->file->Convert('webp');
            $this->deleteOldFile();
            $this->file->File = $tmpFile;
            $this->file->setFromString($tmpFile->getImageBackend()->getImageResource(), $this->file->getFilename().'.webp');
            $this->saveAndPublish($this->file);
            $this->loadBackend();

        }
        return $modified;
    }

    protected function compress(): bool
    {
        $modified = false;
        // Check if WebP is smaller
        if ($this->transformed && $this->needsCompressing()) {
            if ($this->verbose) {
                echo 'Compression ' . $this->file->getFilename() . ' to '.$this->maxSizeInMb.' megabytes' . PHP_EOL;
            }
            if ($this->dryRun) {
                return false;
            }
            $this->transformed->writeTo($this->tmpImagePath);
            $sizeCheck = $this->fileIsTooBig($this->tmpImagePath);
            $step = 1;
            while ($sizeCheck && $step > 0) {
                // reduce quality
                $modified = true;
                unlink($this->tmpImagePath);
                $this->transformed->setQuality($this->quality * $step * 100);
                $this->transformed->writeTo($this->tmpImagePath);
                // new round
                $sizeCheck = $this->fileIsTooBig($this->tmpImagePath);
                $step -= $this->qualityReductionIncrement;
            }
        }
        return $modified;
    }


    protected function writeToFile()
    {
        if ($this->dryRun) {
            return;
        }
        // write to tmp file and then overwrite original
        if ($this->transformed) {
            $this->transformed->writeTo($this->tmpImagePath);
            // if !legacy_filenames then delete original, else rogue copies are left on filesystem
            if (file_exists($this->tmpImagePath)) {
                $this->deleteOldFile();
                $this->file->setFromLocalFile($this->tmpImagePath, $this->file->getFilename()); // set new image
                $this->saveAndPublish($this->file);
            }
        }
    }

    protected function deleteOldFile()
    {
        if ($this->dryRun) {
            return;
        }
        if (!Config::inst()->get(FlysystemAssetStore::class, 'legacy_filenames')) {
            $this->file->File->deleteFile();
        }
    }

    protected function fileIsTooBig(string $filePath): bool
    {
        $fileSize = filesize($filePath);
        $maxSize = $this->maxSizeInMb * 1024 * 1024;
        if ($fileSize > $maxSize) {
            return true;
        }
        return false;
    }

    protected function saveAndPublish(Image $image)
    {
        if ($this->dryRun) {
            return;
        }
        $isPublished = $image->isPublished();
        $image->write();
        if ($isPublished) {
            $image->publishSingle();
        }
    }

}

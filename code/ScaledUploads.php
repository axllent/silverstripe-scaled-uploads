<?php
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

class ScaledUploads extends DataExtension {

	public static $max_width = 960;
	public static $max_height = 800;
	public static $exif_rotation = true;

	public function onAfterWrite() {
		$this->ScaleUpload();
	}

	public function getMaxWidth() {
		$w = Config::inst()->get('ScaledUploads', 'max-width');
		return ($w) ? $w : self::$max_width;
	}

	public function getMaxHeight() {
		$h = Config::inst()->get('ScaledUploads', 'max-height');
		return ($h) ? $h : self::$max_height;
	}

	public function getAutoRotate() {
		$r = Config::inst()->get('ScaledUploads', 'auto-rotate');
		if ($r === 0 || $r == 'false') return false;
		return self::$exif_rotation;
	}

	public function ScaleUpload() {
		$extension = strtolower($this->owner->getExtension());

		if($this->owner->getHeight() > $this->getMaxHeight() || $this->owner->getWidth() > $this->getMaxWidth()) {
			$original = $this->owner->getFullPath();
			$resampled = $original . '.tmp.' . $extension;
			$gd = new GD($original);
			/* Backwards compatibility with SilverStripe 3.0 */
			$image_loaded = (method_exists('GD', 'hasImageResource')) ? $gd->hasImageResource() : $gd->hasGD();
			if ($image_loaded) {
				/* Clone original */
				$transformed = $gd;
				/* If rotation allowed & JPG, test to see if orientation needs switching */
				if ($this->getAutoRotate() && preg_match('/jpe?g/i', $extension)) {
					$switchorientation = $this->exifRotation($original);
					if ($switchorientation) {
						$transformed = $transformed->rotate($switchorientation);
					}
				}
				/* Resize to max values */
				if ($transformed) {
					$transformed = $transformed->resizeRatio($this->getMaxWidth(), $this->getMaxHeight());
				}
				/* Overwrite original upload with resampled */
				if ($transformed) {
					$transformed->writeTo($resampled);
					unlink($original);
					rename($resampled, $original);
				}
			}
		}
	}

	/*
	 * exifRotation - return the exif rotation
	 * @param string $FileName
	 * @return int false|angle
	 */
	public function exifRotation($file) {
		$exif = @exif_read_data($file);
		if (!$exif) return false;
		$ort = @$exif['IFD0']['Orientation'];
		if (!$ort) $ort = @$exif['Orientation'];
		switch($ort) {
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
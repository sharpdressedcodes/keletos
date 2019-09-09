<?php

namespace Keletos\Utility;

class Image {

	/**
	 * Convert an image from one format to another format.
	 * Supported formats:
	 * jpg->png
	 * gif->png
	 * png->jpg
	 * png->gif
	 * gif->png
	 * gif->jpg
	 *
	 * @param string $fileName
	 * @param string $newFileName
	 * @param array $transparentColourRGB (Optional) Preserve transparency by setting this value
	 * @return bool true on success, false on failure.
	 */
	public static function convert($fileName, $newFileName, array $transparentColourRGB = array(255, 255, 255)){

		$result = false;

		if (file_exists($fileName)){

			$desiredExtension = FileSystem::getFileExtension($newFileName);
			$extension = FileSystem::getFileExtension($fileName);
			$image = null;

			switch (strtolower($extension)){
				case 'jpg':
				case 'jpeg':
					$image = imagecreatefromjpeg($fileName);
					break;
				case 'png':
					$image = imagecreatefrompng($fileName);
					break;
				case 'gif':
					$image = imagecreatefromgif($fileName);
					break;
				default:
			}

			if (!is_null($image)){

				if (file_exists($newFileName)){
					unlink($newFileName);
				}

				switch (strtolower($desiredExtension)){
					case 'jpg':
					case 'jpeg':
						$result = imagejpeg($image, $newFileName);
						break;
					case 'png':

						//imagealphablending($image, true);
						//$colour = imagecolorallocate($image, $transparentColourRGB[0], $transparentColourRGB[1], $transparentColourRGB[2]);
						//imagecolortransparent($image, $colour);

						imagealphablending($image, false);
						imagesavealpha($image, true);
						$colour = imagecolorallocatealpha($image, $transparentColourRGB[0], $transparentColourRGB[1], $transparentColourRGB[2], 127);
						imagecolortransparent($image, $colour);

						$result = imagepng($image, $newFileName);

						break;
					case 'gif':
						$result = imagegif($image, $newFileName);
						break;
					default:
				}

				imagedestroy($image);

			}

		}

		return $result;

	}

	/**
	 * Resize an image
	 *
	 * @param string $fileName The file to resize
	 * @param string $newFileName File name used to save the resized image
	 * @param int $height (Optional) If this is set and $width = 0, the width will be automatically calculated preserving aspect ratio
	 * @param int $width (Optional) If this is set and $height = 0, the height will be automatically calculated preserving aspect ratio
	 * @param array $transparentColourRGB (Optional) Preserve transparency by setting this value
	 * @return bool true on success, false on failure.
	 */
	public static function resize($fileName, $newFileName, $height = 0, $width = 0, array $transparentColourRGB = array()){

		$image = null;
		$result = false;
		$extension = FileSystem::getFileExtension($fileName);

		switch (strtolower($extension)){
			case 'jpg':
			case 'jpeg':
				$image = @imagecreatefromjpeg($fileName);
				break;
			case 'png':
				$image = @imagecreatefrompng($fileName);
				break;
			case 'gif':
				$image = @imagecreatefromgif($fileName);
				break;
			default:
		}

		if (is_resource($image)){

			$currentHeight = imagesy($image);
			$currentWidth = imagesx($image);

			if ($currentHeight > $currentWidth) {
				$aspectRatio = $currentWidth / $currentHeight;
			} elseif ($currentHeight < $currentWidth){
				$aspectRatio = $currentHeight / $currentWidth;
			} else {
				$aspectRatio = 1;
			}

			if ($height === 0){
				$height = $width * $aspectRatio;
			} elseif ($width === 0){
				$width = $height * $aspectRatio;
			}

			if (file_exists($newFileName)){
				unlink($newFileName);
			}

			if (extension_loaded('imagick')){

				$image = new \Imagick($fileName);
				$image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);
				$image->writeImage($newFileName);
				$image->destroy();

			} else {

				$desiredExtension = FileSystem::getFileExtension($newFileName);
				$newImage = imagecreatetruecolor($width, $height);

				if (strtolower(substr($desiredExtension, 0, 3)) === 'png' && !empty($transparentColourRGB)){
					imagealphablending($newImage, false);
					imagesavealpha($newImage, true);
					$colour = imagecolorallocatealpha($newImage, $transparentColourRGB[0], $transparentColourRGB[1], $transparentColourRGB[2], 127);
					imagefilledrectangle($newImage, 0, 0, $width, $height, $colour);
				}

				imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $currentWidth, $currentHeight);

				switch (strtolower(substr($desiredExtension, 0, 3))){
					case 'jpg':
					case 'jpeg':
						$result = imagejpeg($newImage, $newFileName);
						break;
					case 'png':
						$result = imagepng($newImage, $newFileName);
						break;
					case 'gif':
						$result = imagegif($newImage, $newFileName);
						break;
				}

				!is_null($newImage) && imagedestroy($newImage);

			}

			!is_null($image) && imagedestroy($image);

		}

		return $result;

	}

	public static function getDimensions($fileName){

		$result = null;

		if (file_exists($fileName)){
			$info = getimagesize($fileName);
			$result = array(
				'width' => $info[0],
				'height' => $info[1],
			);
		}

		return $result;

	}

	public static function getDimensionsFromString($imageData){

		$info = getimagesizefromstring($imageData);
		$result = array(
			'width' => $info[0],
			'height' => $info[1],
		);

		return $result;

	}

	public static function ensureDimensions($fileName, array $maxDimensions) {

		$dimensions = self::getDimensions($fileName);

		if (($maxDimensions['width'] > 0 && $dimensions['width'] > $maxDimensions['width']) ||
			($maxDimensions['height'] > 0 && $dimensions['height'] > $maxDimensions['height'])){

			$tempFileName = FileSystem::generateTempFileNameFromFileName($fileName);
			rename($fileName, $tempFileName);

			if ($dimensions['width'] > $maxDimensions['width']){
				self::resize($tempFileName, $fileName, 0, $maxDimensions['width']);
			} else {
				self::resize($tempFileName, $fileName, $maxDimensions['height'], 0);
			}

			unlink($tempFileName);

		}

	}

}

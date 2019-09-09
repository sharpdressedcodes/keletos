<?php
namespace Keletos\Utility;

class FileSystem {

	/**
	 * Get files within a directory.
	 *
	 * @param string $path The full path to the directory
	 * @param bool $recurse Get files in sub-directories too
	 * @param null $filter (Optional) A regular expression filter to apply
	 * @return array A list of files
	 */
	public static function getFiles($path, $recurse = false, $filter = null){

		$result = array();
		$iterator = $recurse ? new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS) : new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS);

		if ($recurse){
			$iterator = new \RecursiveIteratorIterator($iterator);
		}

		if (!is_null($filter)){
			$iterator = new \RegexIterator($iterator, $filter, \RegexIterator::ALL_MATCHES);
		}

		foreach ($iterator as $item){
			if ((!is_null($filter) && is_array($item) && !is_dir($item[0][0]) || !$item->isDir())){
				$sep = substr($path, strlen($path) - 1, 1) === DIRECTORY_SEPARATOR ? '' : DIRECTORY_SEPARATOR;
				$result[] = $path . $sep . ($recurse ? $iterator->getSubPathName() : $iterator->getFilename());
			}
		}

		return $result;

	}

	public static function getFilesAsJson($path, $recurse = false, $filter = null){

		$result = array();
		$files = self::getFiles($path, $recurse, $filter);

		foreach ($files as $file){
			$result[] = json_decode(file_get_contents($file), true);
		}

		return $result;

	}

	public static function createDirectory($directory) {

		$directory = rtrim($directory, "/\\");

		if (!file_exists($directory)) {
			mkdir($directory);
		}

	}

	public static function cloneDirectory($source, $destination, $contentsOnly = false){

		$result = false;

		try {

			if ($contentsOnly){

				foreach (
					$iterator = new \RecursiveIteratorIterator(
						new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
						\RecursiveIteratorIterator::SELF_FIRST) as $item) {
					if ($item->isDir()) {
						$result = self::cloneDirectory($source, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
						if (!$result)
							break;
					} else {
						self::copyFile($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
					}
				}

				return $result;

			}

			@mkdir($destination, 0755);

			foreach (
				$iterator = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
					\RecursiveIteratorIterator::SELF_FIRST) as $item) {
				if ($item->isDir()) {
					@mkdir($destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
				} else {
					self::copyFile($item, $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
				}
			}

			$result = true;

		} catch (\Exception $e){}

		return $result;

	}

	/**
	 * If the destination file exists, it will be deleted first.
	 */
	public static function copyFile($source, $destination){

		self::deleteFile($destination);
		copy($source, $destination);

	}

	public static function combinePaths($paths, $endWith = true){

		$result = implode(DIRECTORY_SEPARATOR, $paths);

		if ($endWith)
			$result .= DIRECTORY_SEPARATOR;

		return $result;

	}

	/**
	 * Generates a unique file name.
	 *
	 * @param string|null $directory (Optional) The directory to create the file name in. If this is null, the temp path is used.
	 * @return string Returns the full path to the file name.
	 */
	public static function generateTempFileName($directory = null) {

		if (is_null($directory)) {
			$directory = sys_get_temp_dir();
		}

		if (!GString::endsWith($directory, array('/', '\\'))) {
			$directory .= DIRECTORY_SEPARATOR;
		}

		$file = Uid::generate(16);

		while (file_exists($directory . $file)) {
			$file = Uid::generate(16);
		}

		return $directory . $file;

	}

	/**
	 * Creates a temporary file which will be deleted when the handle is closed.
	 *
	 * @return array('handle' => file_handle, 'path' => file_path);
	 */
	public static function createTempFile() {

		$handle = tmpfile();
		$metaData = stream_get_meta_data($handle);

		return array(
			'handle' => $handle,
			'path' => $metaData['uri'],
		);

	}

	/**
	 * Save a string to a file.
	 *
	 * @param string $fileName The full path to the file
	 * @param string $data The data to save
	 * @param bool $append (Optional) Append?
	 * @return bool Returns true if successful.
	 */
	public static function saveToFile($fileName, $data, $append = false) {

		$result = false;
		$f = new \Keletos\Component\Stream\File($fileName);

		if ($f->open(array('mode' => 'WRITE' . ($append ? '+APPEND' : '')))){
			$f->put($data);
			$f->close();
			$result = true;
		}

		return $result;

	}

	/**
	 * Read a string from a file.
	 *
	 * @param string $fileName The full path to the file
	 * @return string|null Returns the string if successful, null if not
	 */
	public static function readFromFile($fileName) {

		$result = null;
		$f = new \Keletos\Component\Stream\File($fileName);

		if (file_exists($fileName) && $f->open(array('mode' => 'READ'))) {
			$result = $f->get();
			$f->close();
		}

		return $result;

	}

	public static function deleteFile($fileName) {

		$result = false;

		if (file_exists($fileName)) {
			$result = unlink($fileName);
		}

		return $result;

	}

	public static function deleteDirectory($directory){

		$result = false;

		if (file_exists($directory)){
			self::deleteDirectoryContents($directory);
			rmdir($directory);
			$result = true;
		}

		return $result;

	}

	public static function deleteDirectoryContents($directory, $ignored = array()){

		$result =false;

		if (file_exists($directory)){
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($files as $fileinfo) {

				$toIgnore = false;
				foreach ($ignored as $ignore){
					if ($ignore === $fileinfo->getFilename()){
						$toIgnore = true;
						break;
					}
				}
				if ($toIgnore || $fileinfo->getRealPath() === $directory)
					continue;

				$todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
				$todo($fileinfo->getRealPath());
			}
			$result = true;
		}

		return $result;

	}

	public static function getFileName($fileName, $withExtension = true){

		$result = null;
		$fi = pathinfo($fileName);

		if (isset($fi['filename'])){
			$result = $fi['filename'];
			if ($withExtension){
				$result .= ".{$fi['extension']}";
			}
		}

		return $result;

	}

	public static function getFileExtension($fileName){

		$fi = pathinfo($fileName);

		return isset($fi['extension']) ? $fi['extension'] : null;

	}

	public static function generateTempFileNameFromFileName($fileName){

		$extension = self::getFileExtension($fileName);

		while (1){
			$random = Uid::generate(8);
			$temp = preg_replace("/.$extension$/i", "_temp$random.$extension", $fileName);
			if (!file_exists($temp)){
				$fileName = $temp;
				break;
			}
		}

		return $fileName;

	}

}

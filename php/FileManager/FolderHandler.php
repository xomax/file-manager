<?php
	namespace FileManager;

	use Symfony\Component\Filesystem\Filesystem;
	use Symfony\Component\Finder\Finder;
	use Sirius\Upload\Handler as UploadHandler;

	class FolderHandler {

		private $rootFolder;
		private $fileSystem;
		private $finder;
		private $folder;

		public function __construct ($rootFolder)
		{
			$this->rootFolder = $rootFolder;
			$this->fileSystem = new Filesystem();
			$this->finder = new Finder();
		}

		public function getFolders ($parentFolder = null)
		{
			$folders = [];
			if ($readFolders = $this->readFolder()) {
				if (count($readFolders) > 0) {
					$readFolders = iterator_to_array($readFolders);
					foreach ($readFolders as $folder) {
						if ($folder->getRelativePath() == $parentFolder) {
							$folders[] = $folder->getFilename();
						}
					}
				}
			}
			return $folders;
		}

		public function getFiles ($parentFolder = null)
		{
			$files = [];
			$folder = $this->rootFolder.'/'.$parentFolder;
			if ($this->fileSystem->exists($folder)) {
				$readFiles = $this->finder->files()
					->in($folder)
					->depth(0)
					->sortByName();
				if (count($readFiles) > 0) {
					return iterator_to_array($readFiles);
				}
			}
			return $files;
		}

		private function readFolder ()
		{
			if ($this->folder === null) {
				$this->folder = false;
				if ($this->fileSystem->exists($this->rootFolder)) {
					$this->folder = $this->finder->directories()
						->in($this->rootFolder)
						->sortByName();
				}
			}
			return $this->folder;
		}

		public function create ($folderName, $parent)
		{
			$this->fileSystem->mkdir($this->rootFolder.'/'.$parent.'/'.$folderName);
		}

		public function delete ($folderName)
		{
			$this->fileSystem->remove($this->rootFolder.'/'.$folderName);
		}

		public function upload ($folderName, $fileKey)
		{
			$uploader = new UploadHandler($this->rootFolder.'/'.$folderName);
//			$uploader->addRule('extension', ['allowed' => 'jpg', 'jpeg', 'png'], '{label} should be a valid image (jpg, jpeg, png)', 'Profile picture');
//			$uploader->addRule('size', ['max' => '20M'], '{label} should have less than {max}', 'Profile picture');
//			$uploader->addRule('imageratio', ['ratio' => 1], '{label} should be a sqare image', 'Profile picture');

			$result = $uploader->process($_FILES[$fileKey]);

			if ($result->isValid()) {
				try {
					$result->confirm(); // this will remove the .lock file

				} catch (\Exception $e) {
					// something wrong happened, we don't need the uploaded files anymore
					$result->clear();
					throw $e;
				}
			} else {
				// image was not moved to the container, where are error messages
				$messages = $result->getMessages();
			}
		}
	}

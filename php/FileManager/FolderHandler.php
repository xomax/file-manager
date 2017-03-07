<?php
	namespace FileManager;

	use Symfony\Component\Filesystem\Filesystem;
	use Symfony\Component\Finder\Finder;

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
					$readFiles = iterator_to_array($readFiles);
					foreach ($readFiles as $file) {
						$files[] = $file->getFilename();
					}
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
	}

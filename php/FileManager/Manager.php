<?php
	namespace FileManager;

	use Kumatch\FilenameNormalizer\Normalizer;
	use Symfony\Component\Finder\SplFileInfo;
	use wapmorgan\FileTypeDetector\Detector;

	class Manager {

		private $folder = 'uploads';
		private $folderHandler;

		/**
		 * @return \FileManager\FolderHandler
		 */
		public function getFolderHandler()
		{
			$this->loadFolderHandler();
			return $this->folderHandler;
		}

		private function loadFolderHandler()
		{
			if ($this->folderHandler === null) {
				$this->folderHandler = new FolderHandler($this->folder);
			}
		}

		/**
		 * @return string
		 */
		public function getFolder ()
		{
			return $this->folder;
		}

		/**
		 * @param string $folder
		 * @return Manager
		 */
		public function setFolder ( $folder )
		{
			$this->folder = $folder;
			return $this;
		}

		public function generateFolderNavigation ()
		{
			$r = '
				<li class="root">
					<a href="">/</a>
				</li>
			';
			$r .= $this->generateFolderNavigationDirecotries();
			return $r;
		}

		private function generateFolderNavigationDirecotries ($parent = null)
		{
			$r = '';
			if ($folders = $this->getFolderHandler()->getFolders($parent)) {
				foreach ($folders as $folder) {
					$path = $parent . ($parent != null ? '/' : '') . $folder;
					$subDirectories = $this->generateFolderNavigationDirecotries($path);
					$r .= '
						<li>
							<a href="'.$path.'">'.$folder.'</a>
							'.($subDirectories != '' ? '<ul>'.$subDirectories.'</ul>' : '').'
						</li>
					';
				}
			}
			return $r;
		}

		public function isAllowedAction ($action)
		{
			$allowed = ['new-folder', 'load-folder'];
			return in_array($action, $allowed);
		}

		public function perform ($action) {
			$r = [];
			if ($this->isAllowedAction($action)) {
				if ($action == 'new-folder') {
					$this->loadFolderHandler();
					$newFolderName = isset($_POST['value']) ? $this->normalize($_POST['value']) : null;
					$parentFolder = isset($_POST['parent']) ? $_POST['parent'] : null;
					if ($newFolderName != null) {
						$this->folderHandler->create($newFolderName, $parentFolder);
						$r['snippet']['navigator'] = '<ul>'.$this->generateFolderNavigation().'</ul>';
					}
				} elseif ($action == 'load-folder') {
					$this->loadFolderHandler();
					$folderName = isset($_POST['value']) ? $_POST['value'] : null;
					$files = $this->folderHandler->getFiles($folderName);
					$r['snippet']['browser'] = $this->addNewFilePlaceHolder();
					foreach ($files as $file) {
						$r['snippet']['browser'] .= $this->addFilePreview($file);
					}
				}
			}
			return $r;
		}

		private function normalize ($name)
		{
			return Normalizer::normalize($name);
		}

		private function addNewFilePlaceHolder ()
		{
			return '
				<a class="new-file">
					<span>Nahrát soubor</span>
				</a>
			';
		}

		private function addFilePreview (SplFileInfo $file)
		{
			$mime = Detector::detectByFilename($file->getRealPath());
			return '
				<a href="'.$file->getPathname().'">
					<figure>
						'.(is_array($mime) && $mime[0] == 'image' ? '<img src="https://s3-us-west-2.amazonaws.com/s.cdpn.io/123941/placeimg01.jpg" alt="">' : '').'
						<figcaption>
							'.$file->getFilename().'
						</figcaption>
					</figure>
					<span class="actions">
						<button class="select">Vybrat</button>
						<button class="delete">Smazat</button>
					</span>
				</a>
			';
		}
	}

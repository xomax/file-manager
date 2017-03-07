<?php
	namespace FileManager;

	use FileIconGenerator\Helper as IconHelper;
	use Kumatch\FilenameNormalizer\Normalizer;
	use Symfony\Component\Finder\SplFileInfo;
	use wapmorgan\FileTypeDetector\Detector;

	class Manager {

		private $folder = 'uploads';
		private $linkFolder = 'uploads';
		private $iconFolder = 'icons';

		/**
		 * @var FolderHandler
		 */
		private $folderHandler;

		/**
		 * @var IconHelper
		 */
		private $iconHandler;

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

		private function loadIconHandler()
		{
			if ($this->iconHandler === null) {
				$this->iconHandler = new IconHelper();
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

		/**
		 * @param string $folder
		 * @return Manager
		 */
		public function setLinkFolder ( $folder )
		{
			$this->linkFolder = $folder;
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
			$allowed = ['new-folder', 'load-folder', 'delete-folder', 'upload-file'];
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
						return $this->renderNavigation();
					}
				} elseif ($action == 'load-folder') {
					$this->loadFolderHandler();
					$folderName = isset($_POST['value']) ? $_POST['value'] : null;
					return $this->renderBrowser($folderName);
				} elseif ($action == 'delete-folder') {
					$this->loadFolderHandler();
					$folderName = isset($_POST['parent']) ? $_POST['parent'] : null;
					$this->folderHandler->delete($folderName);
					return $this->renderNavigation();
				} elseif ($action == 'upload-file') {
					$this->loadFolderHandler();
					$folderName = isset($_POST['parent']) ? $_POST['parent'] : null;
					$this->folderHandler->upload($folderName, 'file');
					return $this->renderBrowser($folderName);
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

		private function getIcon ($ext)
		{
			$fileName = $this->iconFolder.'/'.$ext.'.png';
			if (file_exists($fileName)) {
				return $fileName;
			}
			return null;
		}

		private function addFilePreview (SplFileInfo $file)
		{
			$mime = Detector::detectByFilename($file->getRealPath());
			$image = (is_array($mime) && $mime[0] == 'image' && file_exists($this->getThumbLinkPath($file->getPathname())) ? $this->getThumbLinkPath($file->getPathname()) : $this->getIcon($file->getExtension()));
			return '
				<a href="'.$this->getLinkPath($file->getPathname()).'">
					<figure>
						<span class="image">'.($image != '' ? '<img src="'.$image.'" alt="">' : '').'</span>
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

		private function getLinkPath ($link)
		{
			return $this->linkFolder.'/'.str_replace($this->folder.'/', '', $link);
		}

		private function getThumbLinkPath ($link)
		{
			return $this->linkFolder.'/_thumbs_/'.str_replace($this->folder.'/', '', $link);
		}

		private function renderBrowser ($folderName)
		{
			$this->loadIconHandler();
			$r = [];
			$files = $this->folderHandler->getFiles($folderName);
			$r['snippet']['browser'] = $this->addNewFilePlaceHolder();
			foreach ($files as $file) {
				$r['snippet']['browser'] .= $this->addFilePreview($file);
			}
			return $r;
		}

		private function renderNavigation ()
		{
			$r = [];
			$r['snippet']['navigator'] = '<ul>'.$this->generateFolderNavigation().'</ul>';
			return $r;
		}
	}

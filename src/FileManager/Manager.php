<?php
	namespace xomax\FileManager;

	use Camel\CaseTransformer;
	use Camel\Format\CamelCase;
	use Camel\Format\SpinalCase;
	use Kumatch\FilenameNormalizer\Normalizer;
	use Symfony\Component\Finder\SplFileInfo;
	use wapmorgan\FileTypeDetector\Detector;

	class Manager {

		private $folder = 'uploads';
		private $linkFolder = 'uploads';
		private $iconFolder = 'icons';
		private $allowedActions = ['load-folder'];

		/**
		 * @var FolderHandler
		 */
		private $folderHandler;

		/**
		 * @return \xomax\FileManager\FolderHandler
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

		/**
		 * @param string $folder
		 * @return Manager
		 */
		public function setIconFolder ( $folder )
		{
			$this->iconFolder = $folder;
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

		private function normalize ($name)
		{
			return Normalizer::normalize($name);
		}

		private function getLinkPath ($link)
		{
			return $this->linkFolder.'/'.str_replace($this->folder.'/', '', $link);
		}

		private function getThumbLinkPath ($link)
		{
			return $this->linkFolder.'/_thumbs_/'.str_replace($this->folder.'/', '', $link);
		}

		private function getInnerThumbLinkPath ($link)
		{
			return $this->folder.'/_thumbs_/'.str_replace($this->folder.'/', '', $link);
		}


		/**
		 * Perform actions
		 */
		public function perform ($action) {
			$r = [];
			if ($this->isAllowedAction($action)) {
				$transformer = new CaseTransformer(new SpinalCase, new CamelCase);
				$action = $transformer->transform('perform-'.$action);

				$value = isset($_POST['value']) ? trim($_POST['value']) : null;
				$parent = isset($_POST['parent']) ? trim($_POST['parent']) : null;

				$this->loadFolderHandler();
				$r = $this->$action($value, $parent);

			}
			return $r;
		}

		private function performNewFolder ($newFolderName, $parentFolder)
		{
			if ($newFolderName != null) {
				$newFolderName = $this->normalize($newFolderName);
				$this->folderHandler->create($newFolderName, $parentFolder);
				return $this->renderNavigation();
			}
			return [];
		}

		private function performLoadFolder ($folderName, $parentFolder)
		{
			return $this->renderBrowser($folderName);
		}

		private function performDeleteFolder ($folderName, $parentFolder)
		{
			if ($parentFolder != '') {
				$this->folderHandler->delete($parentFolder);
				$this->folderHandler->delete('_thumbs_/'.$parentFolder);
				return $this->renderNavigation();
			}
			return [];
		}

		private function performUploadFile ($folderName, $parentFolder)
		{
			$this->folderHandler->upload($parentFolder, 'file');
			return $this->renderBrowser($parentFolder);
		}

		private function performDeleteFile ($folderName, $parentFolder)
		{
			$this->folderHandler->deleteFile($parentFolder.'/'.$folderName);
			return [];
		}



		/**
		 * Render actions
		 */
		private function renderBrowser ($folderName)
		{
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
			return $this->iconFolder.'/'.$ext.'.png';
		}

		private function addFilePreview (SplFileInfo $file)
		{
			$mime = Detector::detectByFilename($file->getRealPath());
			$image = (is_array($mime) && $mime[0] == 'image' && file_exists($this->getInnerThumbLinkPath($file->getPathname())) ? $this->getThumbLinkPath($file->getPathname()) : $this->getIcon($file->getExtension()));
			return '
				<a href="'.$this->getLinkPath($file->getPathname()).'">
					<figure>
						<span class="image">'.($image != '' ? '<img src="'.$image.'" alt="">' : '').'</span>
						<figcaption>
							'.$file->getFilename().'
						</figcaption>
					</figure>
					<span class="actions">
						'.($this->isAllowedAction('pick-file') ? '<button class="select-file">Vybrat</button>' : '').'
						'.($this->isAllowedAction('delete-file') ? '<button class="delete-file">Smazat</button>' : '').'
					</span>
				</a>
			';
		}

		public function generateFolderNavigation ()
		{
			$r = '
				<li class="root">
					<a href="">/</a>
				</li>
			';
			$r .= $this->generateFolderNavigationDirectories();
			return $r;
		}

		public function generateFolderActions ()
		{
			$r = '';
			if ($this->isAllowedAction('new-folder')) {
				$r .= '<button class="new-folder">Nový adresář</button>';
			}
			if ($this->isAllowedAction('delete-folder')) {
				$r .= '<button class="delete-folder">Smazat adresář</button>';
			}
			return $r;
		}

		private function generateFolderNavigationDirectories ($parent = null)
		{
			$r = '';
			if ($folders = $this->getFolderHandler()->getFolders($parent)) {
				foreach ($folders as $folder) {
					$path = $parent . ($parent != null ? '/' : '') . $folder;
					$subDirectories = $this->generateFolderNavigationDirectories($path);
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


		/**
		 * Allowed actions
		 */
		public function allowDirectoriesManipulation () {
			$this->allowedActions[] = 'new-folder';
			$this->allowedActions[] = 'delete-folder';
		}

		public function allowFilesManipulation () {
			$this->allowedActions[] = 'upload-file';
			$this->allowedActions[] = 'delete-file';
		}

		public function allowFilePick () {
			$this->allowedActions[] = 'pick-file';
		}

		public function isAllowedAction ($action)
		{
			return in_array($action, $this->allowedActions);
		}

	}

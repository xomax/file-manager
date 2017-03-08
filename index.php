<?php
	require_once 'vendor/autoload.php';
	$manager = new \FileManager\Manager();
	$manager->setFolder(__DIR__.'/uploads');

	$manager->allowDirectoriesManipulation();
	$manager->allowFilesManipulation();
	$manager->allowFilePick();

	if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		$content = [];
		$action = isset($_GET['action']) ? $_GET['action'] : null;
		$content = $manager->perform($action);
		die(json_encode($content));
	}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="stylesheet" href="style/main.css">
	<link rel="stylesheet" href="style/jquery-confirm.min.css">
	<link rel="stylesheet" href="style/dropzone.min.css">
	<script type="application/javascript" src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
	<script type="application/javascript" src="scripts/jquery-confirm.min.js"></script>
	<script type="application/javascript" src="scripts/dropzone.min.js"></script>
	<script type="application/javascript" src="scripts/default.js"></script>
	<title>FileManager</title>
</head>
<body>
	<div class="file-manager">
		<nav>
			<div class="folders" id="snippet-navigator">
				<ul>
					<?
						echo $manager->generateFolderNavigation();
					?>
				</ul>
			</div>
			<div class="actions">
				<?
					echo $manager->generateFolderActions();
				?>
			</div>
		</nav>
		<section class="browser" id="snippet-browser"></section>
	</div>
</body>
</html>
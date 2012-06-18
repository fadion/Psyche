<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">
		<title><?= $site_title; ?></title>

		<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
		
	</head>

	<body>

		<h1><?= $title; ?></h1>

		<div class="content">
			<?php foreach ($users as $user): ?>
	<?= $user->name; ?><br>
<?php endforeach; ?>
		</div>

	</body>

</html>
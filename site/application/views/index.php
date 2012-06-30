<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">
		<title>Master Page</title>

		<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	</head>

	<body>

		<h1><?= $title; ?></h1>

		<div style="background: #eee; padding: 30px; margin-bottom: 20px; width: 60%; margin-right: 5%; float:left;">
			<?= Partial::reserve('content'); ?>
		</div>
		<div style="background: #b3cdc7; padding: 30px; margin-bottom: 20px; width: 25%; float:left;">
			<?= Partial::reserve('sidebar', 'Some default content if no partial replaces it.'); ?>
		</div>
		<div style="clear: both;"></div>

		<footer style="background: #eee; padding: 10px;">This is the footer</footer>		

	</body>

</html>
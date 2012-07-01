<? Partial::begin('content'); ?>
	<h2>Some title</h2>
	<p>This is some content</p>
<? Partial::end(); ?>

<?= Partial::inline('sidebar', 'Some short, inline content'); ?>

<? include($_view.'index.php'); ?>
<!DOCTYPE html>
<html lang="en">

	<head>
		<meta charset="utf-8">
		<title>Framework Test</title>

		<?= FW\Core\Asset::css('main'); ?>
	</head>

	<body>
		<div>
			<h1><?= $title; ?> <?= $version; ?></h1>
			<h2>Forms</h2>
			<?= FW\Core\Form::open(); ?>

			<p>
				<?= FW\Core\Form::label('Your Name', 'name'); ?>
				<?= FW\Core\Form::text('name', '', array('class=fancy')); ?>
			</p>

			<p>
				<?= FW\Core\Form::label('Your Email', 'email'); ?>
				<?= FW\Core\Form::text('email', '', array('class=fancy')); ?>
			</p>

			<p>
				<?= FW\Core\Form::checkbox('newsletter', 'Receive Newsletters?'); ?>
			</p>

			<p>
				<?= FW\Core\Form::label('Department', 'department'); ?>
				<?= FW\Core\Form::select('department', array('Support', 'Marketing', 'Technical')); ?>
			</p>

			<p>
				<?= FW\Core\Form::label('About Yourself', 'about'); ?>
				<?= FW\Core\Form::textarea('about', '', array('class=fancy', 'rows=5', 'cols=30')); ?>
			</p>
			
			<?= FW\Core\Form::button('GO'); ?>

			<?= FW\Core\Form::close(); ?>

			<div class="validation">
				<?php if (!is_null($errors)): ?>
					<?php foreach ($errors as $key => $val): ?>
						<?= $key.' '.$val; ?><br>
					<?php endforeach; ?>
				<?php else: ?>
					<?= $success; ?>
				<?php endif; ?>
			</div>
		</div>

	</body>

</html>
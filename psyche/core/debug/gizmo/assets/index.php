<?= str_repeat("\n", 50); ?>
<script type="text/javascript"><?= file_get_contents(__DIR__.'/jquery.js'); ?></script>
<script type="text/javascript"><?= file_get_contents(__DIR__.'/scripts.js'); ?></script>
<style type="text/css"><?= file_get_contents(__DIR__.'/styles.css'); ?></style>

<div id="gizmo-console"><?= $console; ?></div>
<div id="gizmo-toolbar">
	<div class="gizmo-console">Debug Console</div>
	<div class="gizmo-clock"><?= $execution_time; ?></div>
	<div class="gizmo-ram"><?= $memory; ?> RAM</div>
	<div class="gizmo-controller"><?= $controller; ?></div>
	<div class="gizmo-close">Close</div>
</div>
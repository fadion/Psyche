<?php echo str_repeat("\n", 50); ?>
<?php if (config('gizmo load jquery')): ?>
<script type="text/javascript"><?php echo file_get_contents(__DIR__.'/jquery.js'); ?></script>
<?php endif; ?>
<script type="text/javascript"><?php echo file_get_contents(__DIR__.'/scripts.js'); ?></script>
<style type="text/css"><?php echo file_get_contents(__DIR__.'/styles.css'); ?></style>

<div id="gizmo-console"><?php echo $console; ?></div>
<div id="gizmo-toolbar">
	<div class="gizmo-console">Debug Console</div>
	<div class="gizmo-clock"><?php echo $execution_time; ?></div>
	<div class="gizmo-ram"><?php echo $memory; ?> RAM</div>
	<div class="gizmo-queries"><?php echo $queries; ?> Queries</div>
	<div class="gizmo-controller"><?php echo $controller; ?></div>
	<div class="gizmo-close">Close</div>
</div>
<?php
// $Id: dashboard_view_form.tpl.php,v 1.0 2017/11/28 shafiq Exp $
global $base_url;
/**
 * @file dashboard_view_form.tpl.php
 * Default theme implementation for dashboard view form information.
 *
 */
$form = $variables['form'];
?>
<div class="dashboard-view">
	<div class="content-row clearfix">
		<?php print drupal_render($form['results']); ?>
	</div>

	<div class="content-row actions clearfix">
		<?php print drupal_render($form['save']); ?>
		<?php print drupal_render($form['cancel']); ?>
	</div>

	<?php print drupal_render_children($form); ?>
</div>

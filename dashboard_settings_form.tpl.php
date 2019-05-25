<?php
// $Id: dashboard_settings_form.tpl.php,v 1.0 2017/11/28 shafiq Exp $
global $base_url;
/**
 * @file dashboard_settings_form.tpl.php
 * Default theme implementation for dashboard settings form information.
 *
 */
$form = $variables['form'];
?>
<div class="dashboard-settings">
	<div class="content-row clearfix">
		<table cellpadding="0" cellspacing="0" class="dashboard-settings-table">
		<tr>
		  <th colspan="3">Select Sections to Auto Refresh</th>
		</tr>
		<tr>
		  <td><?php print drupal_render($form['mrdash_rx_order_amount']); ?></td>
		  <td><?php print drupal_render($form['mrdash_po_amount']); ?></td>
		  <td><?php print drupal_render($form['mrdash_sales_amount']); ?></td>
		</tr>
		<tr>
		  <td><?php print drupal_render($form['mrdash_rx_payments']); ?></td>
		  <td><?php print drupal_render($form['mrdash_order_submitted']); ?></td>
		  <td><?php print drupal_render($form['mrdash_rx_pending']); ?></td>
		</tr>
		<tr>
		  <td><?php print drupal_render($form['mrdash_rx_scheduled']); ?></td>
		  <td><?php print drupal_render($form['mrdash_rx_upcoming_refills']); ?></td>
		  <td><?php print drupal_render($form['mrdash_expiring_rx']); ?></td>
		</tr>
		<tr>
		  <td><?php print drupal_render($form['mrdash_expiring_arb']); ?></td>
		  <td><?php print drupal_render($form['mrdash_expiring_cc']); ?></td>
		  <td><?php print drupal_render($form['mrdash_expiring_profile_cc']); ?></td>
		</tr>
		<tr>
		  <td><?php print drupal_render($form['mrdash_total_clinics']); ?></td>
		  <td><?php print drupal_render($form['mrdash_silentpost']); ?></td>
		  <td><?php print drupal_render($form['mrdash_rx_created_refills']); ?></td>
		</tr>
		<tr>
		  <td><?php print drupal_render($form['mrdash_clinic_vs_staff']); ?></td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
		</table>
	</div>
	<div class="content-row clearfix">
		<?php print drupal_render($form['dashboard_refresh_interval']); ?>
	</div>

	<div class="content-row actions clearfix">
		<?php print drupal_render($form['save']); ?>
		<?php print drupal_render($form['cancel']); ?>
	</div>

	<?php print drupal_render_children($form); ?>
</div>


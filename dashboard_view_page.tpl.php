<?php
// $Id: dashboard_view_page.tpl.php,v 1.0 2017/10/28 shafiq Exp $
global $base_url, $user;
/**
 * @file dashboard_view_page.tpl.php
 * theme implementation for dashboard information.
 *
 *
 * @see theme_dashboard_view_page()
 */
?>
<div class="dashboard">
	<div class="content-row dashboard-row-1 clearfix">
		<div class="dashboard-block daily-total-rx-amount" id="daily-total-rx-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL RX AMOUNT</div>
			<div class="dashboard-block-date">Today | <?php print $total_rx_amount_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_rx_amount; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_rx_amount_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_rx_amount_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_rx_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="report-block"><a target="_blank" class="rx-amount-report-link" href="/rx_reports/rx/sales"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-amount-refresh-link use-ajax" href="/dashboard/total_rx_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block daily-total-order-amount" id="daily-total-order-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL STORE AMOUNT</div>
			<div class="dashboard-block-date">Today | <?php print $total_order_amount_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_order_amount; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_order_amount_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_order_amount_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_order_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="report-block"><a target="_blank" class="order-amount-report-link" href="/rx_reports/rx/sales"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="order-amount-refresh-link use-ajax" href="/dashboard/total_order_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block weekly-total-amount" id="weekly-total-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL WEEKLY AMOUNT</div>
			<div class="dashboard-block-date">This Week | <?php print $total_weekly_amount_week; ?></div>
			<div class="dashboard-block-amount-type">Rx | Store</div>
			<div class="dashboard-block-amount"><span class="rx-amount"><?php print $weekly_total_rx_amount; ?></span> | <span class="order-amount"><?php print $weekly_total_order_amount; ?></span></div>
			<div class="dashboard-block-amount-percent"><span class="rx-amount-percent"><?php print $weekly_total_rx_amount_percent; ?></span>&nbsp;&nbsp;<span class="rx-amount-percent-marker"><?php print $weekly_total_rx_amount_marker; ?></span> | <span class="order-amount-percent"><?php print $weekly_total_order_amount_percent; ?></span>&nbsp;&nbsp;<span class="order-amount-percent-marker"><?php print $weekly_total_order_amount_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $weekly_rx_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="refresh-block"><a class="weekly-amount-refresh-link use-ajax" href="/dashboard/total_weekly_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block monthly-total-amount" id="monthly-total-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL MONTHLY AMOUNT</div>
			<div class="dashboard-block-date">This Month | <?php print $total_monthly_amount_month; ?>, <?php print $total_monthly_amount_year; ?></div>
			<div class="dashboard-block-amount-type">Rx | Store</div>
			<div class="dashboard-block-amount"><span class="rx-amount"><?php print $monthly_total_rx_amount; ?></span> | <span class="order-amount"><?php print $monthly_total_order_amount; ?></span></div>
			<div class="dashboard-block-amount-percent"><span class="rx-amount-percent"><?php print $monthly_total_rx_amount_percent; ?></span>&nbsp;&nbsp;<span class="rx-amount-percent-marker"><?php print $monthly_total_rx_amount_marker; ?></span> | <span class="order-amount-percent"><?php print $monthly_total_order_amount_percent; ?></span>&nbsp;&nbsp;<span class="order-amount-percent-marker"><?php print $monthly_total_order_amount_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $monthly_rx_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="refresh-block"><a class="monthly-amount-refresh-link use-ajax" href="/dashboard/total_monthly_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>
	</div>

	<div class="content-row dashboard-row-10 clearfix">
		<div class="dashboard-block daily-total-po-amount" id="daily-total-po-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL PO AMOUNT</div>
			<div class="dashboard-block-date">Today | <?php print $total_po_amount_date; ?></div>
			<div class="dashboard-block-amount-type">Amount | Count</div>
			<div class="dashboard-block-amount"><span class="po-amount"><?php print $daily_total_po_amount; ?></span> | <span class="po-created"><?php print $daily_total_po_created; ?></span></div>
			<div class="dashboard-block-amount-percent"><span class="po-amount-percent"><?php print $daily_total_po_amount_percent; ?></span>&nbsp;&nbsp;<span class="po-amount-percent-marker"><?php print $daily_total_po_amount_marker; ?></span> | <span class="po-created-percent"><?php print $daily_total_po_created_percent; ?></span>&nbsp;&nbsp;<span class="po-created-percent-marker"><?php print $daily_total_po_created_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_po_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="report-block"><a target="_blank" class="po-amount-report-link" href="/rx_reports/rx/sales"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="po-amount-refresh-link use-ajax" href="/dashboard/total_po_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block daily-total-po-refund-amount" id="daily-total-po-refund-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL PO REFUND AMOUNT</div>
			<div class="dashboard-block-date">Today | <?php print $total_po_refund_amount_date; ?></div>
			<div class="dashboard-block-amount-type">Amount | Count</div>
			<div class="dashboard-block-amount"><span class="po-refund-amount"><?php print $daily_total_po_refund_amount; ?></span> | <span class="po-refund-created"><?php print $daily_total_po_refund_created; ?></span></div>
			<div class="dashboard-block-amount-percent"><span class="po-refund-amount-percent"><?php print $daily_total_po_refund_amount_percent; ?></span>&nbsp;&nbsp;<span class="po-refund-amount-percent-marker"><?php print $daily_total_po_refund_amount_marker; ?></span> | <span class="po-refund-created-percent"><?php print $daily_total_po_refund_created_percent; ?></span>&nbsp;&nbsp;<span class="po-refund-created-percent-marker"><?php print $daily_total_po_refund_created_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_po_refund_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="report-block"><a target="_blank" class="po-refund-amount-report-link" href="/rx_reports/rx/sales"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="po-refund-amount-refresh-link use-ajax" href="/dashboard/total_po_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block weekly-total-po-amount" id="weekly-total-po-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">WEEKLY PO AMOUNT</div>
			<div class="dashboard-block-date">This Week | <?php print $total_weekly_po_amount_week; ?></div>
			<div class="dashboard-block-amount-type">PO | Refund</div>
			<div class="dashboard-block-amount"><span class="po-amount"><?php print $weekly_total_po_amount; ?></span> | <span class="po-refund-amount"><?php print $weekly_total_po_refund_amount; ?></span></div>
			<div class="dashboard-block-amount-percent"><span class="po-amount-percent"><?php print $weekly_total_po_amount_percent; ?></span>&nbsp;&nbsp;<span class="po-amount-percent-marker"><?php print $weekly_total_po_amount_marker; ?></span> | <span class="po-refund-amount-percent"><?php print $weekly_total_po_refund_amount_percent; ?></span>&nbsp;&nbsp;<span class="po-refund-amount-percent-marker"><?php print $weekly_total_po_refund_amount_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $weekly_po_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="refresh-block"><a class="weekly-po-amount-refresh-link use-ajax" href="/dashboard/total_weekly_po_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block monthly-total-po-amount" id="monthly-total-po-amount">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">MONTHLY PO AMOUNT</div>
			<div class="dashboard-block-date">This Month | <?php print $total_monthly_po_amount_month; ?>, <?php print $total_monthly_po_amount_year; ?></div>
			<div class="dashboard-block-amount-type">PO | Refund</div>
			<div class="dashboard-block-amount"><span class="po-amount"><?php print $monthly_total_po_amount; ?></span> | <span class="po-refund-amount"><?php print $monthly_total_po_refund_amount; ?></span></div>
			<div class="dashboard-block-amount-percent"><span class="po-amount-percent"><?php print $monthly_total_po_amount_percent; ?></span>&nbsp;&nbsp;<span class="po-amount-percent-marker"><?php print $monthly_total_po_amount_marker; ?></span> | <span class="po-refund-amount-percent"><?php print $monthly_total_po_refund_amount_percent; ?></span>&nbsp;&nbsp;<span class="po-refund-amount-percent-marker"><?php print $monthly_total_po_refund_amount_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $monthly_po_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="refresh-block"><a class="monthly-po-amount-refresh-link use-ajax" href="/dashboard/total_monthly_po_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>
	</div>


	<div class="content-row dashboard-row-2 clearfix">
		<div class="dashboard-block monthly-chart-rx-amount" id="monthly-chart-rx-amount">
		  <div class="dashboard-block-content">
			<div id="monthly-rx-amount-container"></div>
			<script type="text/javascript">
			Highcharts.chart('monthly-rx-amount-container', {
				chart: {
					type: 'area'
				},
				title: {
					text: 'MONTHLY RX AMOUNT',
					style: {
						color: '#B5CA92',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Year: <?php print $total_monthly_rx_amount_year; ?>'
				},
				xAxis: {
        			type: 'category',
					title: {
						text: 'Month'
					},
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Amount ($)'
					}
				},
				plotOptions: {
					area: {
						pointStart: 0,
						marker: {
							enabled: false,
							symbol: 'circle',
							radius: 2,
							states: {
								hover: {
									enabled: true
								}
							}
						}
					}
				},
				series: [{
					name: 'Amount',
					data: [<?php print $chart_monthly_rx_amounts; ?>],
        			color: '#B5CA92'
				}]
			});
			</script>
		  </div>
		</div>

		<div class="dashboard-block monthly-chart-order-amount" id="monthly-chart-order-amount">
		  <div class="dashboard-block-content">
			<div id="monthly-order-amount-container"></div>
			<script type="text/javascript">
			Highcharts.chart('monthly-order-amount-container', {
				chart: {
					type: 'area'
				},
				title: {
					text: 'MONTHLY STORE AMOUNT',
					style: {
						color: '#DB843D',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Year: <?php print $total_monthly_order_amount_year; ?>'
				},
				xAxis: {
        			type: 'category',
					title: {
						text: 'Month'
					},
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Amount ($)'
					}
				},
				plotOptions: {
					area: {
						pointStart: 0,
						marker: {
							enabled: false,
							symbol: 'circle',
							radius: 2,
							states: {
								hover: {
									enabled: true
								}
							}
						}
					}
				},
				series: [{
					name: 'Amount',
					data: [<?php print $chart_monthly_order_amounts; ?>],
        			color: '#DB843D'
				}]
			});
			</script>
		  </div>
		</div>

		<div class="dashboard-block monthly-chart-rx-and-order" id="monthly-chart-rx-and-order">
		  <div class="dashboard-block-content">
			<div id="monthly-rx-order-amount-container"></div>
			<script type="text/javascript">
			Highcharts.chart('monthly-rx-order-amount-container', {
				chart: {
					type: 'column'
				},
				title: {
					text: 'MONTHLY RX vs STORE vs PO',
					style: {
						color: '#01C1EA',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Year: <?php print $chart_compare_monthly_amount_year; ?>'
				},
				xAxis: {
        			type: 'category',
					title: {
						text: 'Month'
					},
					categories: [
						'Jan',
						'Feb',
						'Mar',
						'Apr',
						'May',
						'Jun',
						'Jul',
						'Aug',
						'Sep',
						'Oct',
						'Nov',
						'Dec'
					],
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Amount ($)'
					}
				},
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y:.2f} USD</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					},
					series: {
						borderWidth: 0,
						dataLabels: {
							enabled: true,
							format: '{point.y:.0f}',
							rotation: 0,
							crop: false,
							overflow: 'none',
							inside: false
						}
					}
				},
				series: [
				{
					name: '<?php print $chart_compare_monthly_rx_label_prev; ?>',
    				color: '#286198',
					data: [<?php print $chart_compare_monthly_rx_amounts_prev; ?>]

				}, {
					name: '<?php print $chart_compare_monthly_rx_label_current; ?>',
    				color: '#2b908f',
					data: [<?php print $chart_compare_monthly_rx_amounts_current; ?>]

				}, {
					name: '<?php print $chart_compare_monthly_order_label_prev; ?>',
    				color: '#e4d354',
					data: [<?php print $chart_compare_monthly_order_amounts_prev; ?>]
				}, {
					name: '<?php print $chart_compare_monthly_order_label_current; ?>',
    				color: '#f7a35c',
					data: [<?php print $chart_compare_monthly_order_amounts_current; ?>]
				}, {
					name: '<?php print $chart_compare_monthly_po_label_prev; ?>',
    				color: '#f15c80',
					data: [<?php print $chart_compare_monthly_po_amounts_prev; ?>]
				}, {
					name: '<?php print $chart_compare_monthly_po_label_current; ?>',
    				color: '#434348',
					data: [<?php print $chart_compare_monthly_po_amounts_current; ?>]
				}]
			});
			</script>
		  </div>
		</div>
	</div>

	<div class="content-row dashboard-row-3 clearfix">
		<div class="dashboard-block daily-rx-slit-created" id="daily-rx-slit-created">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL SLIT CREATED</div>
			<div class="dashboard-block-date">Today | <?php print $total_slit_scit_created_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_slit_created; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_slit_created_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_slit_created_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_slit_scit_created_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/daily/slit/created/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-slit-scit-created-refresh-link use-ajax" href="/dashboard/total_slit_scit_created/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block daily-rx-scit-created" id="daily-rx-scit-created">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL SCIT CREATED</div>
			<div class="dashboard-block-date">Today | <?php print $total_slit_scit_created_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_scit_created; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_scit_created_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_scit_created_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_slit_scit_created_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/daily/scit/created/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-slit-scit-created-refresh-link use-ajax" href="/dashboard/total_slit_scit_created/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block daily-rx-slit-refills" id="daily-rx-slit-refills">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL SLIT REFILLS</div>
			<div class="dashboard-block-date">Today | <?php print $total_slit_refills_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_slit_refills; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_slit_refills_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_slit_refills_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_slit_scit_refills_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/daily/slit/refills/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-slit-scit-refills-refresh-link use-ajax" href="/dashboard/total_slit_scit_refills/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block daily-rx-scit-refills" id="daily-rx-scit-refills">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL SCIT REFILLS</div>
			<div class="dashboard-block-date">Today | <?php print $total_scit_refills_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_scit_refills; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_scit_refills_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_scit_refills_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_slit_scit_refills_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="rx-display-link ctools-use-modal" href="/dashboard/report/rx_created_refills/daily/scit/refills/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-slit-scit-refills-refresh-link use-ajax" href="/dashboard/total_slit_scit_refills/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>
	</div>

	<div class="content-row dashboard-row-4 clearfix">
		<div class="dashboard-block monthly-chart-slit-vs-scit-created" id="monthly-chart-slit-vs-scit-created">
		  <div class="dashboard-block-content">
			<div id="monthly-slit-vs-scit-created-container"></div>
			<script type="text/javascript">
			Highcharts.chart('monthly-slit-vs-scit-created-container', {
				chart: {
					type: 'column'
				},
				title: {
					text: 'MONTHLY SLIT vs SCIT CREATED',
					style: {
						color: '#2E7AA2',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Year: <?php print $monthly_slit_vs_scit_created_year; ?>'
				},
				legend: {
					enabled: true
				},
				xAxis: {
        			type: 'category',
					title: {
						text: 'Month'
					},
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Count'
					}
				},
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y}</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					},
					series: {
						borderWidth: 0,
						dataLabels: {
							enabled: true,
							format: '{point.y:.0f}',
							rotation: 0,
							crop: false,
							overflow: 'none',
							inside: false
						}
					}
				},
				series: [{
					name: 'SLIT',
    				color: '#f15c80',
					data: [<?php print $chart_monthly_compare_slit_created; ?>]
				}, {
					name: 'SCIT',
    				color: '#e4d354',
					data: [<?php print $chart_monthly_compare_scit_created; ?>]
				}]
			});
			</script>
		  </div>
		</div>

		<div class="dashboard-block monthly-chart-slit-vs-scit-refills" id="monthly-chart-slit-vs-scit-refills">
		  <div class="dashboard-block-content">
			<div id="monthly-slit-vs-scit-refills-container"></div>
			<script type="text/javascript">
			Highcharts.chart('monthly-slit-vs-scit-refills-container', {
				chart: {
					type: 'column'
				},
				title: {
					text: 'MONTHLY SLIT vs SCIT REFILLS',
					style: {
						color: '#2E7AA2',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Year: <?php print $monthly_slit_vs_scit_refills_year; ?>'
				},
				xAxis: {
        			type: 'category',
					title: {
						text: 'Month'
					},
					categories: [
						'Jan',
						'Feb',
						'Mar',
						'Apr',
						'May',
						'Jun',
						'Jul',
						'Aug',
						'Sep',
						'Oct',
						'Nov',
						'Dec'
					],
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Count'
					}
				},
				legend: {
					enabled: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					},
					series: {
						borderWidth: 0,
						dataLabels: {
							enabled: true,
							format: '{point.y:.0f}',
							rotation: 0,
							crop: false,
							overflow: 'none',
							inside: false
						}
					}
				},
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y}</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				series: [
				{
					name: '<?php print $chart_monthly_compare_slit_refills_label_prev; ?>',
    				color: '#f15c80',
					data: [<?php print $chart_monthly_compare_slit_refills_prev; ?>]

				}, {
					name: '<?php print $chart_monthly_compare_slit_refills_label_current; ?>',
    				color: '#e4d354',
					data: [<?php print $chart_monthly_compare_slit_refills_current; ?>]

				}, {
					name: '<?php print $chart_monthly_compare_scit_refills_label_prev; ?>',
    				color: '#2b908f',
					data: [<?php print $chart_monthly_compare_scit_refills_prev; ?>]

				}, {
					name: '<?php print $chart_monthly_compare_scit_refills_label_current; ?>',
    				color: '#f7a35c',
					data: [<?php print $chart_monthly_compare_scit_refills_current; ?>]

				}]
			});
			</script>
		  </div>
		</div>

		<div class="dashboard-block monthly-slit-vs-scit" id="monthly-slit-vs-scit">
		  <div class="dashboard-block-content">
		    <div class="dashboard-block-table" id="monthly-table-slit-vs-scit">
			  <?php print $comparison_table_slit_vs_scit; ?>
		    </div>
		    <div class="dashboard-block-separator"></div>
		    <div class="dashboard-block-links">
			  <span class="last-update-block"><?php print $rx_slit_vs_scit_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
			  <span class="refresh-block"><a class="rx-slit-vs-scit-link use-ajax" href="/dashboard/rx_slit_vs_scit/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
		    </div>
		  </div>
		</div>

	</div>

	<div class="content-row dashboard-row-12 clearfix">
		<div class="dashboard-block daily-clinic-vs-staff-rx-created" id="daily-clinic-vs-staff-rx-created">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">CLINIC VS STAFF RX CREATED</div>
			<div class="dashboard-block-date">Today | <?php print $clinic_vs_staff_rx_created_date; ?></div>
			<div class="dashboard-block-data">
			  <table cellspacing="0" cellpadding="5" class="table_clinic_vs_staff daily_table_clinic_vs_staff_rx_created">
			  <tr>
				<th colspan="2" class="header-top">Clinic</th>
				<th colspan="2" class="header-top">Staff</th>
			  </tr>
			  <tr>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
			  </tr>
			  <tr>
				<td><span class="clinic-slit-created"><?php print $date_clinic_total_slit_created; ?></span></td>
				<td><span class="clinic-scit-created"><?php print $date_clinic_total_scit_created; ?></span></td>
				<td><span class="staff-slit-created"><?php print $date_staff_total_slit_created; ?></span></td>
				<td><span class="staff-scit-created"><?php print $date_staff_total_scit_created; ?></span></td>
			  </tr>
			  <tr>
				<td><div class="dashboard-block-created-percent"><span class="clinic-slit-created-percent"><?php print $date_clinic_total_slit_created_percent; ?></span>&nbsp;&nbsp;<span class="clinic-slit-created-marker"><?php print $date_clinic_total_slit_created_marker; ?></span></div></td>
				<td><div class="dashboard-block-created-percent"><span class="clinic-scit-created-percent"><?php print $date_clinic_total_scit_created_percent; ?></span>&nbsp;&nbsp;<span class="clinic-scit-created-marker"><?php print $date_clinic_total_scit_created_marker; ?></span></div></td>
				<td><div class="dashboard-block-created-percent"><span class="staff-slit-created-percent"><?php print $date_staff_total_slit_created_percent; ?></span>&nbsp;&nbsp;<span class="staff-slit-created-marker"><?php print $date_staff_total_slit_created_marker; ?></span></div></td>
				<td><div class="dashboard-block-created-percent"><span class="staff-scit-created-percent"><?php print $date_staff_total_scit_created_percent; ?></span>&nbsp;&nbsp;<span class="staff-scit-created-marker"><?php print $date_staff_total_scit_created_marker; ?></span></div></td>
			  </tr>
			  </table>
			</div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $daily_clinic_vs_staff_rx_created_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="clinic-vs-staff-rx-created-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/daily/created/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="clinic-vs-staff-rx-created-refresh-link use-ajax" href="/dashboard/clinic_vs_staff_rx_created/daily/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block monthly-clinic-vs-staff-rx-created" id="monthly-clinic-vs-staff-rx-created">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">CLINIC VS STAFF RX CREATED</div>
			<div class="dashboard-block-date">This Month | <?php print $clinic_vs_staff_rx_created_month; ?></div>
			<div class="dashboard-block-data">
			  <table cellspacing="0" cellpadding="5" class="table_clinic_vs_staff monthly_table_clinic_vs_staff_rx_created">
			  <tr>
				<th colspan="2" class="header-top">Clinic</th>
				<th colspan="2" class="header-top">Staff</th>
			  </tr>
			  <tr>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
			  </tr>
			  <tr>
				<td><span class="clinic-slit-created"><?php print $monthly_clinic_total_slit_created; ?></span></td>
				<td><span class="clinic-scit-created"><?php print $monthly_clinic_total_scit_created; ?></span></td>
				<td><span class="staff-slit-created"><?php print $monthly_staff_total_slit_created; ?></span></td>
				<td><span class="staff-scit-created"><?php print $monthly_staff_total_scit_created; ?></span></td>
			  </tr>
			  <tr>
				<td><div class="dashboard-block-created-percent"><span class="clinic-slit-created-percent"><?php print $monthly_clinic_total_slit_created_percent; ?></span>&nbsp;&nbsp;<span class="clinic-slit-created-marker"><?php print $monthly_clinic_total_slit_created_marker; ?></span></div></td>
				<td><div class="dashboard-block-created-percent"><span class="clinic-scit-created-percent"><?php print $monthly_clinic_total_scit_created_percent; ?></span>&nbsp;&nbsp;<span class="clinic-scit-created-marker"><?php print $monthly_clinic_total_scit_created_marker; ?></span></div></td>
				<td><div class="dashboard-block-created-percent"><span class="staff-slit-created-percent"><?php print $monthly_staff_total_slit_created_percent; ?></span>&nbsp;&nbsp;<span class="staff-slit-created-marker"><?php print $monthly_staff_total_slit_created_marker; ?></span></div></td>
				<td><div class="dashboard-block-created-percent"><span class="staff-scit-created-percent"><?php print $monthly_staff_total_scit_created_percent; ?></span>&nbsp;&nbsp;<span class="staff-scit-created-marker"><?php print $monthly_staff_total_scit_created_marker; ?></span></div></td>
			  </tr>
			  </table>
			</div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $monthly_clinic_vs_staff_rx_created_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="clinic-vs-staff-rx-created-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/list/monthly/created/<?php print date('n'); ?>/<?php print date('Y'); ?>/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="clinic-vs-staff-rx-created-refresh-link use-ajax" href="/dashboard/clinic_vs_staff_rx_created/monthly/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block daily-clinic-vs-staff-rx-refill" id="daily-clinic-vs-staff-rx-refill">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">CLINIC VS STAFF RX REFILLS</div>
			<div class="dashboard-block-date">Today | <?php print $clinic_vs_staff_rx_refill_date; ?></div>
			<div class="dashboard-block-data">
			  <table cellspacing="0" cellpadding="5" class="table_clinic_vs_staff daily_table_clinic_vs_staff_rx_refill">
			  <tr>
				<th colspan="2" class="header-top">Clinic</th>
				<th colspan="2" class="header-top">Staff</th>
			  </tr>
			  <tr>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
			  </tr>
			  <tr>
				<td><span class="clinic-slit-refill"><?php print $date_clinic_total_slit_refill; ?></span></td>
				<td><span class="clinic-scit-refill"><?php print $date_clinic_total_scit_refill; ?></span></td>
				<td><span class="staff-slit-refill"><?php print $date_staff_total_slit_refill; ?></span></td>
				<td><span class="staff-scit-refill"><?php print $date_staff_total_scit_refill; ?></span></td>
			  </tr>
			  <tr>
				<td><div class="dashboard-block-refill-percent"><span class="clinic-slit-refill-percent"><?php print $date_clinic_total_slit_refill_percent; ?></span>&nbsp;&nbsp;<span class="clinic-slit-refill-marker"><?php print $date_clinic_total_slit_refill_marker; ?></span></div></td>
				<td><div class="dashboard-block-refill-percent"><span class="clinic-scit-refill-percent"><?php print $date_clinic_total_scit_refill_percent; ?></span>&nbsp;&nbsp;<span class="clinic-scit-refill-marker"><?php print $date_clinic_total_scit_refill_marker; ?></span></div></td>
				<td><div class="dashboard-block-refill-percent"><span class="staff-slit-refill-percent"><?php print $date_staff_total_slit_refill_percent; ?></span>&nbsp;&nbsp;<span class="staff-slit-refill-marker"><?php print $date_staff_total_slit_refill_marker; ?></span></div></td>
				<td><div class="dashboard-block-refill-percent"><span class="staff-scit-refill-percent"><?php print $date_staff_total_scit_refill_percent; ?></span>&nbsp;&nbsp;<span class="staff-scit-refill-marker"><?php print $date_staff_total_scit_refill_marker; ?></span></div></td>
			  </tr>
			  </table>
			</div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $daily_clinic_vs_staff_rx_refill_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="clinic-vs-staff-rx-refill-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/daily/refills/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="clinic-vs-staff-rx-refill-refresh-link use-ajax" href="/dashboard/clinic_vs_staff_rx_refill/daily/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block monthly-clinic-vs-staff-rx-refill" id="monthly-clinic-vs-staff-rx-refill">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">CLINIC VS STAFF RX REFILLS</div>
			<div class="dashboard-block-date">This Month | <?php print $clinic_vs_staff_rx_refill_month; ?></div>
			<div class="dashboard-block-data">
			  <table cellspacing="0" cellpadding="5" class="table_clinic_vs_staff monthly_table_clinic_vs_staff_rx_refill">
			  <tr>
				<th colspan="2" class="header-top">Clinic</th>
				<th colspan="2" class="header-top">Staff</th>
			  </tr>
			  <tr>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
				<th class="header-bottom">SLIT</th>
				<th class="header-bottom">SCIT</th>
			  </tr>
			  <tr>
				<td><span class="clinic-slit-refill"><?php print $monthly_clinic_total_slit_refill; ?></span></td>
				<td><span class="clinic-scit-refill"><?php print $monthly_clinic_total_scit_refill; ?></span></td>
				<td><span class="staff-slit-refill"><?php print $monthly_staff_total_slit_refill; ?></span></td>
				<td><span class="staff-scit-refill"><?php print $monthly_staff_total_scit_refill; ?></span></td>
			  </tr>
			  <tr>
				<td><div class="dashboard-block-refill-percent"><span class="clinic-slit-refill-percent"><?php print $monthly_clinic_total_slit_refill_percent; ?></span>&nbsp;&nbsp;<span class="clinic-slit-refill-marker"><?php print $monthly_clinic_total_slit_refill_marker; ?></span></div></td>
				<td><div class="dashboard-block-refill-percent"><span class="clinic-scit-refill-percent"><?php print $monthly_clinic_total_scit_refill_percent; ?></span>&nbsp;&nbsp;<span class="clinic-scit-refill-marker"><?php print $monthly_clinic_total_scit_refill_marker; ?></span></div></td>
				<td><div class="dashboard-block-refill-percent"><span class="staff-slit-refill-percent"><?php print $monthly_staff_total_slit_refill_percent; ?></span>&nbsp;&nbsp;<span class="staff-slit-refill-marker"><?php print $monthly_staff_total_slit_refill_marker; ?></span></div></td>
				<td><div class="dashboard-block-refill-percent"><span class="staff-scit-refill-percent"><?php print $monthly_staff_total_scit_refill_percent; ?></span>&nbsp;&nbsp;<span class="staff-scit-refill-marker"><?php print $monthly_staff_total_scit_refill_marker; ?></span></div></td>
			  </tr>
			  </table>
			</div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $monthly_clinic_vs_staff_rx_refill_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="clinic-vs-staff-rx-refill-link ctools-use-modal" href="/dashboard/report/clinic_vs_staff_created_refills/list/monthly/refills/<?php print date('n'); ?>/<?php print date('Y'); ?>/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="clinic-vs-staff-rx-refill-refresh-link use-ajax" href="/dashboard/clinic_vs_staff_rx_refill/monthly/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

	</div>

	<div class="content-row dashboard-row-13 clearfix">
	  <div class="dashboard-block monthly-chart-clinic-vs-staff-rx" id="monthly-chart-clinic-vs-staff-rx">
		<div class="dashboard-block-content">
		  <div id="monthly-clinic-vs-staff-rx-container"></div>
			<script type="text/javascript">
			  Highcharts.chart('monthly-clinic-vs-staff-rx-container', {
				chart: {
				  type: 'spline'
				},
				title: {
					text: 'MONTHLY CLINIC vs STAFF RX',
					style: {
						color: '#C0463D',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Year: <?php print $monthly_clinic_vs_staff_rx_year; ?>'
				},
				xAxis: {
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Count'
					}
				},
				tooltip: {
					headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
					pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
						'<td style="padding:0"><b>{point.y}</b></td></tr>',
					footerFormat: '</table>',
					shared: true,
					useHTML: true
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				series: [{
					name: 'Clinic SLIT Created',
					color: '#c0463d',
					data: [<?php print $chart_monthly_compare_clinic_slit_created; ?>]
				}, {
					name: 'Clinic SCIT Created',
					color: '#434348',
					data: [<?php print $chart_monthly_compare_clinic_scit_created; ?>]
				}, {
					name: 'Staff SLIT Created',
					color: '#f8a13f',
					data: [<?php print $chart_monthly_compare_staff_slit_created; ?>]
				}, {
					name: 'Staff SCIT Created',
					color: '#825f8e',
					data: [<?php print $chart_monthly_compare_staff_scit_created; ?>]
				}, {
					name: 'Clinic SLIT Refill',
					color: '#f45b5b',
					data: [<?php print $chart_monthly_compare_clinic_slit_refill; ?>]
				}, {
					name: 'Clinic SCIT Refill',
					color: '#e4d354',
					data: [<?php print $chart_monthly_compare_clinic_scit_refill; ?>]
				}, {
					name: 'Staff SLIT Refill',
					color: '#2b908f',
					data: [<?php print $chart_monthly_compare_staff_slit_refill; ?>]
				}, {
					name: 'Staff SCIT Refill',
					color: '#bbd719',
					data: [<?php print $chart_monthly_compare_staff_scit_refill; ?>]
				}]
			  });
			</script>
		</div>
	  </div>

	  <div class="dashboard-block monthly-compare-clinic-vs-staff-rx" id="monthly-compare-clinic-vs-staff-rx">
		<div class="dashboard-block-content">
		  <div class="dashboard-block-table" id="monthly-table-clinic-vs-staff-rx">
			<?php print $comparison_table_clinic_vs_staff_rx; ?>
		  </div>
		  <div class="dashboard-block-separator"></div>
		  <div class="dashboard-block-links">
			<span class="last-update-block"><?php print $clinic_vs_staff_rx_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
			<span class="refresh-block"><a class="rx-clinic-vs-staff-link use-ajax" href="/dashboard/rx_clinic_vs_staff/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
		  </div>
		</div>
	  </div>
	</div>

	<div class="content-row dashboard-row-5 clearfix">
		<div class="content-row-left">
			<div class="content-row dashboard-row-left-1 clearfix">
				<div class="dashboard-block daily-rx-successful-payment" id="daily-rx-successful-payment">
		  		  <div class="dashboard-block-content">
					<div class="dashboard-block-title">TOTAL SUCCESSFUL PAYMENT</div>
					<div class="dashboard-block-date">Today | <?php print $total_successful_payment_date; ?></div>
					<div class="dashboard-block-amount"><?php print $date_total_successful_payment; ?></div>
					<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_successful_payment_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_successful_payment_marker; ?></span></div>
					<div class="dashboard-block-separator"></div>
					<div class="dashboard-block-links">
						<span class="last-update-block"><?php print $date_successful_payment_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="display-block"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/daily/successful/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
						<span class="refresh-block"><a class="rx-successful-payment-refresh-link use-ajax" href="/dashboard/total_payments/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
					</div>
				  </div>
				</div>

				<div class="dashboard-block daily-rx-refund-payment" id="daily-rx-refund-payment">
		  		  <div class="dashboard-block-content">
					<div class="dashboard-block-title">TOTAL REFUND PAYMENT</div>
					<div class="dashboard-block-date">Today | <?php print $total_refund_payment_date; ?></div>
					<div class="dashboard-block-amount"><?php print $date_total_refund_payment; ?></div>
					<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_refund_payment_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_refund_payment_marker; ?></span></div>
					<div class="dashboard-block-separator"></div>
					<div class="dashboard-block-links">
						<span class="last-update-block"><?php print $date_refund_payment_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="display-block"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/daily/refund/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
						<span class="refresh-block"><a class="rx-refund-payment-refresh-link use-ajax" href="/dashboard/total_payments/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
					</div>
				  </div>
				</div>

				<div class="dashboard-block daily-rx-invoice-payment" id="daily-rx-invoice-payment">
		  		  <div class="dashboard-block-content">
					<div class="dashboard-block-title">TOTAL INVOICE PAYMENT</div>
					<div class="dashboard-block-date">Today | <?php print $total_invoice_payment_date; ?></div>
					<div class="dashboard-block-amount"><?php print $date_total_invoice_payment; ?></div>
					<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_invoice_payment_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_invoice_payment_marker; ?></span></div>
					<div class="dashboard-block-separator"></div>
					<div class="dashboard-block-links">
						<span class="last-update-block"><?php print $date_invoice_payment_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="display-block"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/daily/invoice/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
						<span class="refresh-block"><a class="rx-invoice-payment-refresh-link use-ajax" href="/dashboard/total_payments/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
					</div>
				  </div>
				</div>
			</div>

			<div class="content-row dashboard-row-left-2 clearfix">
				<div class="dashboard-block daily-rx-denied-payment" id="daily-rx-denied-payment">
		  		  <div class="dashboard-block-content">
					<div class="dashboard-block-title">TOTAL DENIED PAYMENT</div>
					<div class="dashboard-block-date">Today | <?php print $total_denied_payment_date; ?></div>
					<div class="dashboard-block-amount"><?php print $date_total_denied_payment; ?></div>
					<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_denied_payment_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_denied_payment_marker; ?></span></div>
					<div class="dashboard-block-separator"></div>
					<div class="dashboard-block-links">
						<span class="last-update-block"><?php print $date_denied_payment_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="display-block"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/daily/denied/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
						<span class="refresh-block"><a class="rx-denied-payment-refresh-link use-ajax" href="/dashboard/total_payments/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
					</div>
				  </div>
				</div>

				<div class="dashboard-block daily-rx-void-payment" id="daily-rx-void-payment">
		  		  <div class="dashboard-block-content">
					<div class="dashboard-block-title">TOTAL VOID PAYMENT</div>
					<div class="dashboard-block-date">Today | <?php print $total_void_payment_date; ?></div>
					<div class="dashboard-block-amount"><?php print $date_total_void_payment; ?></div>
					<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_void_payment_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_void_payment_marker; ?></span></div>
					<div class="dashboard-block-separator"></div>
					<div class="dashboard-block-links">
						<span class="last-update-block"><?php print $date_void_payment_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="display-block"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/daily/void/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
						<span class="refresh-block"><a class="rx-void-payment-refresh-link use-ajax" href="/dashboard/total_payments/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
					</div>
				  </div>
				</div>

				<div class="dashboard-block daily-rx-error-payment" id="daily-rx-error-payment">
		  		  <div class="dashboard-block-content">
					<div class="dashboard-block-title">TOTAL ERROR PAYMENT</div>
					<div class="dashboard-block-date">Today | <?php print $total_error_payment_date; ?></div>
					<div class="dashboard-block-amount"><?php print $date_total_error_payment; ?></div>
					<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_error_payment_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_error_payment_marker; ?></span></div>
					<div class="dashboard-block-separator"></div>
					<div class="dashboard-block-links">
						<span class="last-update-block"><?php print $date_error_payment_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
						<span class="display-block"><a class="payment-display-link ctools-use-modal" href="/dashboard/report/payments/daily/error/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
						<span class="refresh-block"><a class="rx-error-payment-refresh-link use-ajax" href="/dashboard/total_payments/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
					</div>
				  </div>
				</div>
			</div>
		</div>

		<div class="content-row-right">
			<div class="dashboard-block daily-sales-amount" id="daily-sales-amount">
			  <div class="dashboard-block-content">
				<div class="dashboard-block-title">DAILY SALES AMOUNT</div>
				<div class="dashboard-block-date">Today | <?php print $daily_sales_amount_date; ?></div>
				<div class="dashboard-block-amount"><?php print $daily_sales_amount; ?></div>
				<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $daily_sales_amount_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $daily_sales_amount_marker; ?></span></div>
				<div class="dashboard-block-separator"></div>
				<div class="dashboard-block-links">
					<span class="last-update-block"><?php print $daily_sales_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
					<span class="report-block"><a class="sales-amount-report-link ctools-use-modal" href="/dashboard/report/sales/daily/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
					<span class="refresh-block"><a class="rx-error-payment-refresh-link use-ajax" href="/dashboard/daily_sales_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
				</div>
			  </div>
			</div>

			<div class="dashboard-block monthly-sales-amount" id="monthly-sales-amount">
			  <div class="dashboard-block-content">
				<div class="dashboard-block-title">MONTHLY SALES AMOUNT</div>
				<div class="dashboard-block-date">This Month | <?php print $monthly_sales_amount_month; ?>, <?php print $monthly_sales_amount_year; ?></div>
				<div class="dashboard-block-amount"><?php print $monthly_sales_amount; ?></div>
				<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $monthly_sales_amount_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $monthly_sales_amount_marker; ?></span></div>
				<div class="dashboard-block-separator"></div>
				<div class="dashboard-block-links">
					<span class="last-update-block"><?php print $monthly_sales_amount_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
					<span class="report-block"><a class="sales-amount-report-link ctools-use-modal" href="/dashboard/report/sales/monthly/<?php print date('n'); ?>/<?php print date('Y'); ?>/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
					<span class="refresh-block"><a class="monthly-amount-refresh-link use-ajax" href="/dashboard/monthly_sales_amount/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
				</div>
			  </div>
			</div>
		</div>

	</div>

	<div class="content-row dashboard-row-11 clearfix">
		<div class="dashboard-block daily-chart-sales-amount" id="daily-chart-sales-amount">
		  <div class="dashboard-block-content">
			<div id="daily-sales-amount-container"></div>
			<script type="text/javascript">
			Highcharts.chart('daily-sales-amount-container', {
				chart: {
					type: 'areaspline'
				},
				title: {
					text: 'DAILY SALES AMOUNT',
					style: {
						color: '#e54e52',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Range: <?php print $total_daily_sales_amount_range; ?>'
				},
				xAxis: {
					title: {
						text: 'Date'
					},
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Amount ($)'
					}
				},
				tooltip: {
					shared: true,
					valueSuffix: ' USD'
				},
				plotOptions: {
					area: {
						pointStart: 0,
						areaspline: {
							fillOpacity: 0.5
						},
						marker: {
							enabled: false,
							symbol: 'circle',
							radius: 2,
							states: {
								hover: {
									enabled: true
								}
							}
						}
					}
				},
				series: [{
					name: 'Amount',
					data: [<?php print $chart_daily_sales_amounts; ?>],
        			color: '#e54e52'
				}]
			});
			</script>
		  </div>
		</div>

		<div class="dashboard-block monthly-chart-sales-amount" id="monthly-chart-sales-amount">
		  <div class="dashboard-block-content">
			<div id="monthly-sales-amount-container"></div>
			<script type="text/javascript">
			Highcharts.chart('monthly-sales-amount-container', {
				chart: {
					type: 'column'
				},
				title: {
					text: 'MONTHLY SALES AMOUNT',
					style: {
						color: '#8ac541',
						fontWeight: 'bold'
					}
				},
				subtitle: {
					text: 'Year: <?php print $total_monthly_sales_amount_year; ?>'
				},
				xAxis: {
        			type: 'category',
					title: {
						text: 'Month'
					},
					crosshair: true
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Amount ($)'
					}
				},
				legend: {
					enabled: false
				},
				plotOptions: {
					series: {
						borderWidth: 0,
						dataLabels: {
							enabled: true,
							format: '{point.y:.0f}',
							rotation: 0,
							crop: false,
							overflow: 'none',
							inside: false
						}
					}
				},
				tooltip: {
					headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
					pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y:.0f}</b><br/>'
				},
				series: [{
					name: 'Amount',
        			colorByPoint: true,
					data: [<?php print $chart_monthly_sales_amounts; ?>],
        			color: '#8ac541'
				}]
			});
			</script>
		  </div>
		</div>

		<div class="dashboard-block monthly-payments" id="monthly-payments">
		  <div class="dashboard-block-content">
			<div class="payment-comparison" id="monthly-table-payments">
			  <?php print $rx_payments_comparison; ?>
			</div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
			  <span class="last-update-block"><?php print $rx_payments_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
			  <span class="refresh-block"><a class="rx-payments-link use-ajax" href="/dashboard/rx_payments_summary/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

	</div>

	<div class="content-row dashboard-row-6 clearfix">
		<div class="dashboard-block daily-order-submitted" id="daily-order-submitted">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">ORDER SUBMITTED</div>
			<div class="dashboard-block-date">Today | <?php print $total_order_submitted_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_order_submitted; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_order_submitted_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_order_submitted_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_order_submitted_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="order-submitted-display-link ctools-use-modal" href="/dashboard/total_order_submitted/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="order-submitted-refresh-link use-ajax" href="/dashboard/total_order_submitted/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block rx-pending" id="rx-pending">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">RX PENDING</div>
			<div class="dashboard-block-date">Today | <?php print $total_rx_pending_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_rx_pending; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_rx_pending_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_rx_pending_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_rx_pending_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="rx-pending-display-link ctools-use-modal" href="/dashboard/total_rx_pending/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-pending-refresh-link use-ajax" href="/dashboard/total_rx_pending/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block rx-scheduled" id="rx-scheduled">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">RX SCHEDULED</div>
			<div class="dashboard-block-date">Today | <?php print $total_rx_scheduled_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_rx_scheduled; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_rx_scheduled_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_rx_scheduled_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_rx_scheduled_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="rx-scheduled-display-link ctools-use-modal" href="/dashboard/total_rx_scheduled/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-scheduled-refresh-link use-ajax" href="/dashboard/total_rx_scheduled/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block rx-refills" id="rx-refills">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">UPCOMING RX REFILLS</div>
			<div class="dashboard-block-date">Today | <?php print $total_rx_refills_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_rx_refills; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_rx_refills_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_rx_refills_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_rx_refills_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="rx-refills-display-link ctools-use-modal" href="/dashboard/total_rx_refills/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="rx-refills-refresh-link use-ajax" href="/dashboard/total_upcoming_rx_refills/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>
	</div>

	<div class="content-row dashboard-row-7 clearfix">
		<div class="dashboard-block expiring-rx" id="expiring-rx">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">EXPIRING RX</div>
			<div class="dashboard-block-date">Today | <?php print $total_expiring_rx_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_expiring_rx; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_expiring_rx_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_expiring_rx_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_expiring_rx_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="expiring-rx-display-link ctools-use-modal" href="/dashboard/total_expiring_rx/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="expiring-rx-refresh-link use-ajax" href="/dashboard/total_expiring_rx/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block expiring-arb" id="expiring-arb">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">EXPIRING ARB</div>
			<div class="dashboard-block-date">Today | <?php print $total_expiring_arb_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_expiring_arb; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_expiring_arb_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_expiring_arb_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_expiring_arb_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="expiring-arb-display-link ctools-use-modal" href="/dashboard/total_expiring_arb/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="expiring-arb-refresh-link use-ajax" href="/dashboard/total_expiring_arb/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block expiring-cc" id="expiring-cc">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">EXPIRING CREDIT CARD</div>
			<div class="dashboard-block-date">Today | <?php print $total_expiring_cc_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_expiring_cc; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_expiring_cc_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_expiring_cc_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_expiring_cc_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="expiring-cc-display-link ctools-use-modal" href="/dashboard/total_expiring_cc/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="expiring-cc-refresh-link use-ajax" href="/dashboard/total_expiring_cc/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block expiring-profile-cc" id="expiring-profile-cc">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">EXPIRING PROFILE CREDIT CARD</div>
			<div class="dashboard-block-date">Today | <?php print $total_expiring_profile_cc_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_expiring_profile_cc; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_expiring_profile_cc_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_expiring_profile_cc_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_expiring_profile_cc_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a class="expiring-profile-cc-display-link ctools-use-modal" href="/dashboard/total_expiring_profile_cc/view/nojs"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="expiring-profile-cc-refresh-link use-ajax" href="/dashboard/total_expiring_profile_cc/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>
	</div>

	<div class="content-row dashboard-row-8 clearfix">
		<div class="dashboard-block clinics-summary" id="clinics-summary">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">TOTAL CLINICS</div>
			<div class="dashboard-block-date">Today | <?php print $total_clinics_date; ?></div>
			<div class="dashboard-block-amount"><?php print $date_total_clinics; ?></div>
			<div class="dashboard-block-amount-percent"><span class="amount-percent"><?php print $date_total_clinics_percent; ?></span>&nbsp;&nbsp;<span class="amount-percent-marker"><?php print $date_total_clinics_percent_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $date_total_clinics_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a target="_blank" class="total-clinics-display-link" href="<?php $base_url; ?>"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="total-clinics-refresh-link use-ajax" href="/dashboard/total_clinics/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>

		<div class="dashboard-block silent-post-summary" id="silent-post-summary">
		  <div class="dashboard-block-content">
			<div class="dashboard-block-title">SILENT POST SUMMARY</div>
			<div class="dashboard-block-date">This Year | <?php print $silent_post_summary_date; ?></div>
			<div class="dashboard-block-label">Resolved | Pending</div>
			<div class="dashboard-block-amount"><span class="silent-post-resolved"><?php print $silent_post_resolved; ?></span> | <span class="silent-post-pending"><?php print $silent_post_pending; ?></span></div>
			<div class="dashboard-block-amount-percent"><span class="silent-post-resolved-percent"><?php print $silent_post_resolved_percent; ?></span>&nbsp;&nbsp;<span class="silent-post-resolved-percent-marker"><?php print $silent_post_resolved_marker; ?></span> | <span class="silent-post-pending-percent"><?php print $silent_post_pending_percent; ?></span>&nbsp;&nbsp;<span class="silent-post-pending-percent-marker"><?php print $silent_post_pending_marker; ?></span></div>
			<div class="dashboard-block-separator"></div>
			<div class="dashboard-block-links">
				<span class="last-update-block"><?php print $silent_post_summary_last_update; ?></span>&nbsp;&nbsp;|&nbsp;&nbsp;
				<span class="display-block"><a target="_blank" class="silent-post-summary-link" href="/silentpost/report"><i class="fa fa-th" aria-hidden="true">&nbsp;</i></a></span>
				<span class="refresh-block"><a class="silent-post-summary-link use-ajax" href="/dashboard/silent_post_summary/refresh/nojs"><i class="fa fa-refresh" aria-hidden="true">&nbsp;</i></a></span>
			</div>
		  </div>
		</div>
	</div>

	<div class="content-row dashboard-row-9 clearfix">
		<div class="dashboard-block-actions" id="dashboard-block-actions">
		  <div class="dashboard-block-content">
		    <?php if (in_array('administrator', $user->roles)): ?>
		  	  <a title="Cron Schedule" target="_blank" class="dashboard-cron" href="/dashboard/cron"><i class="fa fa-exchange" aria-hidden="true">&nbsp;</i></a>
		  	  <a title="Re-Genarate" class="dashboard-update-all ctools-use-modal" href="/dashboard/run/generate/nojs"><i class="fa fa-cogs" aria-hidden="true">&nbsp;</i></a>
		  	  <a title="Settings" class="dashboard-settings ctools-use-modal" href="/dashboard/settings/nojs"><i class="fa fa-wrench" aria-hidden="true">&nbsp;</i></a>
		  	  <a title="Clean Up" class="dashboard-clean-all ctools-use-modal" href="/dashboard/run/clean/nojs"><i class="fa fa-trash-o" aria-hidden="true">&nbsp;</i></a>
		  	  &nbsp;&nbsp;|&nbsp;&nbsp;
		    <?php endif; ?>
		    <span class="refresh-time-wrapper"><span class="refresh-label">Refresh Time: </span><span id="dashboard-refresh-time"><?php print $dashboard_refresh_time; ?></span><span class="refresh-label"> minutes.</span>&nbsp;&nbsp;|&nbsp;&nbsp;<span class="refresh-label"> Last refreshed: </span><span id="dashboard-last-refresh-time"><?php print $dashboard_last_refresh_time; ?></span></span>
		  </div>
		</div>
	</div>

	<div id="dashboard-refresh-wrapper"></div>

</div>

<!--
Blocks: Total Rx Amount | Total Order Amount | Weekly (Rx and Order) | Monthly (Rx and Order)
Charts: Monthly chart of Rx amount, Monthly chart of order amount, Monthly chart of Rx and Order amount (bar chart),

Blocks: SLIT Created, SCIT Created, SLIT Refills, SCIT Refills
Charts: Monthly SLIT/SCIT Created (line chart), Monthly SLIT/SCIT Refills (line chart), Monthly chart of SLIT/SCIT created vs refills (comparison table),

Blocks (left): Successful Payment, Refund payment, Invoice payment
Blocks (left): Denied Payment, Void payment, Error payment
Charts (on right side): 10 days comparison of successful vs refund vs invoice (Comparison table)

Blocks: Order Submitted, Rx Pending, Rx Scheduled, Rx Refills   -> on click will show the ids / nids list
Blocks: Expiring Rx, Expiring ARB, Expiring CC, Profile Expiring CC   -> on click will show the ids / nids list
Blocks: Total Clinics, Silent Post Summary
-->




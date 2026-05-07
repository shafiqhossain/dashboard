(function (Drupal, once, drupalSettings) {
  Drupal.behaviors.monthly_rx_order_amount_container = {
    attach: function (context, settings) {
      once('monthly_rx_order_amount_container', '#monthly-rx-order-amount-container', context).forEach(function (element) {
        const data = drupalSettings.mrsDashboard.monthlyRxOrderChart;

        Highcharts.chart(element, {
          chart: { type: 'column' },
          title: {
            text: 'MONTHLY RX vs STORE vs PO',
            style: { color: '#01C1EA', fontWeight: 'bold' }
          },
          subtitle: { text: 'Year: ' + data.year },
          xAxis: {
            categories: [
              'Jan','Feb','Mar','Apr','May','Jun',
              'Jul','Aug','Sep','Oct','Nov','Dec'
            ],
            crosshair: true
          },
          yAxis: { min: 0, title: { text: 'Amount ($)' } },
          tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat:
                '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>{point.y:.2f} USD</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
          },
          plotOptions: {
            column: { pointPadding: 0.2, borderWidth: 0 },
            series: {
              dataLabels: {
                enabled: true,
                format: '{point.y:.0f}',
                crop: false,
                overflow: 'none',
                inside: false
              }
            }
          },
          series: [
            { name: data.rx_label_prev,   color: '#286198', data: data.rx_amounts_prev },
            { name: data.rx_label_current,color: '#2b908f', data: data.rx_amounts_current },
            { name: data.order_label_prev,color: '#e4d354', data: data.order_amounts_prev },
            { name: data.order_label_current,color: '#f7a35c', data: data.order_amounts_current },
            { name: data.po_label_prev,   color: '#f15c80', data: data.po_amounts_prev },
            { name: data.po_label_current,color: '#434348', data: data.po_amounts_current }
          ]
        });
      });
    }
  };
})(Drupal, once, drupalSettings);

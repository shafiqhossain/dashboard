(function (Drupal, once, drupalSettings) {
  Drupal.behaviors.monthly_slit_vs_scit_refills_container = {
    attach: function (context) {
      once('monthly_slit_vs_scit_refills_container', '#monthly-slit-vs-scit-refills-container', context)
          .forEach(function (element) {
            const data = drupalSettings.mrsDashboard.monthlySlitVsScitRefills;

            Highcharts.chart(element, {
              chart: { type: 'column' },
              title: {
                text: 'MONTHLY SLIT vs SCIT REFILLS',
                style: { color: '#2E7AA2', fontWeight: 'bold' }
              },
              subtitle: { text: 'Year: ' + data.year },
              xAxis: {
                categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                crosshair: true,
                title: { text: 'Month' }
              },
              yAxis: { min: 0, title: { text: 'Count' } },
              legend: { enabled: true },
              tooltip: {
                headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                pointFormat:
                    '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"><b>{point.y}</b></td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
              },
              plotOptions: {
                column: { pointPadding: 0.2, borderWidth: 0 },
                series: {
                  borderWidth: 0,
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
                { name: data.slitPrevLabel,  color: '#f15c80', data: data.slitPrev },
                { name: data.slitCurrentLabel, color: '#e4d354', data: data.slitCurrent },
                { name: data.scitPrevLabel,  color: '#2b908f', data: data.scitPrev },
                { name: data.scitCurrentLabel, color: '#f7a35c', data: data.scitCurrent }
              ]
            });
          });
    }
  };
})(Drupal, once, drupalSettings);

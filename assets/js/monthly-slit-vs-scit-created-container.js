(function (Drupal, once, drupalSettings) {
  Drupal.behaviors.monthly_slit_vs_scit_created_container = {
    attach: function (context) {
      once('monthly_slit_vs_scit_created_container', '#monthly-slit-vs-scit-created-container', context)
          .forEach(function (element) {
            const data = drupalSettings.mrsDashboard.monthlySlitVsScitCreated;

            Highcharts.chart(element, {
              chart: { type: 'column' },
              title: {
                text: 'MONTHLY SLIT vs SCIT CREATED',
                style: { color: '#2E7AA2', fontWeight: 'bold' }
              },
              subtitle: { text: 'Year: ' + data.year },
              legend: { enabled: true },
              xAxis: {
                type: 'category',
                title: { text: 'Month' },
                crosshair: true
              },
              yAxis: { min: 0, title: { text: 'Count' } },
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
                { name: 'SLIT', color: '#f15c80', data: data.slit },
                { name: 'SCIT', color: '#e4d354', data: data.scit }
              ]
            });
          });
    }
  };
})(Drupal, once, drupalSettings);

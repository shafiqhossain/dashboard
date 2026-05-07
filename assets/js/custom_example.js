(function ($) {

	/**
	 * Move a block in the blocks table from one region to another via select list.
	 *
	 * This behavior is dependent on the tableDrag behavior, since it uses the
	 * objects initialized in that behavior to update the row.
	 */
	Drupal.behaviors.custom_rx = {
	  attach: function (context, settings) {
      }
    };

	$(document).ready(function() {
		//check if we are in dashboard page
		if($('#dashboard-refresh-time').length) {
			//get the interval from dashboard page
			var refresh_interval = parseInt($('#dashboard-refresh-time').html());

			//interval in miliseconds
			var interval = 1000 * 60 * refresh_interval;

			//function to be called
			var ajax_call = function() {
			  $.ajax({
            	  type: 'GET',
				  url: "/custom_example/sections/refresh",
				  cache: false,
				  dataType: 'json'
			  })
			  .done(function(results) {
			    if (results && results.length > 0) {
				  for (var i = 0; i < results.length; i++) {
					var row = results[i];
				    if (row['command'] === 'invoke' && row['method'] === 'html') {
					  const args = row['arguments'] || row['args']; // support both
					  if (row['selector'] && args && args.length > 0) {
					    $(row['selector']).html(args[0]);
					  }
					  else {
					    console.warn("Invalid row format:", row);
					  }
				    }
				  }
			    }

				var now     = new Date();
				var year    = now.getFullYear();
				var month   = now.getMonth()+1;
				var day     = now.getDate();
				var hour    = now.getHours();
				var minute  = now.getMinutes();
				var second  = now.getSeconds();
				var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October","November", "December"];

  				var last_update =  monthNames[month] + ' ' + day + ', ' + year + ' ' + hour + ':' + minute + ':' + second;
				$('#dashboard-last-refresh-time').html(last_update);

				 //console.log(results);
				 console.log('Dashboard sections refreshed after '+refresh_interval+' minutes.');
			  })
			  .fail(function(jqXHR, textStatus, errorThrown) {
				  console.error("Dashboard refresh failed: " + textStatus, errorThrown);
			  });
			};

			//ajax called here at interval
			if (refresh_interval > 0) {
			  setInterval(ajax_call, interval);
			}
		}

		//datatable
		if($('#mrs-dashboard-rx-links-list').length) {
    	  $('#mrs-dashboard-rx-links-list').DataTable({
			"searching": false,
			"paging":   false,
			"lengthChange": false,
			"info": false
		  });
		}
	});
})(jQuery);

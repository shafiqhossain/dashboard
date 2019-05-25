(function ($) {

	/**
	 * Move a block in the blocks table from one region to another via select list.
	 *
	 * This behavior is dependent on the tableDrag behavior, since it uses the
	 * objects initialized in that behavior to update the row.
	 */
	Drupal.behaviors.dashboard = {
	  attach: function (context, settings) {
    	// Make modal window height scaled automatically.
    	$('.ctools-modal-content, #modal-content', context).height('auto');

		$('.ctools-use-modal:not(.ctools-use-modal-processed)').each(function(i, obj) {
		  var url = $(obj).attr('href');
		  // This is to pop up the modal as soon as the user clicks the element.
		  $(obj).click(Drupal.CTools.Modal.clickAjaxLink);

		  var element_settings = {};
		  element_settings.url = url;
		  element_settings.event = 'click';
		  element_settings.progress = { type: 'throbber' };
		  var base = url;
		  Drupal.ajax[base] = new Drupal.ajax(base, obj, element_settings);
		  $(obj).addClass('ctools-use-modal-processed');
		});
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
				  url: "/dashboard/sections/refresh",
				  cache: false,
			  })
			  .success(function(data) {
      			var results = JSON.parse(data);
				if(results.length > 0) {
				  for(var i = 0, len = results.length; i < len; i++) {
					row = results[i];
					if(row['command'] == 'invoke' && row['method'] == 'html') {
						$(row['selector']).html(row['arguments'][0]);
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
			};

			//ajax called here at interval
			if(refresh_interval > 0) {
			  setInterval(ajax_call, interval);
			}
		}

		//datatable
		if($('#dashboard-rx-links-list').length) {
    	  $('#dashboard-rx-links-list').DataTable({
			"searching": false,
			"paging":   false,
			"lengthChange": false,
			"info": false
		  });
		}
	});
})(jQuery);

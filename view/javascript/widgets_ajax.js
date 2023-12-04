jQuery(function($){

	$('.nitropack-widget-ajax').each(function(){

		$(this).load( nitropack_widget_ajax.ajax_url + '?action=nitropack_widget_output_ajax&widget_id=' + $(this).attr('data-widget-id') + '&sidebar_id=' + $(this).attr('data-sidebar-id'));
	});
});
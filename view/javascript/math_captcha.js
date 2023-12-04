jQuery(function($){
	$('.nitropack_math_captcha').each(function() {
		var el = $(this);
		var endpoint = nitropack_math_captcha_ajax.root + '/nitropack/math_captcha/';

		$.ajax({
			url: endpoint,
			data: {
				"form-type": el.attr('data-form-type'),
			},
			cache: false,
			method: 'GET',
			dataType: 'json',
			beforeSend: function(xhr){
				xhr.setRequestHeader( 'X-WP-Nonce', nitropack_math_captcha_ajax.nonce );
			}
		}).done(function(response){
			if (response.code == 'ok') {
				el.html(response.html);
			}
		});
	});
});
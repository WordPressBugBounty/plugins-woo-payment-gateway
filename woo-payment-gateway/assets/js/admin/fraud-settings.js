jQuery(function($){
	var kount = {
			init: function(){
				$('.kount-api-key').on('click', this.api_new_key);
			},
			api_new_key: function(e){
				e.preventDefault();
				kount.block();
				$.when($.ajax({
					url: woocommerce_braintree_fraud_settings_params.url.api_key,
					method: 'POST',
					data: {_wpnonce: woocommerce_braintree_fraud_settings_params._wpnonce}
				})).done(function(api_key){
					$('.wc-braintree-kount-url').text(api_key);
					$('[name="save"]').click();
				}).fail(function(jqXHR, textStatus, errorThrown){
					kount.unblock();
					window.alert(errorThrown);
				})
			},
			block: function() {
				$.blockUI({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
			},
			unblock: function() {
				$.unblockUI();
			}
	};
	kount.init();
})

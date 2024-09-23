jQuery(function($){
	var subscription = {
			params: wc_braintree_view_subscription_params,
			init: function(){
				$('.woocommerce').on('click', '.action-cancel', this.cancel_confirmation);
			},
			cancel_confirmation: function(e){
				e.preventDefault();
				var href = $(e.target).attr('href');
				$('#wc-braintree-subscription-dialog').dialog({
					modal: true,
					title: subscription.params.messages.title,
					width: 400,
					height: 'auto',
					buttons: [
						{
							text: subscription.params.messages.cancel,
							click: function(){
								$(this).dialog('close');
							}
						},
						{
							text: subscription.params.messages.confirm,
							click: function(){
								window.location.href = href;
							}
						}
					]
				});
			}
	};
	
	subscription.init();
})
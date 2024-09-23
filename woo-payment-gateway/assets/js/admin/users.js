jQuery(function($){
	var users = {
			init: function(){
				$('.wc-braintree-delete-token').on('click', this.api_delete_token);
			},
			api_delete_token: function(){
				if(!confirm(wc_braintree_users_params.messages.confirm_delete)){
					return;
				}
				users.block();
				$.when($.ajax({
					method: 'DELETE',
					url: wc_braintree_users_params.urls.delete_token.replace('%id%', $(this).data('token')),
				})).done(function(response){
					if(response.success){
						window.location.href = window.location.href;
					}else{
						users.unblock();
						window.alert(response.message);
					}
				}).fail(function(jqXHR, textStatus, errorThrown){
					users.unblock();
					window.alert(errorThrown);
				});
			},
			block: function(){
				$.blockUI({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
			},
			unblock: function(){
				$.unblockUI();
			}
	};
	users.init();
})
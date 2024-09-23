(function($){
	var data = {
			params: wc_braintree_data_migration_params,
			init: function(){
				$('.api-migration').on('click', this.do_api_migration);
			},
			do_api_migration: function(e){
				e.preventDefault();
				$form = $('#data_migration');
				$form.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				})
				$.ajax({
					method: 'POST',
					dataType: 'json',
					url: data.params.route,
					data: $form.serialize(),
				}).done(function(response){
					window.alert(response.message);
					$form.unblock();
				}).fail(function(jqXHR, textStatus, errorThrown){
					window.alert(errorThrown);
					$form.unblock();
				})
			}
	}
	data.init();
}(jQuery))
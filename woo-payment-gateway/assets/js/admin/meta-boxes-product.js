jQuery(function($){
	var data = {
			params: wc_braintree_meta_box_product_params,
			init: function(){
				$(document.body).on('click', '.wc-braintree-fetch-plans', this.do_api_fetch);
				$(document.body).on('change', '[name^="wc_braintree_environment"], #product-type', this.show_and_hide_options).
					on('change', this.filter_plans);
				$('[name*="subscription_period_interval"]').on('change', this.filter_plans);
				$(document.body).on('select2:selecting', '[name*="braintree_sandbox_plans"], [name*="braintree_production_plans"]', this.validate_selection);
				$('#woocommerce-product-data').on('woocommerce_variations_loaded', this.show_and_hide_options).
					on('woocommerce_variations_loaded', this.filter_plans);
				$('#variable_product_options').on('woocommerce_variations_added', this.show_and_hide_options);
				$(document.body).on('keyup change', '[name*="subscription_trial_length"]', this.update_trial_period);
				$(document.body).on('woocommerce_added_attribute', this.woocommerce_added_attribute);
				this.show_and_hide_options();
				this.filter_plans();
			},
			show_and_hide_options: function(){
				var product_type = $('#product-type').val();
				$('[name^="wc_braintree_environment"]').each(function(){
					var $parent = $(this).closest($(this).data('container'));
					var hide_classes = '.hide_if_' + data.get_environment($parent);
					var show_classes = '.show_if_' + data.get_environment($parent);
					if(product_type.indexOf('braintree') > -1){
						$parent.find(hide_classes).hide();
						$parent.find(show_classes).show();
						if(product_type.indexOf('variable') > -1){
							$('.show_if_variable').show();
						}
					}else{
						$parent.find('.hide_if_' + product_type).hide();
					}
				})
			},
			woocommerce_added_attribute: function(){
				if($('#product-type').val().indexOf('braintree-variable') > -1){
					$('.enable_variation').show();
				}
			},
			filter_plans: function(){
				if($('#product-type').val().indexOf('braintree') !== -1){
					$('[name*="subscription_period_interval"]').each(function(){
						var $parent = $(this).closest($(this).data('container'));
						var env = data.get_environment($parent);
						var plans = data.params.plans[data.get_environment($parent)];
						var $interval_select = $parent.find('[id*="' + data.get_environment($parent) + '_subscription_period_interval"]');
						var $plans_select = $parent.find('[id*="braintree_' + data.get_environment($parent) + '_plans"]');
						var frequency = $interval_select.val();
						$plans_select.find('option').each(function(index, el){
							var plan_id = $(el).val();
							$(el).prop('disabled', plans[plan_id].billingFrequency != frequency);
						});
						$plans_select.select2();
					})
				}
			},
			get_environment: function($parent){
				return $parent.find('[id^="wc_braintree_environment"]').val();
			},
			do_api_fetch: function(e){
				e.preventDefault();
				data.block();
				$.when($.ajax({
					method: 'POST',
					url: data.params.url.plans,
					data: {_wpnonce: data.params._wpnonce}
				})).done(function(response){
					if(response.code){
						data.unblock();
						window.alert(response.message);
					}else{
						$('[name="save"]').click();
					}
				}).fail(function(jqXHR, textStatus, errorThrown){
					data.unblock();
					window.alert(errorThrown);
				})
			},
			validate_selection: function(e){
				var option = e.params.args.data;
				var $parent = $(this).closest($(this).data('container'));
				var plans = data.params.plans[data.get_environment($parent)],
				plan = plans[option.id];
				var selected_data = $parent.find('[name*="braintree_' + data.get_environment($parent) + '_plans"]').select2('data');
				$.each(selected_data, function(i, object){
					if(plans[object.id].currencyIsoCode === plan.currencyIsoCode){
						e.preventDefault();
						window.alert(data.params.messages.duplicate_currency.replace('%currency%', plan.currencyIsoCode));
						return false;
					}
				});
			},
			update_trial_period: function(){
				$('[name*="subscription_trial_length"]').each(function(){
					var trial_period = parseInt($(this).val()), type = '', $that = $(this);
					type = trial_period == 1 ? 'singular' : 'plural';
					if($(this).attr('name').indexOf('variable') !== -1){
						var $trial_period = $(this).closest('.woocommerce_variation').find('[name*="subscription_trial_period"]');
					}else{
						var $trial_period = $(this).closest('.options_group').find('[name*="subscription_trial_period"]');
					}
					$trial_period.find('option').each(function(){
						$(this).text(data.params.trial_period[$(this).val()][type]);
					})
				})
			},
			block: function() {
				$( '#woocommerce-product-data' ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
			},
			unblock: function() {
				$( '#woocommerce-product-data' ).unblock();
			}
	};
	data.init();
})
(function($){
	
	$.fn.WCBraintreePayOrderBackboneModal = function(options){
		return this.each(function(){
			new $.WCBraintreePayOrderBackboneModal($(this), options);
		})
	}
	
	$.WCBraintreePayOrderBackboneModal = function(element, options){
		var settings = $.extend( {}, $.WCBackboneModal.defaultOptions, options );

		if ( settings.template ) {
			new $.WCBraintreePayOrderBackboneModal.View({
				target: settings.template,
				string: settings.variable
			});
		}
	}
	
	$.WCBraintreePayOrderBackboneModal.View = $.WCBackboneModal.View.extend({
		events: _.extend($.WCBackboneModal.View.prototype.events, {
			'click #pay-order': 'pay_order',
			'change [name="payment_type"]': 'payment_type'
		}),
		render: function(){
			$.WCBackboneModal.View.prototype.render.apply(this, arguments);
			this.$el.find('.wc-select2').select2();
			this.payment_type();
		},
		pay_order: function(e){
			e.preventDefault();
			$( document.body ).trigger( 'wc_backbone_pay_order_modal_response', [ this ] );
		},
		payment_type: function(e){
			var val = this.$el.find('[name="payment_type"]:checked').val();
			var show_if = '.show_if_' + val, hide_if = '.hide_if_' + val;
			this.$el.find(show_if).show();
			this.$el.find(hide_if).hide();
		},
		block: function(){
			this.$el.find('.wc-backbone-modal-content').block({
				message : null,
				overlayCSS : {
					background : '#fff',
					opacity : 0.6
				}
			});
		},
		unblock: function(){
			this.$el.find('.wc-backbone-modal-content').unblock();
		},
		add_messages: function(messages){
			this.$el.find('.woocommerce-error').remove();
			this.$el.find('form').prepend(messages);
		},
		use_token: function(){
			return this.$el.find('[name="payment_type"]:checked').val() === 'token';
		},
		set_nonce: function(value){
			this.$el.find('[name="payment_nonce"]').val(value);
		}
	});
}(jQuery));
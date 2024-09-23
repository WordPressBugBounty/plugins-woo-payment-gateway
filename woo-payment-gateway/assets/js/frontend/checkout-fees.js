(function($){
	var fees = {
			init: function(){
				$(document.body).on('change', 'input[name="payment_method"]', this.update_fees);
			},
			update_fees: function(){
				$( document.body ).trigger( 'update_checkout' );
			}
	}
	fees.init();
}(jQuery));
jQuery(function($){
	var meta_box = {
			params: wc_braintree_meta_boxes_subscription_params,
			init: function(){
				$(document.body).on('wc_backbone_modal_loaded', this.add_modal_data);
			},
			add_modal_data: function(e, type){
				switch(type){
					case 'wc-modal-add-products':
						meta_box.add_exluded_products();
						break;
				}
			},
			add_exluded_products: function(){
				$('.wc-product-search').data('exclude', meta_box.params.excluded_products);
			}
	}
	meta_box.init();
})
jQuery(function($){
	var settings = {
			params: woocommerce_braintree_fee_settings_params,
			init: function(){
				new FeeContainer();
			},
			get_index: function(){
				return $('#wc-braintree-fee-tbody tr').length;
			}
	}
	
	var Fee = Backbone.Model.extend({
		defaults: {
			name: '',
			calculation: '',
			tax_status: 'none',
			gateways: [],
			index: 0
		}
	});
	var FeeView = Backbone.View.extend({
		tagName: 'tr',
		template: _.template($('#wc-braintree-fee-template').html()),
		events: {
			'click .wc-braintree-delete-row': 'removeFee'
		},
		initialize: function(params){
			this.parent = params.parent
			this.render();
		},
		render: function(){
			this.$el.html(this.template(this.model.toJSON()));
			var pre_select = [];
			_.each(this.model.get('gateways'), function(id, index){
				this.$el.find('select option[value="' + id + '"]').attr('selected', true);
			}, this)
			this.$el.find('.wc-enhanced-select').select2();
			return this;
		},
		removeFee: function(){
			this.remove();
		}
	});
	var FeesCollection = Backbone.Collection.extend({
		model: Fee
	});
	
	var FeesView = Backbone.View.extend({
		el: '#wc-braintree-fee-tbody',
		tagName: 'tbody',
		initialize: function(params){
			this.feesCollection = params.feesCollection;
			this.render();
		},
		render: function(){
			this.feesCollection.each(function(fee, index){
				var feeView = new FeeView({
					model: fee,
					index: index
				});
				this.$el.append(feeView.$el);
			}, this);
			return this;
		},
		addFee: function(){
			var feeView = new FeeView({
				model: new Fee({index: settings.get_index()}),
			});
			this.$el.append(feeView.$el);
		}
	});
	var FeeContainer = Backbone.View.extend({
		el: '.wc-braintree-fee-container',
		table: '.wc-braintree-fee-table',
		events: {
			'click .wc-braintree-add-fee': 'addFee'
		},
		initialize: function(){
			var feesCollection = new FeesCollection();
			//populate the feesCollection with existing fees.
			_.each(settings.params.fees, function(fee, index){
				feesCollection.add(new Fee({
					name: fee.name,
					calculation: fee.calculation,
					tax_status: fee.tax_status,
					gateways: fee.gateways,
					index: index
				}));
			});
			this.feesView = new FeesView({
				feesCollection: feesCollection,
			});
			$(this.table).append(this.feesView.$el);
		},
		render: function(){
			return this;
		},
		addFee: function(e){
			e.preventDefault()
			this.feesView.addFee();
		}
	});
	
	settings.init();
})
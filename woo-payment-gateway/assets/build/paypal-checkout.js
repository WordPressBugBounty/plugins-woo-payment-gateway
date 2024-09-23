!function(){var e,t={30:function(e,t,n){var r,a=(r=n(567))&&r.__esModule?r:{default:r},i=n(345);function o(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(e);t&&(r=r.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,r)}return n}function s(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?o(Object(n),!0).forEach((function(t){c(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):o(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function c(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}function p(){wc_braintree.PayPal.call(this)}p.prototype=a.default.extend({},wc_braintree.PayPal.prototype,wc_braintree.CheckoutGateway.prototype,{params:wc_braintree_paypal_checkout_params}),p.prototype.initialize=function(){wc_braintree.CheckoutGateway.call(this)},p.prototype.create_instance=function(){var e=arguments,t=this;return(0,i.loadScript)(this.params.query_params).then((function(n){return wc_braintree.PayPal.prototype.create_instance.apply(t,e).then(function(){if(this.banner_enabled&&(0,a.default)(this.banner_container).length){(0,a.default)(".wc-braintree-paypal-top-container").remove(),(0,a.default)(this.banner_container).prepend('<div class="wc-braintree-paypal-top-container"></div>');var e=this.render_options();n.Buttons(a.default.extend({},this.render_options(n.FUNDING.PAYPAL),{onInit:function(){}.bind(this),onClick:function(){this.set_payment_method(this.gateway_id),this.set_use_nonce_option(!0),(0,a.default)('[name="terms"]').prop("checked",!0).trigger("change"),e.onClick.apply(this,arguments),this.processingBannerCheckout=!0}.bind(this),onCancel:function(){this.processingBannerCheckout=!1}.bind(this)})).render(".wc-braintree-paypal-top-container")}(0,a.default)(".wc_braintree_banner_gateways").addClass("paypal-active"),(0,a.default)(document.body).on("wc_braintree_payment_method_selected",this.payment_gateway_changed.bind(this)),(0,a.default)(document.body).on("wc_braintree_display_saved_methods",this.display_saved_methods.bind(this)),(0,a.default)(document.body).on("wc_braintree_display_new_payment_method",this.display_new_payment_method_container.bind(this)),(0,a.default)(document.body).on("change",'[name="terms"]',this.handle_terms_click.bind(this)),(0,a.default)(document.body).on("change",'[type="checkbox"]',this.handle_checkbox_change.bind(this)),setTimeout(this.create_button.bind(this),5e3),setInterval(wc_braintree.PayPal.prototype.create_button.bind(this),5e3)}.bind(t)).catch(function(e){throw e}.bind(t))})).catch((function(e){console.log(e)}))},p.prototype.create_button=function(){wc_braintree.PayPal.prototype.create_button.call(this).then(function(){this.payment_gateway_changed(null,this.get_selected_gateway()),this.create_bnpl_msg("checkout","form.checkout .shop_table")}.bind(this))},p.prototype.render_options=function(){var e=wc_braintree.PayPal.prototype.render_options.apply(this,arguments),t=a.default.extend({},e,{onInit:function(){e.onInit.apply(this,arguments),this.handle_terms_click()}.bind(this),onClick:function(){if(this.processingBannerCheckout=!1,this.fields.fromFormToFields(),!this.is_valid_checkout())return this.submit_error(this.params.messages.terms);e.onClick.apply(this,arguments)}.bind(this)});return t},p.prototype.handle_tokenize_response=function(e){this.needs_shipping()?(this.tokenize_response=e,this.set_nonce(e.nonce),this.set_device_data(),this.payment_method_received=!0,(0,a.default)(document.body).one("updated_checkout",function(){this.on_payment_method_received.call(this,e)}.bind(this)),this.update_addresses(e.details),this.maybe_set_ship_to_different(),this.fields.toFormFields({update_shipping_method:!1}),(0,a.default)(document.body).trigger("update_checkout",{update_shipping_method:!1})):wc_braintree.PayPal.prototype.handle_tokenize_response.call(this,e)},p.prototype.get_funding=function(){var e=[];return"1"===this.params.card_icons&&e.push(paypal.FUNDING.CARD),this.is_credit_enabled("checkout")&&(e.push(paypal.FUNDING.PAYLATER),e.push(paypal.FUNDING.CREDIT)),e.concat(wc_braintree.PayPal.prototype.get_funding.apply(this,arguments))},p.prototype.on_payment_method_received=function(){if(wc_braintree.CheckoutGateway.prototype.on_payment_method_received.apply(this,arguments),this.validate_checkout_fields())if(this.needs_shipping()){var e=this.get_address_object(this.get_shipping_prefix(),["phone"]);(0,a.default)('[name^="shipping_method"]').length<2||JSON.stringify(this.shipping_address)==JSON.stringify(e)?this.get_form().trigger("submit"):this.processingBannerCheckout&&this.scroll_to_place_order()}else this.get_form().trigger("submit")},p.prototype.scroll_to_place_order=function(){(0,a.default)("html, body").animate({scrollTop:(0,a.default)("#place_order").offset().top-100},1e3)},p.prototype.handle_terms_click=function(){(0,a.default)('[name="terms"]').length&&((0,a.default)('[name="terms"]').is(":checked")?this.actions.enable():this.actions.disable())},p.prototype.handle_checkbox_change=function(){setTimeout(this.handle_terms_click.bind(this),250)},p.prototype.needs_shipping=function(){return!!this.processingBannerCheckout&&wc_braintree.BaseGateway.prototype.needs_shipping.call(this)},p.prototype.updated_checkout=function(e,t){var n,r=this;if(null!=t&&null!==(n=t.fragments)&&void 0!==n&&n.paypal_query_args){var a=t.fragments.paypal_query_args,o="".concat(this.params.query_params.vault,"-").concat(this.params.query_params.currency),c="".concat(a.vault,"-").concat(a.currency);if(this.params.query_params=s(s({},this.params.query_params),t.fragments.paypal_query_args),this.params.options=s(s({},this.params.options),t.fragments.paypal_options),o!==c)return(0,i.loadScript)(this.params.query_params).then((function(){wc_braintree.CheckoutGateway.prototype.updated_checkout.call(r)}))}wc_braintree.CheckoutGateway.prototype.updated_checkout.call(this)},wc_braintree.register(p)},567:function(e){"use strict";e.exports=window.jQuery}},n={};function r(e){var a=n[e];if(void 0!==a)return a.exports;var i=n[e]={exports:{}};return t[e](i,i.exports,r),i.exports}r.m=t,e=[],r.O=function(t,n,a,i){if(!n){var o=1/0;for(h=0;h<e.length;h++){n=e[h][0],a=e[h][1],i=e[h][2];for(var s=!0,c=0;c<n.length;c++)(!1&i||o>=i)&&Object.keys(r.O).every((function(e){return r.O[e](n[c])}))?n.splice(c--,1):(s=!1,i<o&&(o=i));if(s){e.splice(h--,1);var p=a();void 0!==p&&(t=p)}}return t}i=i||0;for(var h=e.length;h>0&&e[h-1][2]>i;h--)e[h]=e[h-1];e[h]=[n,a,i]},r.d=function(e,t){for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},r.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},function(){var e={129:0};r.O.j=function(t){return 0===e[t]};var t=function(t,n){var a,i,o=n[0],s=n[1],c=n[2],p=0;if(o.some((function(t){return 0!==e[t]}))){for(a in s)r.o(s,a)&&(r.m[a]=s[a]);if(c)var h=c(r)}for(t&&t(n);p<o.length;p++)i=o[p],r.o(e,i)&&e[i]&&e[i][0](),e[i]=0;return r.O(h)},n=self.webpackChunkwoo_payment_gateway=self.webpackChunkwoo_payment_gateway||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))}();var a=r.O(void 0,[216],(function(){return r(30)}));a=r.O(a)}();
//# sourceMappingURL=paypal-checkout.js.map
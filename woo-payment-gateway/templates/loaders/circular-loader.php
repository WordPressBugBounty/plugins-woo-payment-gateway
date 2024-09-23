<?php 
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<div class="loader"></div>

<style>
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader{
	background: rgba(0,0,0,.65);
	display: flex;
	display: -webkit-flex;
    justify-content: center;
    align-items: center;
}
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .loader,
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .loader:after {
  border-radius: 50%;
  width: 10em;
  height: 10em;
}
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .loader {
  margin: 60px auto;
  font-size: 10px;
  position: relative;
  border-top: 1.1em solid rgba(255, 255, 255, 0.2);
  border-right: 1.1em solid rgba(255, 255, 255, 0.2);
  border-bottom: 1.1em solid rgba(255, 255, 255, 0.2);
  border-left: 1.1em solid #ffffff;
  -webkit-transform: translateZ(0);
  -ms-transform: translateZ(0);
  transform: translateZ(0);
  -webkit-animation: circular-animation 1.1s infinite linear;
  animation: circular-animation 1.1s infinite linear;
}
@-webkit-keyframes circular-animation {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@keyframes circular-animation {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}

</style>
<?php 
/**
 * @version 3.0.0
 * @package Braintree/Templates
 */
?>
<div class="hourglass">
  <div class="top"></div>
  <div class="bottom"></div>
</div>
<style>
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader{
  background: rgba(0,0,0, .75);
}

body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .hourglass {
    margin: 0;
    height: 100%;
    width: 100%;
    animation: flip 6s ease infinite;
    /* text-align: center; */
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    flex-direction: column;
    justify-content: center;
}

body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .top,
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .bottom {
  background-color: rgba(239, 236, 202, 0.15);
  background-image: linear-gradient(#EFECCA, #EFECCA);
  background-size: 100px 100px;
  background-repeat: no-repeat;
  width: 100px;
  height: 100px;
}

body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .top {
  background-position: 0 0;
  animation: top-do 6s ease infinite;
  -webkit-clip-path: polygon(0% 0%, 100% 0%, 50% 100%);
}

body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .bottom {
  background-position: 0 100px;
  animation: bottom-do 6s ease infinite;
  -webkit-clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
}
body.wc-braintree-body .wc-braintree-new-payment-method-container .wc-braintree-payment-loader .bottom:after {
  content: "";
  position: absolute;
  width: 2px;
  height: 100px;
  left: 49px;
  background-image: linear-gradient(#EFECCA, transparent);
}

@keyframes top-do {
  95%, 100% {
    background-position: 0 100px;
  }
}
@keyframes bottom-do {
  95%, 100% {
    background-position: 0 0;
  }
}
@keyframes flip {
  0%, 95% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(180deg);
  }
}

</style>
<?php defined( 'ABSPATH' ) || exit(); ?>
<script type="text/template" id="tmpl-wc-braintree-message">
    <div class="wc-backbone-modal">
        <div class="wc-backbone-modal-content wc-transaction-data">
            <section class="wc-backbone-modal-main" role="main">
                <header class="wc-backbone-modal-header">
                    <h1><?php esc_html_e( 'Message', 'woo-payment-gateway' ); ?></h1>
                    <button
                            class="modal-close modal-close-link dashicons dashicons-no-alt">
                        <span class="screen-reader-text">Close modal panel</span>
                    </button>
                </header>
                <article class="wc-braintree-modal-message">
                    <#if(data.message){#>
                      {{{ data.message }}}
                    <#}else if (data.messages){#>
                        <#for(var i=0;i<data.messages.length;i++){#>
                            <p>{{{data.messages[i]}}}</p>
                        <#}#>
                    <#}#>
                </article>
                <footer>
                    <div class="inner">

                    </div>
                </footer>
            </section>
        </div>
    </div>
    <div class="wc-backbone-modal-backdrop modal-close"></div>
</script>


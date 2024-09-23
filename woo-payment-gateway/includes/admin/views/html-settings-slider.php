<?php defined( 'ABSPATH' ) || exit(); ?>
<tr valign="top">
    <th scope="row" class="titledesc"><label
                for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?><?php echo $this->get_tooltip_html( $data ); // WPCS: XSS ok. ?></label>
    </th>
    <td class="forminp">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php echo wp_kses_post( $data['title'] ); ?></span>
            </legend>
            <div type="submit" class="wc-braintree-slider <?php echo esc_attr( $data['class'] ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>"
                 value="<?php echo $field_key; ?>" <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. ?>>
                <span class="wc-braintree-slider-val"><?php echo $value ?>px</span>
            </div>
            <input type="hidden" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>"
                   value="<?php echo $value ?>"/>
			<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. ?>
        </fieldset>
    </td>
</tr>


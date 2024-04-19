<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

/**
 * The 2Checkout field is a credit card field used specifically by the 2Checkout Add-On.
 *
 * @since 1.2
 *
 * Class GF_Field_2Checkout_CreditCard
 */
class GF_Field_2Checkout_CreditCard extends GF_Field {

	/**
	 * Field type.
	 *
	 * @since 1.2
	 *
	 * @var string
	 */
	public $type = '2checkout_creditcard';

	/**
	 * Get field button title.
	 *
	 * @since 1.2
	 *
	 * @return string
	 */
	public function get_form_editor_field_title() {
		return esc_attr__( '2Checkout', 'gravityforms2checkout' );
	}

	/**
	 * Returns the field's form editor icon.
	 *
	 * This could be an icon url or a dashicons class.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_form_editor_field_icon() {
		return gf_2checkout()->get_base_url() . '/images/menu-icon.svg';
	}

	/**
	 * Returns the field's form editor description.
	 *
	 * @since 3.8
	 *
	 * @return string
	 */
	public function get_form_editor_field_description() {
		return esc_attr__( 'Allows accepting credit card information to make payments via 2Checkout payment gateway.', 'gravityforms2checkout' );
	}

	/**
	 * Returns the scripts to be included for this field type in the form editor.
	 *
	 * @since  2.6
	 *
	 * @return string
	 */
	public function get_form_editor_inline_script_on_page_render() {
		// Allow only one 2Checkout field to exist on a form.
		$js = sprintf(
			"
			function SetDefaultValues_%s(field) {
				field.label = '%s';
				field.inputs = [new Input(field.id + '.1', %s), new Input(field.id + '.4', %s), new Input(field.id + '.5', %s)];
			}",
			$this->type,
			esc_html__( 'Credit Card', 'gravityforms2checkout' ),
			json_encode( gf_apply_filters( array( 'gform_card_details', rgget( 'id' ) ), esc_html__( 'Card Details', 'gravityforms2checkout' ), rgget( 'id' ) ) ),
			json_encode( gf_apply_filters( array( 'gform_card_type', rgget( 'id' ) ), esc_html__( 'Card Type', 'gravityforms2checkout' ), rgget( 'id' ) ) ),
			json_encode( gf_apply_filters( array( 'gform_card_name', rgget( 'id' ) ), esc_html__( 'Cardholder Name', 'gravityforms2checkout' ), rgget( 'id' ) ) )
		) . PHP_EOL;

		$js .= "gform.addFilter('gform_form_editor_can_field_be_added', function(result, type) {
					if (type === '2checkout_creditcard') {
						if (GetFieldsByType(['2checkout_creditcard']).length > 0) {" .
							sprintf( 'alert(%s);', json_encode( esc_html__( 'Only one 2Checkout field can be added to the form', 'gravityforms2checkout' ) ) )
							. ' result = false;
						}
					}
					
					return result;
				});';

		return $js;
	}

	/**
	 * Get field settings in the form editor.
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public function get_form_editor_field_settings() {
		return array(
			'conditional_logic_field_setting',
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'description_setting',
		);
	}

	/**
	 * Get form editor button.
	 *
	 * @since 1.2
	 * @since 3.4 Add the 2Checkout field only when checkout method is not Checkout.
	 *
	 * @return array
	 */
	public function get_form_editor_button() {
		return array(
			'group'       => 'pricing_fields',
			'text'        => $this->get_form_editor_field_title(),
			'icon'        => $this->get_form_editor_field_icon(),
			'description' => $this->get_form_editor_field_description(),
		);
	}

	/**
	 * Used to determine the required validation result.
	 *
	 * @since 1.2
	 *
	 * @param int $form_id The ID of the form currently being processed.
	 *
	 * @return bool
	 */
	public function is_value_submission_empty( $form_id ) {
		// check only the cardholder name.
		$cardholder_name_input = GFFormsModel::get_input( $this, $this->id . '.5' );
		$cardholder_name       = rgpost( 'input_' . $this->id . '_5' );

		return empty( $cardholder_name );
	}

	/**
	 * Get submission value.
	 *
	 * @since 1.2
	 *
	 * @param array $field_values Field values.
	 * @param bool  $get_from_post_global_var True if get from global $_POST.
	 *
	 * @return array|string
	 */
	public function get_value_submission( $field_values, $get_from_post_global_var = true ) {

		if ( $get_from_post_global_var ) {
			$value[ $this->id . '.1' ] = $this->get_input_value_submission( 'input_' . $this->id . '_1', rgar( $this->inputs[0], 'name' ), $field_values, true );
			$value[ $this->id . '.4' ] = $this->get_input_value_submission( 'input_' . $this->id . '_4', rgar( $this->inputs[1], 'name' ), $field_values, true );
			$value[ $this->id . '.5' ] = $this->get_input_value_submission( 'input_' . $this->id . '_5', rgar( $this->inputs[2], 'name' ), $field_values, true );
		} else {
			$value = $this->get_input_value_submission( 'input_' . $this->id, $this->inputName, $field_values, $get_from_post_global_var );
		}

		return $value;
	}

	/**
	 * Get field input.
	 *
	 * @since 1.2
	 *
	 * @param array      $form  The Form Object currently being processed.
	 * @param array      $value The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param null|array $entry Null or the Entry Object currently being edited.
	 *
	 * @return string
	 */
	public function get_field_input( $form, $value = array(), $entry = null ) {
		$form_id  = $form['id'];
		$id       = intval( $this->id );
		$field_id = $this->is_entry_detail() || $this->is_form_editor() || $form_id === 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$disabled_text = $this->is_form_editor() ? "disabled='disabled'" : '';
		$class_suffix  = $this->is_entry_detail() ? '_admin' : '';

		// If we are in the form editor, display a placeholder field.
		if ( $this->is_admin() ) {

			$cc_input = "<div class='gf-2checkout-card ginput_complex ginput_container ginput_container_creditcard' id='{$field_id}'>
							<span class='card-details-container'>
								<span class='ginput_full' id='{$field_id}_number_container'>
									<label for='{$field_id}_1' id='{$field_id}_1_label'>" . esc_html__( 'Card Number', 'gravityforms2checkout' ) . "</label>
									<input type='text' id='{$field_id}_1' name='{$field_id}.1' {$disabled_text}>
								</span>
								<span class='ginput_full extra' id='{$field_id}_extra_container'>
									<span class='card-expiry'>
										<label for='{$field_id}_3' id='{$field_id}_3_label'>" . esc_html__( 'Expiration date', 'gravityforms2checkout' ) . "</label>
										<input type='text' id='{$field_id}_3' name='{$field_id}.3' {$disabled_text}>									
									</span>
									<span class='card-security'>
										<label for='{$field_id}_4' id='{$field_id}_4_label'>" . esc_html__( 'Security code', 'gravityforms2checkout' ) . "</label>
										<input type='text' id='{$field_id}_4' name='{$field_id}.4' {$disabled_text}>									
									</span>
								</span>	
								<span class='ginput_full' id='{$field_id}_name_container'>
									<label for='{$field_id}_5' id='{$field_id}_5_label'>" . esc_html__( 'Cardholder Name', 'gravityforms2checkout' ) . "</label>
									<input type='text' id='{$field_id}_5' name='{$field_id}.5' {$disabled_text}>
								</span>															
							</span>
						</div>";

			return $cc_input;
		}

		$cardholder_name = '';
		if ( ! empty( $value ) ) {
			$cardholder_name = esc_attr( rgget( $this->id . '.5', $value ) );
		}

		$card_error = $this->get_card_error_message( '' );

		$cc_input = "<div class='ginput_complex{$class_suffix} ginput_container gf2checkout-creditcard ginput_container_creditcard' id='{$field_id}'>"
					. "<div class='ginput_full gf2checkout-creditcard-details' id='{$field_id}_1_container'>"
					. "<div id='{$field_id}_1'></div>{$card_error}</div>"
					. "<div class='ginput_full gf2checkout-creditcard-name' id='{$field_id}_5_container'>
							<label for='{$field_id}_5' id='{$field_id}_5_label' >" . esc_html__( 'Cardholder Name', 'gravityforms2checkout' ) . "</label>
							<input type='text' name='input_{$id}.5' id='{$field_id}_5' value='{$cardholder_name}'>
					 	</div>
					 </div>";

		return $cc_input;
	}

	/**
	 * Returns the field markup; including field label, description, validation, and the form editor admin buttons.
	 * The {FIELD} placeholder will be replaced in GFFormDisplay::get_field_content with the markup returned by GF_Field::get_field_input().
	 *
	 * @since 1.2
	 *
	 * @param string|array $value                The field value. From default/dynamic population, $_POST, or a resumed incomplete submission.
	 * @param bool         $force_frontend_label Should the frontend label be displayed in the admin even if an admin label is configured.
	 * @param array        $form                 The Form Object currently being processed.
	 *
	 * @return string
	 */
	public function get_field_content( $value, $force_frontend_label, $form ) {
		$field_content = parent::get_field_content( $value, $force_frontend_label, $form );
		return GFCommon::is_ssl() || $this->is_admin()
			? $field_content
			: "<div class='gfield_creditcard_warning_message validation_message'><span>" . esc_html__( 'This page is unsecured. Do not enter a real credit card number! Use this field only for testing purposes. ', 'gravityforms2checkout' ) . '</span></div>' . $field_content;
	}

	/**
	 * Get entry inputs.
	 *
	 * @since 1.2
	 *
	 * @return array|null
	 */
	public function get_entry_inputs() {
		return array_filter(
			$this->inputs,
			function( $input ) {
				return in_array( $input['id'], array( $this->id . '.1', $this->id . '.4', $this->id . '.5' ), true );
			}
		);
	}

	/**
	 * Get the value in entry details.
	 *
	 * @since 1.2
	 *
	 * @param string|array $value The field value.
	 * @param string       $currency The entry currency code.
	 * @param bool|false   $use_text When processing choice based fields should the choice text be returned instead of
	 *       the value.
	 * @param string       $format The format requested for the location the merge is being used. Possible values: html, text
	 *           or url.
	 * @param string       $media The location where the value will be displayed. Possible values: screen or email.
	 *
	 * @return string
	 */
	public function get_value_entry_detail( $value, $currency = '', $use_text = false, $format = 'html', $media = 'screen' ) {
		if ( ! is_array( $value ) ) {
			return '';
		}

		$card_number     = trim( rgget( $this->id . '.1', $value ) );
		$card_type       = trim( rgget( $this->id . '.4', $value ) );
		$cardholder_name = trim( rgget( $this->id . '.5', $value ) );
		$separator       = $format === 'html' ? '<br/>' : "\n";

		$value = empty( $card_number ) ? '' : $card_type . $separator . $card_number . $separator . $cardholder_name;

		return $value;
	}

	/**
	 * Display the 2Checkout  field error message.
	 *
	 * @since 1.2
	 *
	 * @param string $message The error message.
	 *
	 * @return string
	 */
	private function get_card_error_message( $message ) {
		return '<div class="gfield_description validation_message">' . esc_html( $message ) . '</div>';
	}

	/**
	 * Check if we are on an admin page.
	 *
	 * @since 1.2
	 *
	 * @return bool
	 */
	private function is_admin() {
		return $this->is_entry_detail() || $this->is_form_editor();
	}

}

GF_Fields::register( new GF_Field_2Checkout_CreditCard() );

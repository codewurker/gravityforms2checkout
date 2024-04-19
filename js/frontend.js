window.GF2Checkout = null;

( function( $ ) {

	a11yErrorHandler = function( GF2Checkout ) {
		/**
		 * List of messages to be spoken.
		 * @array speechMessages
		 */
		this.speechMessages = [];

		/**
		 * setTimeout Speak function handler.
		 * @int speaker
		 */
		this.speaker = null;

		/**
		 * Errors object, contains all current error messages with field types as keys.
		 * @Object errors
		 */
		this.errors = {};

		/**
		 * Gf2checkout input types (card number, cvv, ..etc ).
		 * @array types
		 */
		this.types = GF2Checkout.fields;

		/**
		 * Adds an error.
		 *
		 * @param type field type.
		 * @param message error message.
		 */
		this.push = function ( type, message ) {
			// Make sure provided type is a field type we already have.
			type = this.getErrorType( type );
			this.errors[ type ] = message;
			// Display error and add it to speech array.
			this.declareError( type, message );
			// delay invoking the speak function in case more errors are being pushed.
			setTimeout( this.speak.bind( this ), 500 );
		}

		/**
		 * Remove errors from the display.
		 *
		 * @param {string} type
		 */
		this.remove = function( type ) {
			type = this.getErrorType( type );
			this.errors[ type ] = null;
			this.clearError( type );
		};

		/**
		 * Removes all errors and declares all good status! ready to submit.
		 */
		this.allGood = function () {
			Object.keys( this.errors ).forEach(
				function ( type, index ) {
					this.clearError( type );
				}.bind( this )
			)
		}

		/**
		 * Get the type for an error.
		 *
		 * Generic errors appear beneath after field container ( not beneath a particular input like card number ).
		 *
		 * @param {null|Array|string} type
		 *
		 * @return {string} The error type.
		 */
		this.getErrorType = function ( type ) {
			if ( type == null || ! type in this.types ) {
				type = 'generic';
			}

			return type;
		}

		/**
		 * Removes error message from UI.
		 * @param type field type.
		 */
		this.clearError = function( type ) {
			// Get field container.
			var container = this.getFieldContainer( type );
			// If validation message exist after container, find error div within and remove it.
			var $validationMessageContainer = container.next( '.validation_message' );
			if ( $validationMessageContainer.length ) {
				$validationMessageContainer.find( 'div.' + type ).remove();
			}
			container.removeClass( 'is-error' );
		}

		/**
		 * Displays a single error, adds error message to speech array to be spoken later.
		 *
		 * @param type field type.
		 * @param message error message.
		 */
		this.declareError = function( type, message ) {
			var container = this.getFieldContainer( type );
			var $errorDiv;

			// Add error class to container.
			container.addClass( 'is-error' );
			// Make sure error container exists after field container, if not create it.
			if ( ! container.next( '.validation_message' ).length ) {
				container.after( '<div class="gfield_description validation_message"></div>' );
			}
			// get error div or create it.
			$errorDiv = container.next( '.validation_message' ).find( 'div.' + type );
			if ( $errorDiv.length ) {
				// If error div exists check if it has the same error, otherwise display new error.
				if ( $errorDiv.text() !== message ) {
					$errorDiv.text( message );
				}
			} else {
				// Error div for this type never existed, create and display it.
				container.next( '.validation_message' ).append( '<div class="' + type + '">' + message + '</div>' );
			}
			// Add error message to speech array even if it existed before.
			// Speak the error again if it's triggered while the error message is already displayed.
			this.speechMessages.push( message );
		}

		/**
		 * Combines all current error messages to one message and speaks it.
		 */
		this.speak = function () {
			// if speak was already invoked, quit.
			if (this.speaker !== null ) {
				return;
			}
			this.speaker = setTimeout(
				function() {
					var toSpeak = '';
					// Sometimes the same error message is added to the array twice when field loses focus before submit is clicked, filter repeated messages.
					var uniqueErrors = this.speechMessages.filter(
						function ( value, index, self) {
							return self.indexOf( value ) === index;
						}
					);
					// Generate Error speech by imploding messages array into one string, calling wp.a11y.speak in a loop failed.
					uniqueErrors.forEach(
						function ( error, index ) {
							// separate error message by new line so VO don't announce them as one line ( dot was not enough ! ).
							toSpeak += '\n' + error;
						}
					);
					// Finally speak.
					wp.a11y.speak( toSpeak );
					// Reset evey thing for next push.
					this.speaker = null;
					this.speechMessages = [];
				}.bind( this ),
				250
			);
		}

		/**
		 * Gets field container
		 * @param type field type.
		 */
		this.getFieldContainer = function ( type ) {
			if ( type === 'generic' ) {
				return $( '.gf2checkout-creditcard' );
			}
			// Decide where will the error be displayed ? next to credit card details or next to card holder name.
			var container = '';
			if ( [ 'cardNumber', 'expirationDate', 'cvv', 'postalCode' ].includes( type ) ) {
				container = $( '.gf2checkout-creditcard-details' );
			} else {
				container = $( 'gf2checkout-creditcard-name' );
			}

			return container;
		}
	}

	GF2Checkout = function( args ) {

		var self = this;

		self.form = null;
		self.jsPaymentClient = null ;

		for ( var prop in args ) {
			if ( args.hasOwnProperty( prop ) ) {
				this[ prop ] = args[ prop ];
			}
		}

		self.get2Pay = function() {
			return self.jsPaymentClient;
		}

		self.errorStack = null;
		self.fields = [ 'cardHolderName' ];

		/**
		 * Initialize 2Checkout.
		 *
		 * @since 1.0
		 */
		self.init = function() {

			if ( ! self.isCreditCardOnPage() ) {
				return;
			}

			var GF2CheckoutObj = this, activeFeed = null, feedActivated = false, merchantCode = this.merchantCode, secretKey = this.secretKey;

			this.form = $('#gform_' + this.formId);
			this.GFCCField = $('#input_' + this.formId + '_' + this.ccFieldId + '_1');

			// Initial state.
			self.resetPaymentForm();

			self.errorStack = new a11yErrorHandler( GF2CheckoutObj );

			gform.addAction( 'gform_frontend_feeds_evaluated', function ( feeds, formId ) {

				if ( formId !== GF2CheckoutObj.formId ) {
					return;
				}

				var activeFeed = false;
				// Loop through the feeds and Check if 2Checkout feed is active.
				var feedsCount = Object.keys( feeds ).length;
				for ( var i = 0; i < feedsCount; i++ ) {
					if ( feeds[ i ].addonSlug === 'gravityforms2checkout' && feeds[ i ].isActivated && self.isCreditCardOnPage() ) {
						self.initPaymentForm();
						activeFeed = true;
						break;
					}
				}

				if( ! activeFeed ) {
					self.resetPaymentForm();
				}

				// Bind 2Checkout functionality to submit event.
				$( '#gform_' + self.formId ).on( 'submit', function( e ) {

					self.form = $( this );

					if ( $( this ).data( 'gf_2checkout_submitting' ) || $( '#gform_save_' + self.formId ).val() == 1 || ! self.isCreditCardOnPage() ) {
						return;
					}

					e.preventDefault();
					$( this ).data( 'gf_2checkout_submitting', true );
					self.maybeAddSpinner();


					// Extract the Name field value
					var billingDetails = {
					  name: self.form.find( '#input_' + self.formId + '_' + self.ccFieldId + '_5' ).val()
					};

					// Call the generate method using the component as the first parameter
					// and the billing details as the second one
					GF2CheckoutObj.jsPaymentClient.tokens.generate(GF2CheckoutObj.component, billingDetails).then( function ( response ) {
						self.errorStack.allGood();
						// Append 2pay.js response
						self.form.append( $( '<input type="hidden" name="2checkout_response" />' ).val( response.token ) );
						// submit the form
						self.form.submit();
					}).catch( function( error ) {
						self.resetFormStatus( self.form, self.formId, self.isLastPage() );
						self.errorStack.push( 'generic', error );
						return false;
					});

				} );

			});
		};

		self.buildPaymentForm = function () {
			//Reset if already created.
			if ( self.jsPaymentClient !== null ) {
				$( '#input_' + self.formId + '_' + self.ccFieldId + '_1' ).html('');
			}

			// Initialize the JS Payments SDK client.
			self.jsPaymentClient = new TwoPayClient( self.merchantCode );

			// Create the component that will hold the card fields.
			self.component = self.jsPaymentClient.components.create('card', {
				margin: 0,
				fontFamily: 'Helvetica, sans-serif',
				fontSize: '1rem',
				fontWeight: '400',
				lineHeight: '1.5',
				color: '#212529',
				textAlign: 'left',
				backgroundColor: 'transparent',
				'*': {
					'boxSizing': 'border-box'
				},
				'.no-gutters': {
					marginRight: 0,
					marginLeft: 0
				},
				'.row': {
					display: 'flex',
					flexWrap: 'wrap',
					height: '96px'
				},
				'.col': {
					flexBasis: '0',
					flexGrow: '1',
					maxWidth: '100%',
					padding: '0',
					position: 'relative',
					width: '100%'
				},
				'.two-co-iframe-hidden-tabbable' : {
					display: 'none',
				},
				'div': {
					display: 'block'
				},
				'.field-container': {
					paddingBottom: '14px'
				},
				'.input-wrapper': {
					position: 'relative'
				},
				label: {
					display: 'inline-block',
					marginBottom: '9px',
					color: '#313131',
					fontSize: '14px',
					fontWeight: '300',
					lineHeight: '17px'
				},
				'input': {
					overflow: 'visible',
					margin: 0,
					fontFamily: 'inherit',
					display: 'block',
					width: '100%',
					height: '42px',
					padding: '10px 12px',
					fontSize: '18px',
					fontWeight: '400',
					lineHeight: '22px',
					color: '#313131',
					backgroundColor: '#fff',
					backgroundClip: 'padding-box',
					border: '1px solid #CBCBCB',
					borderRadius: '3px',
					transition: 'border-color .15s ease-in-out,box-shadow .15s ease-in-out',
					outline: 0
				},
				'.is-error input': {
					border: '1px solid #D9534F'
				},
				'.is-valid input': {
					border: '1px solid #1BB43F'
				},
				'.validation-message': {
					color: '#c02b0a',
					fontSize: '10px',
					fontStyle: 'italic',
					marginTop: '6px',
					marginBottom: '-5px',
					display: 'block',
					lineHeight: '1'
				},
				'.card-expiration-date': {
					paddingRight: '.5rem'
				},
				'.is-empty input': {
					color: '#EBEBEB'
				},
				'.lock-icon': {
					top: 'calc(50% - 7px)',
					right: '10px'
				},
				'.valid-icon': {
					display: 'none'
				},
				'.error-icon': {
					display: 'none'
				},
				'.card-icon': {
					top: 'calc(50% - 10px)',
					left: '10px',
					display: 'none'
				},
				'.is-empty .card-icon': {
					display: 'block'
				},
				'.is-focused .card-icon': {
					display: 'none'
				},
				'.card-type-icon': {
					right: '30px',
					display: 'block'
				},
				'.card-type-icon.visa': {
					top: 'calc(50% - 14px)'
				},
				'.card-type-icon.mastercard': {
					top: 'calc(50% - 14.5px)'
				},
				'.card-type-icon.amex': {
					top: 'calc(50% - 14px)'
				},
				'.card-type-icon.discover': {
					top: 'calc(50% - 14px)'
				},
				'.card-type-icon.jcb': {
					top: 'calc(50% - 14px)'
				},
				'.card-type-icon.dankort': {
					top: 'calc(50% - 14px)'
				},
				'.card-type-icon.cartebleue': {
					top: 'calc(50% - 14px)'
				},
				'.card-type-icon.diners': {
					top: 'calc(50% - 14px)'
				},
				'.card-type-icon.elo': {
					top: 'calc(50% - 14px)'
				}
			});

			// Mount the card fields component in the desired HTML tag. This is where the iframe will be located.
			self.component.mount('#input_' + self.formId + '_' + self.ccFieldId + '_1');
			$('#input_' + self.formId + '_' + self.ccFieldId + '_5_container').css( 'display', '' );
		}

		/**
		 * @function isConversationalForm
		 * @description Determines if we are on conversational form mode
		 *
		 * @since 1.4.0
		 *
		 * @returns {boolean}
		 */
		self.isConversationalForm = function () {
			return typeof gfcf_theme_config !== 'undefined' ? ( gfcf_theme_config !== null && typeof gfcf_theme_config.data !== 'undefined' ? gfcf_theme_config.data.is_conversational_form : undefined ) : false;
		}

		self.initPaymentForm = function() {
			if ( $( '#field_' + self.formId + '_' + self.ccFieldId ).is( ':visible' ) || this.isConversationalForm() ) {
				self.buildPaymentForm();
			} else {
				gform.addAction( 'gform_post_conditional_logic_field_action', function( formId, action, targetId ) {
					if ( action === 'show' && '#field_' + self.formId + '_' + self.ccFieldId === targetId ) {
						self.buildPaymentForm();
					}
				} );
			}
		}

		self.resetPaymentForm = function () {
			self.jsPaymentClient = null;
			$( '#input_' + self.formId + '_' + self.ccFieldId + '_1' ).html('');
			$('#input_' + self.formId + '_' + self.ccFieldId + '_5_container').hide();
		}

		/**
		 * Resets form status when errors are received after trying to submit.
		 *
		 * @param form form object
		 * @param formId
		 * @param isLastPage
		 */
		this.resetFormStatus = function (form, formId, isLastPage) {
			// Reset form status.
			form.data( 'gf_2checkout_submitting', false );
			// Remove spinner.
			$( '#gform_ajax_spinner_' + formId ).remove();
			// must do this or the form cannot be submitted again.
			if (isLastPage) {
				window["gf_submitting_" + formId] = false;
			}
		}

		// # HELPER METHODS ------------------------------------------------------------------------------------------------

		/**
		 * Get the current page number.
		 *
		 * @since 1.0
		 *
		 * @return int|bool
		 */
		self.getCurrentPageNumber = function() {

			var currentPageInput = $( '#gform_source_page_number_' + self.formId );

			return currentPageInput.length > 0 ? currentPageInput.val() : false;

		};

		/**
		 * Determine if the credit card field is on this page.
		 *
		 * @since 1.0
		 *
		 * @uses GF2Checkout.getCurrentPageNumber()
		 *
		 * @return bool
		 */
		self.isCreditCardOnPage = function() {

			var currentPage = self.getCurrentPageNumber();

			// If current page is false or no credit card page number, assume this is not a multi-page form.
			if ( ! self.ccPage || ! currentPage || this.isConversationalForm()) {
				return true;
			}

			return this.ccPage == currentPage;

		};

		/**
		 * Determine if this is the last page of the form.
		 *
		 * @since 1.0
		 *
		 * @return bool
		 */
		self.isLastPage = function() {

			var targetPageInput = $( '#gform_target_page_number_' + self.formId );

			if ( targetPageInput.length > 0 ) {
				return targetPageInput.val() == 0;
			}

			return true;

		};

		/**
		 * Add spinner to form on submit.
		 *
		 * @since 1.0
		 */
		self.maybeAddSpinner = function() {

			if ( self.isAjax ) {
				return;
			}

			if ( 'function' === typeof gformAddSpinner) {
				gformAddSpinner( self.formId );
				return;
			}

			if ( $( '#gform_ajax_spinner_' + self.formId ).length == 0 ) {

				var spinnerUrl     = gform.applyFilters( 'gform_spinner_url', gf_global.spinnerUrl, self.formId ),
					$spinnerTarget = gform.applyFilters( 'gform_spinner_target_elem', $( '#gform_submit_button_' + self.formId + ', #gform_wrapper_' + self.formId + ' .gform_next_button, #gform_send_resume_link_button_' + self.formId ), self.formId );

				$spinnerTarget.after( '<img id="gform_ajax_spinner_' + self.formId + '"  class="gform_ajax_spinner" src="' + spinnerUrl + '" alt="" />' );

			}

		};

		self.init();

	}

} )( jQuery );

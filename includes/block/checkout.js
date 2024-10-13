const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting }            = window.wc.wcSettings;
const settings                  = getSetting( 'nebulox_gateway_payments_data' );
const label                     = window.wp.htmlEntities.decodeEntities( settings.title || window.wp.i18n.__( '' ) );

const Content = () => {
	return window.wp.htmlEntities.decodeEntities( settings.description || '' )
};

const Nebulox_Block = {
	name: 'nebulox_gateway',
	label: label,
	content: Object( window.wp.element.createElement )( Content , null ),
	edit: Object( window.wp.element.createElement )( Content , null ),
	canMakePayment: () => true,
	ariaLabel: label,
	placeOrderButtonLabel: 'Pay with Nebulox',
	icon: settings.icon,
};

registerPaymentMethod( Nebulox_Block );

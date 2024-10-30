function lbx_load_gateway_frontend(gateway_id) {
    const settings = window.wc.wcSettings.getSetting(`${gateway_id}_data`, null);
    console.log(gateway_id, settings)
    if (!settings) {
        // Allows the same script to be reused across gateways
        return;
    }
    const LbxContent = () => {
        return window.wp.htmlEntities.decodeEntities(settings.description || "");
    };
    window.wc.wcBlocksRegistry.registerPaymentMethod({
        name: settings.id,
        label: settings.title,
        content: Object(window.wp.element.createElement)(LbxContent, null),
        edit: Object(window.wp.element.createElement)(LbxContent, null),
        canMakePayment: () => true,
        ariaLabel: settings.title,
        supports: {
            features: settings.supports,
        },
    });
}

lbx_load_gateway_frontend("lenbox_floa_cbnx");

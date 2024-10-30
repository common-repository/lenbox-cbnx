=== Lenbox CBNX ===
Contributors: vazlenbox
Tags: 
Requires at least: 5.6
Tested up to: 6.5.5
Stable tag: 3.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin is meant to be a simple interface to the payment gateway managed by Lenbox for the Woocommerce ecosystem. It supports product payments interface provided by Woocommerce.


== Description ==

This payment gateway plugin is for lenbox's clients who use the Woocommerce backend. 

In order to use the plugin, the user needs credentials provided by lenbox which are available on subscription at [Lenbox.io](https://www.lenbox.io).

When the payment gateway is active, the end user will be redirected to lenbox's payment page where they will have to fill the appropriate forms to request an EMI from Lenbox's bank partner(s).

== Frequently Asked Questions ==

= The order table page is not loading. =

Please verify if you have the credentials entered on the Woocommerce payments page. While the order table is on the external admin menu, it still uses the credentials configured on the Woocommerce admin to retrieve data from lenbox.

= How to refresh order statuses ? =

In the order list table, you will find the a custom admin option "Refresh Status for Lenbox Orders". Select the orders you want to refresh and run the command. The system will skip any orders that are not associated with this module automatically.

= How to add additonal product data ? =

At the moment, the plugin fetches only the data required for mandatory fields required by lenbox's API; In order to fill the additional fields, please use Woocommerce's hooks as shown in the example below.

```
add_action( 'woocommerce_checkout_update_order_meta', 'before_checkout_create_order', 20, 2 );
	function before_checkout_create_order( $order_id, $values ) {
		$product_details = array(
			'modele'      => 'text modele',
			'marque'      => 'Peugeot',
			'image'       => "lien d'image",
			'kilometrage' => 2345.42,
			'pfisc'       => 2345.42,
		);
		update_post_meta( $order_id, 'product_details', $product_details );
	}
```


== Changelog ==


= 3.3.1 =
* Typo in the settings

= 3.3.0 =
* Split out the CNX into separate optionsets 

= 3.2.0 =
* Add support for blocks

= 3.1.2 =
* Switch to French as default language. 

= 3.1.1 =
* Use eligibilities API to validate the client options on the cart

= 3.1.0 =
* Clients can choose the type of CBNX directly from the cart

= 3.0.0 =
* Added payment gateway for Card payments
* Added 12NX for CBNX Gateway

= 2.5.0 =
* Sandbox mode : "Use test" targets sandbox instead of test environment
* Hide Lenbox Payment Gateway if no products are applicable

= 2.4.3 =
* Bugfix : Regresseion when trying to support variable product eligibilities

= 2.4.2 =
* Update bad range condition

= 2.4.1 =
* Update defaults to not break extension on automatic update

= 2.4.0 =
* Set price range per cart

= 2.3.0 =
* Store the references for all the payment tentatives (fixes the bug when a previous attempt succeeded)

= 2.2.0 =
* Added support for fetching eligibility in product page

= 2.1.1 =
* Removed pulling customer details from billing data due unpredictable customer environments / setup 

= 2.1.0 =
* Updated webhook response for cleaner plugin integration

= 2.0.0 =
* Update order API to use enum list for payment options

= 1.3.2 =
* Use Woocommerce's loggers to track changes
* Use exact match for "lenbox_ref"

= 1.3.0 =
* Added a custom metadata field "lenbox_ref" for each order to track the unique order reference for each payment attempt.
* Added a custom admin action to fetch statuses from lenbox based on "lenbox_ref".
* Existing orders will use Order ID as "lenbox_ref" for backwards compatibility.

= 1.2.2 =
* Update json parsing when fetching form_status
* Update generation of product ID to track multiple requests for the same order

= 1.2.0 =
* Update API to prepopulate name and email from billing details

= 1.1.0 =
* Fixed bug in status update webhook.

= 1.0.0 =
* Plugin supports Woocommerce payments
* Redirects to lenbox's external payment page when user pays order via lenbox
* Payment status is updated via pre-configured webhook that will be triggered asynchrnously by lenbox



== Upgrade Notice ==

= 2.0.0 =
Complete internal refactor of payment configuration. Please verify your configuration. 

= 1.3.0 =
Added bulk order update for synchronisation based on reference meta field.

= 1.0.0 =
First version. Nothing to upgrade

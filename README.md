# straal-Prestashop
Payment plugin for Prestashop


---

[![License: Apache 2.0](https://img.shields.io/badge/License-Apache%202.0-green.svg?style=flat)](LICENSE)
[![Twitter](https://img.shields.io/badge/twitter-@straal-blue.svg?style=flat)](http://twitter.com/straal_)

# Straal Prestashop payment plugin

> A Prestashop plugin for integrating your store with Straal payment gateway.

## Description

This plugin allows you to accept credit card and pay-by-link payments on your Prestashop enabled online store. It takes advantage of Straal Checkout – a hosted payment page provided by Straal. We take care of all the technical and regulatory details, so that you can focus on running your business.


## Before you active the plugin

Make sure:
* your company has signed an agreement with Straal and your account manager confirmed that you can start processing payments. [Reach out to our sales team](https://straal.com/) and learn how Straal can help grow your ecommerce business.
* you have access to a working API Key – it will be necessary to authenticate the plugin in Straal Gateway. You can generate it in [Straal Kompas](https://kompas.straal.com).  


## Key features

* Accept credit card payments. 
* Accept pay-by-link payments (bank transfers). 
* Customize how the payment method is displayed (title, description).
* Test payments with sandbox mode.
* Receive automatic order status updates when customer finalises the payment.
* Debug technical issues with automatic logging.

## Payment flow from the customer perspective

1. Your customer starts the checkout process and fills out all the necessary details to fulfill the order and chooses "Straal" as the payment option (you can change this name in the plugin settings).
2. When the customer clicks on the "Pay" button, Straal Prestashop plugin automatically redirects him to Straal Checkout page. 
3. The customer fills in payment details (such as credit card number) in a secure, PCI certified environment and completes his payment.
4. Straal Gateway processes the payment and displays payment confirmation page to the customer.
5. Finally, the customer is redirected back to your website where he can see the order confirmation page.

## Plugin installation

1. Download this repository as .zip file.
2. In your PrestaShop back office, go to:
- Modules > Module Manager if you are using version 1.7 of PrestaShop.
- Modules and Services if you are using version 1.6 of PrestaShop.
3. Select Upload a module, and select the .zip file that you downloaded on your computer.
4. Go to Payment > Payment methods.
5. Find Straal, and select:
- Enable Module if you are using version 1.7 of PrestaShop.
- Install if you are using version 1.6 of PrestaShop.

The plugin is ready to configure and test.


## Notifications

Notifications from Straal allow for automatic payment status updates for your orders. We highly recommend configuring them. 

1. Go to [Straal Kompas](https://kompas.straal.com) and log in (if you don't have access contact our [support team](mailto:support@straal.com)
2. Navigate to "Notification endpoints".
3. Click on "Create".
4. Enter URL that is provided in Straal Prestashop plugin settings.
5. Enter username and password you wish to use and create the endpoint.
6. Go back to your website and enter the same username and password in Straal Straal plugin settings and save changes.

That's it, you should now receive order status updates.

## Testing

We provide every customer with a test account to test the integration before accepting live transactions. You can enable test mode for Straal Prestashop plugin in the settings by checking the "Enable Straal sandbox mode". Once enabled, all payments will be processed using the Sandbox API key that should be generated for the test account provided by Straal. 

## Support

Any suggestions or reports of technical issues are welcome! Contact us via [email](mailto:itsupport@straal.com).

## License

This library is released under Apache License 2.0. See [LICENSE](LICENSE) for more info.


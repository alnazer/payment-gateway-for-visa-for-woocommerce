## Payment Gateway for VISA
Contributors: alnazer
Tags: VISA, payment, kuwait, woocommerce , ecommerce, payment ,gateway
Author: alnazer
Tested up to: 6.0.0
Tested in WooCommerce : 6.8.0
Requires PHP: 7.0
Stable tag: 1.2.0
License: MIT
License URI: https://choosealicense.com/licenses/mit/

## Description

نساعدك في تطوير اعمالك الخاصه بتقديم الاضافة الجديد
الخاصة بالدفع عن طريق بوابة ألفيزا بعد تحديثها
وسع دائرة عملائك باتاحة امكانية الدفع عن طريق ألفيزا

We help you to develop your business by introducing the new add-on
For payment through the VISA portal, after it has been updated
Expand your customers' circle by making the payment available via VISA

## important note
To activate 3-D Secure Authentication
select 3-D Secure Authentication from the Payer Authentication dropdown in the Admin > Integration Settings page of the Merchant Administration user interface.

## Installation

download and unzip to plugins folder
<br/>
or
From merchant’s WordPress admin

1. Go to plugin section-> Add new
2. Search for “Payment Gateway for VISA”
3. Click on Install Now
4. Click on Activate

## TEST CARD

[https://ap.gateway.mastercard.com/api/documentation/integrationGuidelines/supportedFeatures/testAndGoLive.html?locale=en_US](https://ap.gateway.mastercard.com/api/documentation/integrationGuidelines/supportedFeatures/testAndGoLive.html?locale=en_US)

## Usage

go to woocommerce setting in side menu and select tab payment and active VISA from list
<br/>
You can add custom style for redirect page in redirect-page.html
1-Don't change any javascript code
2-Don't make javascript code mistake it will stoped redirection
3- Don't forget backup from page

## Changelog

=== 1.3.0 ===

1. add function to get order information
2. upgrade api version to 66
3. make some change in jhavascript configration in page redirect-page
4. remove input interaction and set interaction opertation by default PURCHASE
5. support 3DS2
=== 1.2.0 ===

1. add commission
2. re order input fileds 
3. Change input password type from text to password
4. Display commission to payment method description 

=== 1.1.3 ===

1. fixed call some classes error

=== 1.1.2 ===

1. fixed invoice descreption long length
2. remove not kwd curnancy note
3. add complete order status

=== 1.1.1 ===

4. connect with last API version 61
5. add select field for Interaction type in VISA setting fields

## License

[GPLv3](https://choosealicense.com/licenses/agpl-3.0/)

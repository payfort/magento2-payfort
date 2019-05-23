magento2-Payfort_Fort
======================

Payfort payment gateway Magento2 extension. Payfort is the Most Trusted Online Payment Gateway in the Middle East
PAYFORT is here to help you accept online payments, reduce fraud, and maximize your revenue


## Install

1. Go to [Magento2 root folder]/app/code

2. Past Payfort folder to the previous path

3. Go to [Magento2 root folder] & Enter following commands to enable module:

    ```bash
    php bin/magento module:enable Payfort_Fort --clear-static-content
    php bin/magento setup:upgrade
    php bin/magento setup:static-content:deploy

    ```
4. Enable and configure Payfort in Magento Admin under Stores -> Configuration -> Sale -> Payment Methods -> PayFort Fort Payment Methods

## Upgrade

1. Go to [Magento2 root folder]/app/code.

2. Past Payfort folder to the previous path.

3. Go to Magento Admin Panel -> System -> Cache Management.

4. Click on Flush Javascript/Css Cache.

5. Click on Flush Static Files Cache.

6. Click On Flush Magento Cache.

## Changelog

`v1.4.1`
- Support magenta 2.3

`v1.3.1`
- Fix order confirmation email is not sent issue

`v1.3.0`
- Added Installments as payment method

`v1.2.1`
- Generate invoice.
- Capture order amount.

`v1.2.0`
- Added option for Gateway currency.
- Added merchant page 2.0
- Fixing some bugs.

## Compatibility


Tested with [One Step Checkout]

## Why Payfort?


### Hassle-free setup


There are a lot of moving parts when it comes to setting up a payment gateway. From applying for a merchant ID to integrating your website with a payment service provider, it can be a lot to manage for a busy business owner. Stop worrying and let us walk you through everything step-by-step.


### Protect your online business against fraud

Credit card fraud can cost a business millions if not managed properly. With some powerful fraud prevention tools, our risk management team will secure you against online fraud, and keep your business safe.



### Our relationship with you doesn’t end after integration

We strongly believe in helping our customers grow their online businesses. Our relationship managers will keep a close eye on your financial performance, and work towards increasing your acceptance ratio and maximizing your revenue.


### Let your customer checkout with any credit card, from anywhere in the world

Give your customers the online shopping experience they deserve with simple and friendly checkout options. Whether they are using Visa or MasterCard credit cards, or checking out from different parts of the world, you’ll be able to sell globally to over 80 countries.


### Are your customers not interested in paying with a credit card?
We’ve got you covered.

Give your customers different payment options and allow them to pay offline with, Sadad – KSA, Edirham – UAE.

### Integrate faster

With over 50 supported shopping cart integrations, our plug and play developer kit makes it easy to quickly integrate your website. Our Integration team is on hand to take you from production to live and get you to start transacting online.

### It’s hard for a startup to succeed.
We’ve got your back

A startup’s success hinges on its ability to acquire customers. PayFort’s Startup Program lets you take off the ground quickly with low pricing and amazing benefits.


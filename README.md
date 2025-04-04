# Nano Split POS - WordPress Plugin

A Point of Sale system for Nano cryptocurrency with payment splitting capabilities.

## Description

Nano Split POS is a WordPress plugin that allows businesses to accept Nano cryptocurrency payments and automatically split the payments between multiple addresses. This is useful for automatically handling tax payments, supplier payments, wages, and more.

## Features

- Accept Nano cryptocurrency payments
- Split payments automatically between multiple addresses
- Allow employees to receive tips directly to their Nano address
- Customizable tip percentages
- Integration with WordPress products (optional)
- Simple and intuitive point of sale interface
- Payment history tracking
- QR code generation for easy payments

## Installation

1. Upload the `nano-split-pos` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your API key and payment addresses in the plugin settings

## Usage

### Admin Settings

1. Go to "Nano Split POS" in the WordPress admin menu
2. Enter your Nano Split API key
3. Configure tip settings if desired
4. Enable product integration if desired

### Payment Addresses

1. Go to "Payment Addresses" under the "Nano Split POS" menu
2. Add addresses with nicknames and percentage splits
3. The total percentage across all active addresses should equal 100%

### Point of Sale

Use the shortcode `[nano_split_pos]` on any page to display the point of sale interface, or visit the dedicated POS page at `/nano-pos/`.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Nano Split API key

## License

This plugin is licensed under the GPL v2 or later. 
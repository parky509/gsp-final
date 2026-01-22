# GlobalSwiftPay Dashboard - WordPress Plugin

A professional investment dashboard plugin for WordPress with glass morphism design, perfect for investment and financial websites.

## Features

### User Dashboard
- **Modern Glass Morphism Design**: Beautiful, professional UI with crystal blue theme
- **User Profile Section**: Displays user avatar and username in real-time
- **Wallet Balance**: Real-time balance display with sleek cards
- **Savings Section**: View savings balance
- **Quick Actions**: Easy access to all financial operations

### Financial Operations

#### Deposit
- Clean, sleek form with required fields:
  - Full Name
  - Email Address
  - Deposit Amount
- Admin approval required

#### Add Balance
- Transfer to platform account with receipt upload
- Shows bank details (Account Number, Account Name, Bank Name)
- Upload receipt in PDF/PNG/JPEG format
- Admin approval required before balance is credited

#### Withdraw
- Withdraw funds from wallet
- Multiple withdrawal methods:
  - Bank Transfer
  - Bitcoin (BTC)
  - Tether (USDT)
- Admin approval required

#### Transfer
- Transfer funds to other users on the platform
- Requires Token Code for verification
- Admin approval required

#### Convert to BTC
- Convert wallet balance to Bitcoin
- Required fields:
  - Email
  - Amount to Convert
  - Bitcoin Address
  - Security Phrase

#### Convert to USDT
- Convert wallet balance to Tether (USDT)
- Required fields:
  - Email
  - Amount to Convert
  - USDT Address
  - Security Phrase

#### Convert to Bank
- Convert wallet balance to bank transfer
- Required fields:
  - Email
  - Amount in Dollars
  - Bank Name
  - Account Name
  - Bank Account Number
  - SWIFT/IFSC/Routing No/Sort Code/IBAN
  - Bank Address
  - Country
  - Security Phrase

### Transaction History
- Real-time transaction history display
- Status indicators (Pending, Approved, Declined)
- Transaction type badges with color coding

### Admin Dashboard

#### Features
- Overview of pending requests
- Manage Deposits
- Manage Withdrawals
- Manage Transfers
- Manage Conversions
- User Balance Management
- Platform Settings

#### Settings
- Update Bank Account Details
- Update BTC Address
- Update USDT Address
- Update Company Email

### Email Notifications
- Automatic email notifications for:
  - Deposit status updates
  - Withdrawal status updates
  - Transfer status updates
  - Conversion status updates
- Professional HTML email templates

### Security
- AJAX nonce verification
- User capability checks
- Input sanitization
- SQL injection prevention

## Installation

1. Download the plugin folder
2. Upload to `/wp-content/plugins/` directory
3. Activate the plugin through WordPress admin
4. A dashboard page will be created automatically at `/gsp-dashboard/`

## Shortcode

Use the shortcode `[gsp_dashboard]` on any page to display the dashboard.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Logout Behavior

When users log out, they are automatically redirected to `globalswiftpay2.com`.

## Customization

### Colors
The plugin uses CSS custom properties for easy color customization. Main colors:
- Primary: `#0096ff` (Crystal Blue)
- Primary Light: `#00d4ff`
- Background: White with blue gradient
- Success: `#00c853`
- Warning: `#ffc107`
- Danger: `#ff5252`

### Glass Morphism
All cards and modals feature glass morphism effects with:
- Blurred backgrounds
- Semi-transparent overlays
- Smooth border radius
- Subtle shadows

## Support

For support, contact: support@globalswiftpay2.com

## License

GPL v2 or later

=== SMS Payment Checker ===
Contributors: xmanstudio
Tags: woocommerce, payment, bank transfer, sms, verification
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatic bank transfer verification via SMS for WooCommerce. Works with SmsChecker Android app.

== Description ==

SMS Payment Checker automates bank transfer verification by reading SMS notifications from your phone and matching them with WooCommerce orders.

= Features =

* **Automatic Payment Matching** - Matches incoming SMS bank notifications with pending orders
* **Unique Amount Generation** - Generates unique payment amounts (with satang variations) for easy matching
* **Real-time Updates** - Pusher WebSocket integration for instant payment notifications
* **Push Notifications** - Firebase Cloud Messaging (FCM) for Android app notifications
* **Multi-device Support** - Connect multiple Android devices
* **Secure Communication** - AES-256-GCM encryption and HMAC signature verification
* **15 Bank Support** - Works with major Thai banks

= Supported Banks =

* Kasikorn Bank (KBANK)
* Siam Commercial Bank (SCB)
* Krungthai Bank (KTB)
* Bangkok Bank (BBL)
* Government Savings Bank (GSB)
* Bank of Ayudhya (BAY)
* TMBThanachart Bank (TTB)
* PromptPay
* CIMB Thai
* Kiatnakin Phatra Bank (KKP)
* Land and Houses Bank (LH)
* TISCO Bank
* United Overseas Bank (UOB)
* ICBC Thai
* Bank for Agriculture (BAAC)

= Requirements =

* WordPress 5.8+
* WooCommerce 6.0+
* PHP 8.0+
* SmsChecker Android app (for reading SMS)
* HTTPS enabled (required for secure communication)

= How It Works =

1. Customer places order and selects bank transfer payment
2. Plugin generates a unique payment amount (e.g., 1,000.17 instead of 1,000)
3. Customer transfers the exact amount
4. Android app reads the bank SMS notification
5. Plugin matches the amount with the pending order
6. Order is automatically marked as paid

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/sms-payment-checker/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to SMS Checker menu in admin to configure
4. Create a device and scan QR code with Android app
5. Configure Pusher and FCM for real-time notifications (optional)

== Configuration ==

= Setting Up Devices =

1. Navigate to SMS Checker > Devices
2. Click "Add Device"
3. Enter device name and select approval mode
4. Scan the QR code with SmsChecker Android app

= Pusher Setup (Optional - for real-time updates) =

1. Create account at pusher.com
2. Create new Channels app
3. Go to SMS Checker > Settings > Pusher
4. Enter App ID, Key, Secret, and Cluster

= Firebase FCM Setup (Optional - for push notifications) =

1. Go to Firebase Console
2. Create or select project
3. Go to Project Settings > Service Accounts
4. Generate new private key
5. Paste JSON in SMS Checker > Settings > Firebase FCM

== Frequently Asked Questions ==

= Does this require the Android app? =

Yes, you need the SmsChecker Android app to read SMS notifications from your phone.

= Is it secure? =

Yes, all communication is encrypted with AES-256-GCM and verified with HMAC signatures. API keys are transmitted only once via QR code.

= Can I use multiple phones? =

Yes, you can connect multiple Android devices. Each device has its own API credentials.

= What happens if the amount doesn't match? =

Unmatched payments are stored as "orphan transactions" and can be manually matched later.

== Screenshots ==

1. Dashboard showing payment statistics
2. Device management screen with QR code
3. Notifications history
4. Settings page

== Changelog ==

= 1.6.0 =
* Added Pusher WebSocket support for real-time updates
* Added Firebase FCM push notifications
* Added REST API sync endpoints
* Added WooCommerce payment gateway
* Improved admin interface
* Added 15 bank support

= 1.5.0 =
* Initial release

== Upgrade Notice ==

= 1.6.0 =
Major update with real-time features. Configure Pusher and FCM for best experience.

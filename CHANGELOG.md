# DOCKontrol2 changelog

This version is complete rewrite of the first version, now based on Symfony framework and using Redis for
camera/queue acceleration and Meilisearch for faster log searches. It is impossible to list all the changes, so here is
at least some highlight:

## Frontend

* 🎨 **New Theme:**  Enjoy a completely redesigned frontend with a modern Bootstrap-based theme, supporting both light and dark modes. New icons enhance the user experience.
* 🔑 **Email Login:** Login is now streamlined and more secure with email addresses instead of usernames. Usernames have been removed from the system.
* 🚀 **Faster Cameras:** Significantly improved camera stream performance and stability.
* 📹 **Independent Camera Streams:** Individual cameras now load in independent streams, leading to faster loading times, especially when multiple cameras are available.
* 🌍 **Multi-language Support:**  DOCKontrol2 now speaks your language! Added Czech translation alongside English for a wider user base.
* 📝 **Custom Guest Pass Notes:**  Add personalized notes to guest passes for better communication with your visitors.
* 🌐 **Guest Language Preference:** Guests can now receive help information in their preferred language (English or Czech) for easier access.
* 🚗 **Customizable Car Buttons:** Users can now customize the visibility of CAR enter/exit buttons based on their individual needs.
* ✉️ **Email Change with Verification:**  Change your registered email address securely with a verification process for both old and new emails.
* 👥 **Apartment User Visibility:** See who else is connected to your apartment for improved transparency and management.
* 🧑‍🤝‍🧑 **Tenant Control Panel:** Landlords can now create and manage tenant accounts, offering a simplified way to grant access for renters. Tenants have similar functionalities to landlords but cannot create sub-accounts.
* ✨ **Dashboard Customization:**  Personalize your dashboard with full customization options! Organize groups, rename buttons, sort items, adjust column layouts in sections, and even change button colors to create your perfect control interface.
* 🛡️ **GDPR Compliance:**  We take your privacy seriously. You can now download a comprehensive report of your data and access logs. Account deletion requests are also available for permanent removal from the system.
* 📜 **Terms of Service & Privacy Policy:**  Detailed Terms of Service and Privacy Policy are now available, outlining our data management practices. Acceptance is required for all users to ensure transparency and user rights.

## Backend

* 📧 **Email Server Integration:** Implemented a dedicated email server for user email verification and sending important announcements directly from DOCKontrol.

### Admin Backend
* 🚀 **Introducing Admin Control Panel:** A brand new Admin Control Panel is now available to manage your DOCKontrol system.
* 🧑‍💼 **Super Admin Features:** Super admins have full control over the system and can manage:
    * 🏢 Buildings
    * 🔑 API Keys
    * 👥 Groups
    * 🎫 Guests
    * 🔒 Permissions
    * ✉️ Signup Codes
    * 👤 Users
    * ✅ GDPR User Deletion Requests
    * ⚙️ Actions & Cron Groups
    * 🏘️ Apartments
    * 🚪 Buttons
    * 📹 Cameras
    * 🔧 Configuration Options
    * ✉️ Email Server Settings
    * 🖥️ DOCKontrol Nodes
    * 📊 System Logs
* 🏢 **Admin Features (Building Specific):** Admins can manage users within their assigned buildings and:
    * 👤 Manage Users within assigned buildings
    * 🔗 Create Invitation Links for new users within their buildings

## API

* ⚙️ **Legacy API Compatibility:** The legacy API endpoint `/api.php` remains functional and now supports POST requests in addition to GET. Login now uses email instead of username.
* 🔐 **New API2 Endpoint with API Keys:** Introducing a new and enhanced API endpoint `/api2` that utilizes API keys for improved security and replay protection.

### Backward compatibility
Please note that due to the significant rewrite and changes like username removal, this version is not backward compatible with the first version of DOCKontrol on API level. Ensure you are using the updated DOCKontrol2 mobile application.

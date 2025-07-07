# Slot Management System

A comprehensive system for managing slot machines in casino environments, tracking transactions, and generating performance reports.

## Features

-   **User Management**: User authentication with three role levels (Administrator, Editor, Viewer).
-   **Slot Machine Management**: Comprehensive inventory management for slot machines, including brands, types, and groups.
-   **Transaction Tracking**: Detailed tracking system for various transaction types like Handpay, Ticket, Refill, Coins Drop, and Cash Drop.
    *   Improved transaction creation and editing forms, allowing selection of active and maintenance machines.
    *   Records and displays the user who last edited a transaction.
-   **Daily & Weekly Performance Tracking**: Dedicated modules for recording and viewing daily and aggregated weekly performance data.
    *   Enhanced daily tracking summary with detailed breakdowns of Slots, Gambee, and Coins performance (Drop, Out, Result, and %) directly within the main statistics cards.
-   **Meter Management**:
    *   Manual meter entry for offline machines, now including inactive machines.
    *   Comprehensive CSV upload for online machine meter data, supporting all meter fields and intelligent type detection.
    *   Detailed meter entry views with variance and anomaly calculations.
-   **Guest Tracking**: Functionality to upload and track guest data from Excel/CSV files, including drop, result, and visits.
-   **Reporting**:
    *   **General Report**: Overview of machine type statistics with drop, out, and result data.
    *   **Detailed Reports**: Comprehensive reports with filtering options for machine, brand, and groups.
    *   **Custom Report**: Flexible report generation with selectable columns and filters.
-   **Logging**:
    *   **Action Logs**: Records user actions within the system for audit trails.
    *   **Security Logs**: Logs security-related events and suspicious activities.
-   **Operation Day Management**: Administrators can set and manage the current operation day, affecting new transaction entries.
-   **Historical Data Import**: Ability to import historical transaction data from CSV files.
-   **Data Export**: Export reports and data to PDF and Excel formats.
-   **Intuitive Interface**: Dynamic filtering, sorting, and pagination for easy data navigation.

## Requirements

-   PHP 8.0 or higher
-   MySQL 8.0 or higher
-   Web server (Apache/Nginx)
-   WAMP (Windows) or LAMPP (Linux) environment

## Installation

1.  Clone or download this repository to your web server directory.
2.  Create a MySQL database named `slotapp`.
3.  Navigate to `install.php` in your web browser (e.g., `http://localhost/slotapp/install.php`).
4.  Follow the on-screen instructions to set up the database tables and create your initial Administrator user.
5.  After successful installation, you will be redirected to the login page.

## User Roles

-   **Administrator**: Full access to all system features, settings, and user management.
-   **Editor**: Can create, edit, and delete most data (e.g., machines, transactions, daily tracking), but has restricted access to system settings and user management.
-   **Viewer**: View-only access to data and reports.

## File Structure

-   `/assets` - CSS, JavaScript, and image files (including icons).
-   `/backups` - Scripts for database backups.
-   `/config` - Application configuration and security settings.
-   `/includes` - Common PHP functions, header, and footer.
-   `/pages` - Core application pages (e.g., dashboard, transactions, reports).
    -   `/pages/brands` - Brand management pages.
    -   `/pages/custom_report` - Custom report generation and export.
    -   `/pages/daily_tracking` - Daily tracking management.
    -   `/pages/guest_tracking` - Guest tracking and data upload.
    -   `/pages/import_transactions` - Historical transaction import.
    -   `/pages/machine_groups` - Machine group management.
    -   `/pages/machine_types` - Machine type management.
    -   `/pages/machines` - Slot machine management.
    -   `/pages/operation_day` - Operation day settings.
    -   `/pages/profile` - User profile management.
    -   `/pages/reports` - Detailed reports and export.
    -   `/pages/transactions` - Transaction management.
    -   `/pages/users` - User account management.
-   `/install.php` - Initial system setup script.
-   `/index.php` - Main application entry point.
-   `/login.php` - User login page.
-   `/logout.php` - User logout script.
-   `security_guide.md` - Detailed documentation on security implementations and recommendations.

## Security Considerations

This system incorporates several security measures, including:
-   Secure session management with periodic ID regeneration and timeouts.
-   CSRF protection for all forms.
-   Input validation and sanitization to prevent XSS and SQL Injection.
-   Use of prepared statements for all database interactions.
-   Implementation of various security headers.
-   Logging of security events and failed login attempts.

**Important**: For detailed information on security implementations and further recommendations, please refer to the `security_guide.md` file. Always ensure your server environment is also securely configured.

## Support

For questions or support, please contact Raf [rafiksemaan@gmail.com](mailto:rafiksemaan@gmail.com).

## License

This project is licensed under the MIT License - see the LICENSE file for details.

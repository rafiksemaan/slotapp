# Slot Management System

A comprehensive system for managing slot machines in casino environments, tracking transactions, and generating performance reports.

## Features

- User authentication with three role levels (admin, editor, viewer)
- Comprehensive slot machine inventory management
- Transaction tracking system for handpays, tickets, refills, and drops
- Dynamic reporting with filterable results
- Real-time performance metrics with auto-refresh capability

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- WAMP (Windows) or LAMPP (Linux) environment

## Installation

1. Clone or download this repository to your web server directory
2. Create a MySQL database named `slotapp`
3. Import the database schema using the `setup.sql` file:
   ```
   mysql -u root -p slotapp < setup.sql
   ```
4. Navigate to the application in your web browser
5. Log in with the default credentials:
   - **Admin**: username: `admin`, password: `admin123`
   - **Editor**: username: `editor`, password: `editor123`
   - **Viewer**: username: `viewer`, password: `viewer123`

## User Roles

- **Admin**: Full access to all system features and settings
- **Editor**: Full functionality except for settings management
- **Viewer**: View-only access with report generation capabilities

## File Structure

- `/assets` - CSS, JavaScript, and image files
- `/config` - Configuration files
- `/includes` - Common PHP files (header, footer, functions)
- `/pages` - Page content files
- `/install.php` - Installation script
- `/index.php` - Main entry point

## Security Considerations

- Change the default passwords immediately after installation
- Secure your MySQL database
- Use HTTPS in production environments

## Support

For questions or support, please contact [your-email@example.com](mailto:your-email@example.com).

## License

This project is licensed under the MIT License - see the LICENSE file for details.
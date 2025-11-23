# CPMS - College Placement Management System

A comprehensive web-based platform for managing college internship placements, connecting students, teachers, and administrators in a streamlined internship management workflow.

## 🚀 Features

### For Students

- **Profile Management**: Create and manage personal profiles
- **Internship Search**: Browse and search for available internship opportunities
- **Application Tracking**: Submit and track internship applications
- **Status Updates**: Real-time application status notifications

### For Teachers

- **Student Supervision**: Monitor student internship progress
- **Application Review**: Approve/reject student applications
- **Progress Tracking**: Track student internship completion
- **Report Generation**: Generate progress reports

### For Administrators

- **System Management**: Complete system oversight
- **User Management**: Manage all user accounts and roles
- **Internship Management**: Add/edit internship opportunities
- **Analytics Dashboard**: View system statistics and reports

## 🛠️ Technology Stack

### Current Implementation

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Authentication**: Session-based authentication

### Modern Alternative (Recommended)

- **Frontend**: React + TypeScript + TailwindCSS
- **Backend**: Next.js API Routes
- **Database**: Supabase (PostgreSQL)
- **Deployment**: Vercel
- **Authentication**: Supabase Auth

## 📋 Prerequisites

### For Current PHP Version

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/NginX) or PHP built-in server
- Modern web browser

### For Modern Version

- Node.js 16+
- npm or yarn
- Supabase account (free tier available)
- Vercel account (free tier available)

## 🚀 Quick Start

### Option 1: Current PHP Version

1. **Clone/Download the project**

   ```bash
   git clone [repository-url]
   cd "cip pro"
   ```
2. **Database Setup**

   - Import `cipms/database/cipmsdb.sql` into your MySQL
   - Update database credentials in `cipms/includes/config.php`
3. **Start the Server**

   ```powershell
   # Using PowerShell (Recommended)
   .\auto-run.ps1

   # Or using Batch File
   start-server.bat

   # Or Manual
   cd cipms
   php -S localhost:8000
   ```
4. **Access the Application**

   - Open browser: http://localhost:8000
   - Login page: http://localhost:8000/login.php

### Option 2: Modern Version (Recommended)

1. **Setup Supabase**

   - Create free account at https://supabase.com
   - Create new project
   - Run SQL schema from database folder
2. **Setup Next.js Project**

   ```bash
   npx create-next-app@latest cipms-modern
   cd cipms-modern
   npm install @supabase/supabase-js tailwindcss
   ```
3. **Configure Environment**

   ```env
   NEXT_PUBLIC_SUPABASE_URL=your_supabase_url
   NEXT_PUBLIC_SUPABASE_ANON_KEY=your_supabase_key
   ```
4. **Run Development Server**

   ```bash
   npm run dev
   ```

## 📁 Project Structure

```
cip pro/
├── README.md                 # This file
├── README-AUTO-RUN.md        # Auto-run instructions
├── auto-run.ps1             # PowerShell auto-run script
├── start-server.bat         # Batch file auto-run
└── cipms/                   # Main application
    ├── login.php            # Login page
    ├── register.php         # User registration
    ├── includes/            # Core PHP files
    │   ├── config.php       # Database configuration
    │   ├── functions.php    # Utility functions
    │   ├── header.php       # HTML header
    │   └── footer.php       # HTML footer
    ├── modules/             # Role-based modules
    │   ├── admin/           # Admin functionality
    │   ├── teacher/         # Teacher functionality
    │   └── student/         # Student functionality
    ├── database/            # Database files
    │   └── cipmsdb.sql      # Database schema
    └── assets/              # Static assets
        ├── css/             # Stylesheets
        ├── js/              # JavaScript files
        └── images/          # Images
```

## 🔧 Configuration

### Database Configuration

Edit `cipms/includes/config.php`:

```php
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'cipmsdb');
```

### Base URL Configuration

The base URL is automatically detected, but can be manually set:

```php
$base_url = 'http://localhost:8000';
```

## 👥 User Roles & Access

### Student

- View and apply for internships
- Track application status
- Manage personal profile

### Teacher

- Review student applications
- Monitor internship progress
- Generate progress reports

### Administrator

- Full system access
- User management
- Internship opportunity management
- System analytics

## 🌐 Deployment

### Local Development

- Use PHP built-in server: `php -S localhost:8000`
- Or use XAMPP/WAMP stack

### Production Deployment

- Shared hosting (PHP + MySQL)
- VPS with LAMP/LEMP stack
- Cloud platforms (AWS, DigitalOcean)

### Modern Deployment

- **Vercel**: Free Next.js hosting
- **Supabase**: Free database + auth
- **Netlify**: Alternative static hosting

## 🔒 Security Features

- Session-based authentication
- Role-based access control
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- CSRF protection (form tokens)

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Troubleshooting

### Common Issues

**"PHP is not installed"**

- Install PHP from https://www.php.net/downloads.php
- Add PHP to system PATH

**"Database connection failed"**

- Check MySQL service is running
- Verify database credentials in config.php
- Import the database SQL file

**"Port already in use"**

- Change port: `php -S localhost:8001`
- Close other applications using port 8000

**"Access denied" in PowerShell**

- Run as Administrator
- Set execution policy: `Set-ExecutionPolicy RemoteSigned -Scope CurrentUser`

### Getting Help

- Check the [FAQ](docs/FAQ.md)
- Open an [Issue](https://github.com/your-repo/issues)
- Contact support

## 🔄 Migration to Modern Stack

For migrating from PHP to React/Next.js:

1. Export existing data from MySQL
2. Set up Supabase project
3. Import data to Supabase
4. Create Next.js project structure
5. Migrate functionality module by module
6. Test and deploy

See [Migration Guide](docs/MIGRATION.md) for detailed instructions.

## 📊 System Requirements

### Minimum Requirements

- **RAM**: 2GB
- **Storage**: 500MB
- **PHP**: 7.4+
- **MySQL**: 8.0+

### Recommended Requirements

- **RAM**: 4GB+
- **Storage**: 1GB+
- **PHP**: 8.0+
- **MySQL**: 8.0+

## 📈 Roadmap

- [ ] Mobile app development
- [ ] Real-time notifications
- [ ] Advanced analytics dashboard
- [ ] Integration with LinkedIn
- [ ] Automated application matching
- [ ] Video interview integration

## 📞 Support

For support and questions:

- 📧 Email: support@cipms.com
- 💬 Discord: [Join our community]
- 📱 Phone: +1-234-567-8900

---

**Built with ❤️ for better internship management**

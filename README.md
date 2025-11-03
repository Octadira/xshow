# XShow File Manager

<div align="center">
  <img src="assets/img/xshow-logo-site.png" alt="XShow Logo" width="300">
  <p><em>Organize. Create. Control.</em></p>
  <p><strong>Version 2.0.0</strong></p>
</div>

## 🌟 Overview

XShow is a modern, secure file management application built with PHP and SQLite. It provides a clean, intuitive interface for managing files and folders with user authentication and admin controls. Version 2.0 introduces powerful navigation features including a collapsible folder tree sidebar and enhanced markdown editing capabilities.

## ✨ Features

### 🆕 New in Version 2.0

- **🌲 Collapsible Folder Tree Sidebar** - Visual hierarchical navigation with expandable/collapsible folders
  - Recursive tree structure showing complete folder hierarchy
  - Expandable folders with arrow indicators (▶/▼)
  - Sticky sidebar that persists while scrolling
  - Mobile-responsive with slide-out panel and toggle button
  - State persistence using sessionStorage - folders stay open after navigation
  - Click folder name to navigate or arrow to expand/collapse

- **📝 Create Markdown Files** - Create new .md files directly from the interface
  - Dedicated action card for creating markdown files
  - Auto-appends `.md` extension if not provided
  - Seamless redirect to SimpleMDE editor after creation
  - Unique autosave per file to prevent content conflicts

### Core Features

- **🗂️ File Management** - Upload, organize, rename, move, and delete files and folders
- **👥 User Management** - Admin panel for managing users and permissions
- **🔒 Secure Authentication** - Session-based login with role-based access
- **📁 Advanced Navigation** - Browse through folder hierarchies with tree view
- **🔍 Search Functionality** - Find both files and folders quickly
- **📤 Multiple Upload** - Upload multiple files simultaneously
- **✏️ Markdown Editor** - Integrated SimpleMDE for editing markdown files
- **🎨 Modern UI** - Responsive design with gradient themes and smooth animations
- **🛡️ Security** - CSRF protection, secure sessions, file validation
- **💾 Database Storage** - SQLite database for users, files, and metadata


## 🚀 Installation

### Requirements

- PHP 7.4 or higher
- PDO SQLite extension
- Web server (Apache, Nginx, etc.)
- Write permissions on the application directory

### Quick Install

1. **Upload all files** to your web server
2. **Set write permissions** on the `xshow` directory
3. **Navigate** to `install.php` in your web browser
4. **Create your admin account** during installation
5. **Start using** XShow!

### File Structure

```
xshow/
├── config.php          # Database configuration
├── auth.php            # Authentication functions
├── file_manager.php    # File operations
├── install.php         # Installation script
├── index.php           # Main file manager
├── login.php           # Login page
├── admin.php           # Admin panel
├── data/               # SQLite database
├── uploads/            # User files
└── README.md
```

## 🔧 Configuration

XShow uses SQLite for data storage and creates the database automatically during installation. File uploads are stored in the `uploads/` directory.

### User Roles

- **Admin**: Full access to user management and all files
- **User**: Access to file management only

## 🛡️ Security Features

- Secure session management with httponly and samesite cookies
- CSRF protection on forms
- Password hashing with bcrypt
- Directory traversal protection
- File upload validation and size limits
- Role-based access control
- Automatic database creation with proper permissions

## 💡 Use Cases

XShow is perfect for:

- **Web developers** organizing project assets and files with visual tree navigation
- **Small teams** sharing and collaborating on files and markdown documentation
- **Content creators** managing web content, media, and markdown articles
- **Documentation writers** creating and organizing markdown-based documentation
- **System administrators** needing a simple file management solution
- **Personal file storage** with user access controls and hierarchical organization

## 🏗️ Architecture

XShow follows a modular architecture:

- **config.php**: Database configuration and user management
- **auth.php**: Authentication and session management
- **file_manager.php**: File operations and folder management
- **install.php**: One-time setup and database initialization
- **index.php**: Main file manager interface
- **login.php**: User authentication page
- **admin.php**: Administrative user management

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. Fork the repository
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request



## 📜 License

This project is licensed under the MIT License.

## 🙏 Acknowledgements

- [Tailwind CSS](https://tailwindcss.com/) for the design inspiration
- [SimpleMDE](https://simplemde.com/) for the Markdown editor
- All the amazing open-source contributors who make projects like this possible

## 📞 Support

Having trouble? Check out our [documentation](https://github.com/adra-hub/xshow/wiki) or [open an issue](https://github.com/adra-hub/xshow/issues/new).

---

<div align="center">
  <p>Crafted with 🫀 by ADIRA Studio</p>
</div>

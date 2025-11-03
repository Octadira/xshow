# Changelog

All notable changes to XShow File Manager will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-11-03

### Added

- **Collapsible Folder Tree Sidebar** - Major UI enhancement for navigation
  - Recursive tree structure displaying complete folder hierarchy
  - Expandable/collapsible folders with arrow indicators (▶/▼)
  - Sticky sidebar that remains visible while scrolling
  - Mobile-responsive design with slide-out panel and toggle button (📂)
  - State persistence using sessionStorage - expanded folders remain open after navigation
  - Click folder name to navigate or arrow to expand/collapse independently
  - Implemented in `index.php` with `getFolderTree()` and `buildTreeNode()` methods in `file_manager.php`

- **Create Markdown File Functionality**
  - New action card in UI to create `.md` files directly
  - Auto-appends `.md` extension if not provided by user
  - Automatic redirect to SimpleMDE editor after file creation
  - Implemented `createFile()` method in `file_manager.php`
  - Added POST handler and UI form in `index.php`

- **Enhanced Search Functionality**
  - Search now finds both files AND folders (previously files only)
  - Improved search results display

- **File Operations**
  - Rename functionality with modal UI
  - Move functionality with folder selector
  - Enhanced file validation and error handling

- **New Documentation**
  - Added `SETUP-INFO.md` for detailed setup instructions
  - Added `TROUBLESHOOTING.md` for common issues and solutions
  - Added `.htaccess` for improved Apache configuration

### Fixed

- **SimpleMDE Autosave Conflict** - Fixed unique ID generation per file
  - Now uses `md5($editingFile . $path)` to generate unique IDs
  - Prevents new markdown files from copying content from existing files
  - Each file has its own autosave state

- **Tree Collapse on Navigation** - Fixed folder tree state persistence
  - Implemented sessionStorage to maintain expanded folder states
  - Tree state now persists when navigating between folders
  - Users don't need to re-expand folders after each navigation

- **Logout Functionality** - Fixed logout bugs in both `index.php` and `admin.php`
  - Proper session destruction and redirect handling
  - Security improvements in logout flow

- **Path Validation** - Enhanced security with `isPathAllowed()` method
  - Better protection against directory traversal attacks
  - Validation for all file operations

### Changed

- **Architecture Refactoring**
  - Separated concerns into dedicated files:
    - `auth.php` - Authentication and session management
    - `file_manager.php` - File operations class
    - `config.php` - Database and configuration
  - Improved code organization and maintainability

- **UI/UX Improvements**
  - Enhanced responsive design for mobile devices
  - Better visual feedback for user actions
  - Improved modal designs for rename and move operations
  - Cleaner action card layout

### Removed

- **Cleanup of Unused Files**
  - Removed `components/MarkdownEditor.js` (functionality integrated)
  - Removed `assets/css/style.css` (consolidated styles)
  - Removed `assets/js/main.js` (refactored)
  - Removed `assets/js/admin.js` (refactored)
  - Removed test files: `test-home.php`, `test-index.php`, `index-simple.php`
  - Removed `.DS_Store` files
  - Removed old view templates from `assets/views/`

### Security

- Enhanced CSRF protection across all forms
- Improved file upload validation
- Better session management with secure cookies
- Enhanced path traversal protection
- Added sanitization for user inputs

---

## [1.0.0] - Initial Release

### Added

- Initial release of XShow File Manager
- Basic file upload and download functionality
- User authentication system
- Admin panel for user management
- Folder navigation
- SQLite database integration
- SimpleMDE markdown editor integration
- Responsive UI with gradient themes

[2.0.0]: https://github.com/Octadira/xshow/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/Octadira/xshow/releases/tag/v1.0.0

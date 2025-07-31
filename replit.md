# Online Casino Platform

## Overview

This is a comprehensive online casino platform built with Flask, featuring user registration, game management, deposit/withdrawal systems, referral programs, and a complete admin panel. The application is designed to be fully compatible with shared hosting environments and provides a complete gaming platform experience.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Backend Architecture
- **Framework**: Flask web framework with modular blueprint structure
- **Database**: SQLAlchemy ORM with configurable database support (defaults to SQLite, supports PostgreSQL via DATABASE_URL)
- **Authentication**: Session-based authentication with separate user and admin systems
- **File Handling**: Secure file upload system for game assets and payment screenshots
- **Middleware**: ProxyFix for handling reverse proxy headers

### Frontend Architecture
- **Template Engine**: Jinja2 templating with modular template inheritance
- **UI Framework**: Bootstrap 5 for responsive design
- **Icons**: Font Awesome for consistent iconography
- **JavaScript**: Vanilla JavaScript with Bootstrap components for interactivity
- **Styling**: Custom CSS with CSS variables for theming (casino gold/black theme)

### Database Schema
- **User Management**: Users with balance tracking, referral system, and authentication
- **Admin System**: Multi-role admin accounts (super_admin, game_manager, payment_manager)
- **Gaming**: Game catalog with categories, betting limits, and winning percentages
- **Financial**: Deposit/withdrawal request tracking with status management
- **Content Management**: Homepage sliders and site settings

### Security & Authorization
- **Password Security**: Werkzeug password hashing for secure credential storage
- **Access Control**: Decorator-based authentication for user and admin routes
- **File Security**: Secure filename handling and upload directory management
- **Session Management**: Flask session handling with configurable secret keys

### Modular Route Structure
- **Main Routes**: Homepage, game catalog, and game playing interfaces
- **Auth Routes**: User registration and login functionality
- **User Routes**: Dashboard, deposit/withdrawal, and profile management
- **Admin Routes**: Complete administrative interface for platform management

## External Dependencies

### Core Framework Dependencies
- **Flask**: Web framework with SQLAlchemy integration
- **Werkzeug**: Security utilities and middleware components
- **Jinja2**: Template rendering (included with Flask)

### Frontend Dependencies (CDN-based)
- **Bootstrap 5**: CSS framework for responsive design
- **Font Awesome 6**: Icon library for UI elements
- **JavaScript**: Bootstrap JavaScript components for interactive elements

### Database Support
- **MySQL**: Primary database for shared hosting (PyMySQL driver)
- **PostgreSQL**: Production database support via DATABASE_URL environment variable
- **SQLite**: Fallback database for development and testing
- **SQLAlchemy**: ORM with connection pooling and engine optimization

### File Storage
- **Local File System**: Upload directory for game assets, screenshots, and slider images
- **Secure Upload**: File type validation and secure filename handling

### Environment Configuration
- **Environment Variables**: DATABASE_URL, SESSION_SECRET for deployment flexibility
- **Shared Hosting Compatibility**: No Node.js or complex build processes required
- **PHP Installation Script**: Complete setup wizard for shared hosting deployment
- **Static Asset Management**: Direct file serving for uploaded content

### Deployment Tools
- **install.php**: Complete installation wizard with system requirements checking
- **MySQL Configuration**: Automatic database setup and table creation
- **Security Setup**: Automatic .htaccess and environment configuration
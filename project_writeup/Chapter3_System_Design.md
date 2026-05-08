# CHAPTER 3: SYSTEM DESIGN AND METHODOLOGY

## 3.1 Introduction

This chapter presents the detailed system design, architecture, and methodology for implementing the MurgLogistics management system. The design follows best practices identified in the literature review and addresses the specific requirements of MurgLogistics operations.

## 3.2 System Architecture

### 3.2.1 Overall Architecture
The MurgLogistics system follows a three-tier architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Customer  │  │    Admin    │  │      Driver         │  │
│  │   Portal    │  │  Dashboard  │  │    Interface        │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Application Layer                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Auth      │  │   Business  │  │      Reporting       │  │
│  │   System    │  │    Logic    │  │      Module         │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      Data Layer                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │   Users     │  │ Shipments   │  │      Tracking        │  │
│  │   Table     │  │    Table    │  │      History         │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 3.2.2 Technology Stack
The system is built using the following technologies:

**Frontend:**
- HTML5, CSS3, JavaScript
- FontAwesome for icons
- Responsive design principles

**Backend:**
- PHP 8.x for server-side logic
- MySQL for database management
- PDO for database connectivity

**Security:**
- Session-based authentication
- Password hashing (bcrypt)
- SQL injection prevention
- XSS protection

## 3.3 Database Design

### 3.3.1 Entity Relationship Diagram
The database consists of the following main entities:

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│    Users    │       │ Shipments   │       │   Tracking  │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ id (PK)     │◄──────│ id (PK)     │◄──────│ id (PK)     │
│ name        │       │ user_id(FK) │       │ shipment_id │
│ email       │       │ tracking_no │       │ status      │
│ password    │       │ sender_name │       │ location    │
│ phone       │       │ receiver_n  │       │ remarks     │
│ role        │       │ sender_addr │       │ updated_by  │
│ status      │       │ receiver_a  │       │ updated_at  │
│ branch_id   │       │ weight      │       └─────────────┘
│ created_at  │       │ cost        │
└─────────────┘       │ status      │
       │              │ branch_id   │
       │              │ driver_id   │
       │              │ created_at  │
       ▼              └─────────────┘
┌─────────────┐               │
│   Branches  │               │
├─────────────┤               │
│ id (PK)     │               │
│ name        │               │
│ address     │               │
│ phone       │               │
│ manager_id  │               │
│ created_at  │               │
└─────────────┘               │
                               │
┌─────────────┐               │
│   Vehicles  │               │
├─────────────┤               │
│ id (PK)     │               │
│ plate_no    │               │
│ type        │               │
│ capacity    │               │
│ driver_id   │               │
│ status      │               │
│ created_at  │               │
└─────────────┘               │
                               │
┌─────────────┐               │
│ Shipment_   │               │
│ Items       │               │
├─────────────┤               │
│ id (PK)     │               │
│ shipment_id │               │
│ description │               │
│ weight      │               │
│ created_at  │               │
└─────────────┘               │
                               ▼
                    ┌─────────────┐
                    │ Price_      │
                    │ Settings    │
                    ├─────────────┤
                    │ id (PK)     │
                    │ price_per_kg│
                    │ updated_by  │
                    │ updated_at  │
                    └─────────────┘
```

### 3.3.2 Table Structures

**Users Table:**
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'manager', 'driver', 'customer') DEFAULT 'customer',
    status ENUM('active', 'inactive') DEFAULT 'active',
    branch_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);
```

**Shipments Table:**
```sql
CREATE TABLE shipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tracking_number VARCHAR(50) UNIQUE NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    sender_phone VARCHAR(20) NOT NULL,
    sender_address TEXT NOT NULL,
    receiver_name VARCHAR(100) NOT NULL,
    receiver_phone VARCHAR(20) NOT NULL,
    receiver_address TEXT NOT NULL,
    weight DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    current_status VARCHAR(50) DEFAULT 'Pending',
    branch_id INT,
    driver_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (driver_id) REFERENCES users(id)
);
```

## 3.4 System Modules

### 3.4.1 Authentication Module
**Purpose:** Secure user authentication and authorization

**Components:**
- Login system
- Session management
- Role-based access control
- Password security

**Security Features:**
- Password hashing using bcrypt
- Session timeout management
- CSRF protection
- Input validation

### 3.4.2 Customer Portal Module
**Purpose:** Provide customers with self-service capabilities

**Features:**
- User registration
- Shipment booking
- Real-time tracking
- Invoice generation
- Profile management

**User Interface:**
- Responsive design
- Intuitive navigation
- Real-time updates
- Mobile-friendly

### 3.4.3 Admin Dashboard Module
**Purpose:** Comprehensive logistics management for administrators

**Features:**
- Shipment management
- User management
- Driver assignment
- Branch management
- System configuration
- Reporting and analytics

**Access Control:**
- Admin and manager roles
- Granular permissions
- Audit logging

### 3.4.4 Tracking System Module
**Purpose:** Real-time shipment tracking and status updates

**Features:**
- Status updates
- Location tracking
- History logging
- Customer notifications

**Data Flow:**
1. Driver updates shipment status
2. System records timestamp and location
3. Customer portal displays real-time status
4. Historical data maintained for audit purposes

### 3.4.5 Invoice Generation Module
**Purpose:** Automated invoice creation and management

**Features:**
- Professional invoice templates
- Automatic calculations
- PDF generation
- Historical tracking

**Invoice Components:**
- Sender/receiver information
- Shipment details
- Cost breakdown
- Tax calculations
- Payment terms

## 3.5 User Interface Design

### 3.5.1 Design Principles
The user interface follows these design principles:

1. **Consistency**: Uniform design patterns across all modules
2. **Intuitiveness**: Clear navigation and logical flow
3. **Responsiveness**: Adaptive design for various screen sizes
4. **Accessibility**: Compliance with web accessibility standards
5. **Performance**: Optimized for fast loading and interaction

### 3.5.2 Color Scheme and Branding
- **Primary Color**: #6366f1 (Indigo)
- **Secondary Colors**: Various shades for different actions
- **Typography**: Inter font family for readability
- **Icons**: FontAwesome for consistent iconography

### 3.5.3 Layout Structure
```
┌─────────────────────────────────────────────────────────────┐
│                        Header                                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │    Logo     │  │ Navigation  │  │    User Profile     │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
├─────────────────────────────────────────────────────────────┤
│                       Sidebar                                │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │  • Dashboard                                           │  │
│  │  • Shipments                                           │  │
│  │  • Drivers                                             │  │
│  │  • Branches                                            │  │
│  │  • Reports                                             │  │
│  └─────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────┤
│                      Main Content                           │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │                    Dynamic Content                      │  │
│  └─────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## 3.6 Development Methodology

### 3.6.1 Agile Approach
The project follows an agile development methodology:

**Sprint Planning:**
- Feature prioritization
- Time-boxed development cycles
- Regular stakeholder feedback

**Development Process:**
1. Requirements analysis
2. Design and prototyping
3. Implementation
4. Testing and validation
5. Deployment and monitoring

### 3.6.2 Version Control
- Git for source code management
- Feature branching strategy
- Regular commits with descriptive messages
- Code review process

### 3.6.3 Testing Strategy
**Unit Testing:**
- Individual function testing
- Database query validation
- Input validation testing

**Integration Testing:**
- Module interaction testing
- API endpoint testing
- Database integration testing

**User Acceptance Testing:**
- End-to-end workflow testing
- User experience validation
- Performance testing

## 3.7 Security Implementation

### 3.7.1 Authentication Security
- Secure password hashing (bcrypt)
- Session management with timeout
- Multi-factor authentication consideration
- Account lockout after failed attempts

### 3.7.2 Data Protection
- Input sanitization and validation
- SQL injection prevention using prepared statements
- XSS protection through output encoding
- CSRF token implementation

### 3.7.3 Access Control
- Role-based permissions
- Resource-level access control
- Audit logging for sensitive operations
- Secure file upload handling

## 3.8 Performance Optimization

### 3.8.1 Database Optimization
- Proper indexing for frequently queried columns
- Query optimization for complex operations
- Connection pooling for database connections
- Regular database maintenance

### 3.8.2 Frontend Optimization
- Minimized CSS and JavaScript files
- Image optimization
- Lazy loading for large datasets
- Browser caching strategies

### 3.8.3 Server Optimization
- Efficient PHP code structure
- Memory management
- Response time optimization
- Error handling and logging

## 3.9 Deployment Strategy

### 3.9.1 Development Environment
- Local development server (XAMPP)
- Version control integration
- Automated testing setup
- Development database

### 3.9.2 Production Deployment
- Web server configuration
- Database migration scripts
- Security hardening
- Monitoring and logging setup

### 3.9.3 Backup and Recovery
- Regular database backups
- File system backups
- Disaster recovery plan
- Data retention policies

## 3.10 Summary

This chapter has presented a comprehensive system design for the MurgLogistics management system. The design incorporates modern web development practices, security considerations, and user experience principles. The modular architecture ensures scalability and maintainability while the database design provides efficient data management. The next chapter will detail the implementation process and testing results.

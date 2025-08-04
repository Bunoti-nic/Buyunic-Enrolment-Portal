-- BUYUNIC Enrollment Portal Database Schema
-- Created: 2024

CREATE DATABASE IF NOT EXISTS buyunic_enrollment CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE buyunic_enrollment;

-- Set SQL mode to handle timestamps properly
SET sql_mode = '';

-- Users table (for both applicants and admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('applicant', 'admin', 'sub_admin') DEFAULT 'applicant',
    status ENUM('pending', 'active', 'suspended', 'banned') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    last_login DATETIME NULL DEFAULT NULL,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Email verification tokens
CREATE TABLE email_verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password reset tokens
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- OTP tokens for MFA
CREATE TABLE otp_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    purpose ENUM('login', 'verification', 'password_reset') DEFAULT 'login',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Training programs catalog
CREATE TABLE training_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    program_name VARCHAR(200) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Applications table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    application_id VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('draft', 'submitted', 'under_review', 'approved', 'rejected', 'returned_for_edit') DEFAULT 'draft',
    progress_percentage INT DEFAULT 0,
    edit_count INT DEFAULT 0,
    submitted_at DATETIME NULL DEFAULT NULL,
    reviewed_at DATETIME NULL DEFAULT NULL,
    reviewer_id INT NULL,
    review_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Personal details
CREATE TABLE personal_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    title VARCHAR(10),
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    national_id VARCHAR(50),
    passport_number VARCHAR(50),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    disability_status BOOLEAN DEFAULT FALSE,
    disability_details TEXT,
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    district VARCHAR(100),
    country VARCHAR(100),
    postal_code VARCHAR(20),
    phone_primary VARCHAR(20),
    phone_secondary VARCHAR(20),
    email_personal VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- Next of kin details
CREATE TABLE next_of_kin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    relationship VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    address VARCHAR(500),
    occupation VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- Academic background
CREATE TABLE academic_background (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    education_level ENUM('primary', 'secondary', 'tertiary', 'postgraduate', 'professional') NOT NULL,
    institution_name VARCHAR(200) NOT NULL,
    qualification VARCHAR(200) NOT NULL,
    start_date DATE,
    end_date DATE,
    grade_obtained VARCHAR(50),
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- Internee information
CREATE TABLE internee_information (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    is_internee BOOLEAN DEFAULT FALSE,
    institution_name VARCHAR(200),
    registration_number VARCHAR(100),
    supervisor_name VARCHAR(200),
    supervisor_contact VARCHAR(20),
    internship_start_date DATE,
    internship_end_date DATE,
    interest_areas TEXT,
    previous_experience TEXT,
    career_goals TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- Program selections
CREATE TABLE program_selections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    program_id INT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES training_programs(id) ON DELETE CASCADE
);

-- Document uploads
CREATE TABLE document_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type ENUM('id_document', 'passport_photo', 'academic_certificate', 'academic_transcript', 'disability_certificate', 'other') NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_status ENUM('pending', 'approved', 'rejected', 'flagged') DEFAULT 'pending',
    admin_notes TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at DATETIME NULL DEFAULT NULL,
    reviewed_by INT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    payment_reference VARCHAR(100) UNIQUE NOT NULL,
    payment_method ENUM('mobile_money', 'flexipay', 'pesapal', 'bank_transfer') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    payment_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    payment_phone VARCHAR(20),
    payment_email VARCHAR(255),
    payment_date DATETIME NULL DEFAULT NULL,
    verified_by INT NULL,
    verification_date DATETIME NULL DEFAULT NULL,
    receipt_number VARCHAR(100),
    gateway_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- User sessions
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages (2-way communication)
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME NULL DEFAULT NULL,
    parent_message_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES messages(id) ON DELETE SET NULL
);

-- Admin logs
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- PDF generations log
CREATE TABLE pdf_generations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    pdf_type ENUM('application_summary', 'enrollment_letter', 'placement_letter', 'receipt') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    qr_code VARCHAR(255),
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Consent and privacy acknowledgements
CREATE TABLE consents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    terms_conditions BOOLEAN DEFAULT FALSE,
    privacy_policy BOOLEAN DEFAULT FALSE,
    data_processing BOOLEAN DEFAULT FALSE,
    marketing_communications BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    user_agent TEXT,
    consent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- QR code verifications
CREATE TABLE qr_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    verification_code VARCHAR(255) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    is_valid BOOLEAN DEFAULT TRUE,
    verified_at DATETIME NULL DEFAULT NULL,
    verifier_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL DEFAULT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default training programs
INSERT INTO training_programs (category, program_name, amount, duration, description) VALUES
-- Microsoft Office Suite
('MICROSOFT OFFICE SUITE', 'General intro + MS Word (2-in-1)', 120000, '3 Wks (2 hrs/day)', 'Comprehensive introduction to Microsoft Office and Word processing'),
('MICROSOFT OFFICE SUITE', 'Microsoft PowerPoint', 50000, '2 Wks', 'Create professional presentations and slideshows'),
('MICROSOFT OFFICE SUITE', 'Microsoft Excel', 60000, '2 Wks', 'Master spreadsheets, formulas, and data analysis'),
('MICROSOFT OFFICE SUITE', 'Microsoft Access/Database', 60000, '2 Wks', 'Database design and management with Access'),
('MICROSOFT OFFICE SUITE', 'Microsoft Publisher', 50000, '2 Wks', 'Desktop publishing and marketing materials'),
('MICROSOFT OFFICE SUITE', 'Internet Basics', 50000, '2 Wks', 'Web browsing, email, and online communication'),

-- Graphics & Web Development
('GRAPHICS & WEB DEVELOPMENT', 'Adobe Photoshop', 550000, '4 Wks', 'Professional image editing and digital design'),
('GRAPHICS & WEB DEVELOPMENT', 'Adobe Illustrator', 550000, '4 Wks', 'Vector graphics and logo design'),
('GRAPHICS & WEB DEVELOPMENT', 'PageMaker', 250000, '4 Wks', 'Desktop publishing and layout design'),
('GRAPHICS & WEB DEVELOPMENT', 'HTML Language', 600000, '4 Wks', 'Web development fundamentals with HTML'),
('GRAPHICS & WEB DEVELOPMENT', 'WordPress CMS', 500000, '4 Wks', 'Content management and website creation'),
('GRAPHICS & WEB DEVELOPMENT', 'CorelDRAW', 550000, '4 Wks', 'Vector graphics and design software'),

-- Specialized IT Programs
('SPECIALIZED IT PROGRAMS', 'Computer Networking', 550000, '6 Wks', 'Network setup, configuration, and troubleshooting'),
('SPECIALIZED IT PROGRAMS', 'Hardware/Software Troubleshooting', 300000, '3 Wks', 'Computer repair and maintenance'),
('SPECIALIZED IT PROGRAMS', 'CCTV Installation & Config', 550000, '3 Wks', 'Security camera systems installation'),
('SPECIALIZED IT PROGRAMS', 'Tally Accounting Software', 550000, '6 Wks', 'Business accounting and inventory management'),
('SPECIALIZED IT PROGRAMS', 'QuickBooks', 550000, '6 Wks', 'Financial management and bookkeeping'),
('SPECIALIZED IT PROGRAMS', 'Epinfo', 550000, '6 Wks', 'Epidemiological data management'),
('SPECIALIZED IT PROGRAMS', 'SPSS', 550000, '6 Wks', 'Statistical analysis software'),
('SPECIALIZED IT PROGRAMS', 'Stata', 350000, '6 Wks', 'Statistical software package'),
('SPECIALIZED IT PROGRAMS', 'Epi Data', 350000, '6 Wks', 'Data entry and analysis'),

-- Internship & Research
('INTERNSHIP & RESEARCH', 'Custom IT Project / Research-Based Training', 400000, '2 Months', 'Tailored IT projects and research opportunities');

-- Insert default admin user (password: 'password' - change immediately!)
INSERT INTO users (email, password_hash, first_name, last_name, user_type, status, email_verified) VALUES
('admin@buyunic.ug', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/UnuaDHdmbxQs/qxWu', 'System', 'Administrator', 'admin', 'active', TRUE);

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('registration_fee', '30000', 'number', 'Registration fee for non-internees'),
('max_edit_attempts', '3', 'number', 'Maximum number of edit attempts for applications'),
('session_timeout', '900', 'number', 'Session timeout in seconds'),
('max_file_size', '1048576', 'number', 'Maximum file upload size in bytes'),
('maintenance_mode', 'false', 'boolean', 'Enable/disable maintenance mode'),
('auto_approve_payments', 'false', 'boolean', 'Auto approve verified payments');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_type_status ON users(user_type, status);
CREATE INDEX idx_applications_user_id ON applications(user_id);
CREATE INDEX idx_applications_status ON applications(status);
CREATE INDEX idx_applications_application_id ON applications(application_id);
CREATE INDEX idx_payments_application_id ON payments(application_id);
CREATE INDEX idx_payments_status ON payments(payment_status);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_messages_application_id ON messages(application_id);
CREATE INDEX idx_admin_logs_admin_id ON admin_logs(admin_id);
CREATE INDEX idx_admin_logs_created_at ON admin_logs(created_at);
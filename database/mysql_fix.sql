-- MySQL Configuration Fix for BUYUNIC Enrollment Portal
-- This script fixes common MySQL timestamp and strict mode issues

-- Check current SQL mode
SELECT @@sql_mode;

-- Set SQL mode to allow zero dates and invalid dates (if needed for development)
SET sql_mode = '';

-- Alternative: Set a more permissive but safer mode
-- SET sql_mode = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- Check MySQL version
SELECT VERSION();

-- Check if database exists
SHOW DATABASES LIKE 'buyunic_enrollment';

-- Drop database if you need to recreate (CAUTION: This will delete all data!)
-- DROP DATABASE IF EXISTS buyunic_enrollment;

-- Create database with proper charset
CREATE DATABASE IF NOT EXISTS buyunic_enrollment 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE buyunic_enrollment;

-- Check current timezone
SELECT @@time_zone;

-- Set timezone if needed (optional)
-- SET time_zone = '+03:00'; -- East Africa Time

-- Show current timestamp settings
SHOW VARIABLES LIKE 'explicit_defaults_for_timestamp';

-- For MySQL 5.7+ with strict mode, you may need to set this in my.cnf:
-- [mysqld]
-- sql_mode = "ONLY_FULL_GROUP_BY,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"
-- explicit_defaults_for_timestamp = 1

-- Test timestamp functionality
SELECT NOW() as current_time;
SELECT CURRENT_TIMESTAMP as current_timestamp;
SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR) as one_hour_later;
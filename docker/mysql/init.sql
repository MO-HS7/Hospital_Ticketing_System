-- MySQL Initialization Script
-- This file is executed when the MySQL container starts for the first time

-- Create additional databases if needed
-- CREATE DATABASE IF NOT EXISTS hospital_ticketing_testing;

-- Grant privileges
GRANT ALL PRIVILEGES ON hospital_ticketing.* TO 'hospital'@'%';
GRANT ALL PRIVILEGES ON hospital_ticketing.* TO 'hospital'@'localhost';

FLUSH PRIVILEGES;

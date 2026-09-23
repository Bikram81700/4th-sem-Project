-- BedTrack Database Schema & Seed Data

CREATE DATABASE IF NOT EXISTS bedtrackproject CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bedtrackproject;

SET FOREIGN_KEY_CHECKS = 0;

-- Hospitals
DROP TABLE IF EXISTS hospitals;
CREATE TABLE hospitals (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(150) NOT NULL,
  city            VARCHAR(100) NOT NULL,
  type            VARCHAR(50)  NOT NULL,
  specializations VARCHAR(255) DEFAULT '',
  status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  license_file    VARCHAR(255) DEFAULT NULL,
  cover_photo     VARCHAR(255) DEFAULT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users (Patients, Hospital Admins, Super Admin)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  name               VARCHAR(100) NOT NULL,
  email              VARCHAR(150) NOT NULL UNIQUE,
  password           VARCHAR(255) NOT NULL,
  role               ENUM('user','hospital','super') NOT NULL,
  phone              VARCHAR(30)  DEFAULT NULL,
  blood_group        VARCHAR(5)   DEFAULT NULL,
  address            VARCHAR(255) DEFAULT NULL,
  emergency_contact  VARCHAR(30)  DEFAULT NULL,
  hospital_id        INT DEFAULT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bed Categories
DROP TABLE IF EXISTS bed_categories;
CREATE TABLE bed_categories (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  hospital_id  INT NOT NULL,
  name         VARCHAR(100) NOT NULL,
  total        INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_cat_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Individual Bed Records
DROP TABLE IF EXISTS beds;
CREATE TABLE beds (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  category_id  INT NOT NULL,
  hospital_id  INT NOT NULL,
  status       ENUM('available','occupied') NOT NULL DEFAULT 'available',
  CONSTRAINT fk_bed_category FOREIGN KEY (category_id) REFERENCES bed_categories(id) ON DELETE CASCADE,
  CONSTRAINT fk_bed_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Bookings
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  hospital_id   INT NOT NULL,
  category_id   INT NOT NULL,
  patient_name  VARCHAR(150) NOT NULL,
  booking_for   ENUM('self','other') NOT NULL DEFAULT 'self',
  contact       VARCHAR(30) DEFAULT NULL,
  booking_date  DATE NOT NULL,
  status        ENUM('pending','accepted','rejected','discharged') NOT NULL DEFAULT 'pending',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_user     FOREIGN KEY (user_id)     REFERENCES users(id)          ON DELETE CASCADE,
  CONSTRAINT fk_booking_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id)      ON DELETE CASCADE,
  CONSTRAINT fk_booking_category FOREIGN KEY (category_id) REFERENCES bed_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Hospital Photos
DROP TABLE IF EXISTS hospital_photos;
CREATE TABLE hospital_photos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  hospital_id INT NOT NULL,
  filename    VARCHAR(255) NOT NULL,
  caption     VARCHAR(255) DEFAULT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_photo_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Password Resets
DROP TABLE IF EXISTS password_resets;
CREATE TABLE password_resets (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(150) NOT NULL,
  token      VARCHAR(64)  NOT NULL UNIQUE,
  expires_at DATETIME     NOT NULL,
  used       TINYINT(1)   NOT NULL DEFAULT 0,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token),
  INDEX idx_email (email)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Seed Data (Default accounts password: password)

INSERT INTO hospitals (id, name, city, type, specializations, status, license_file, cover_photo) VALUES
(1, 'Sunrise General Hospital',      'Kathmandu', 'Multi-specialty', 'Cardiology, Trauma, ICU', 'approved', 'sunrise_license.pdf', NULL),
(2, 'Valley Care Medical Center',    'Lalitpur',  'General',         'Maternity, Pediatrics',   'approved', 'valley_license.pdf', NULL),
(3, 'Himal Community Hospital',      'Bhaktapur', 'Community',       'General Medicine',        'approved', 'himal_license.pdf', NULL),
(4, 'Northfield Speciality Clinic',  'Kathmandu', 'Specialty',       'Orthopedics',              'pending',  'northfield_license.pdf', NULL);

INSERT INTO users (id, name, email, password, role, phone, blood_group, address, emergency_contact, hospital_id) VALUES
(1, 'Admin Root',       'admin@bedtrack.com', '$2b$10$bHjvWHCAaQTikDaAg4RNd.MLxH1AauBGtnF4orizTtuTaxsSJ4rtW', 'super',    NULL,           NULL,  NULL,        NULL,           NULL),
(2, 'Priya Sharma',     'priya@example.com',  '$2b$10$bHjvWHCAaQTikDaAg4RNd.MLxH1AauBGtnF4orizTtuTaxsSJ4rtW', 'user',     '98450 11223',  'B+',  'Lalitpur',  '98450 99881',  NULL),
(3, 'Dr. Bimal Rai',    'admin@sunrise.com',  '$2b$10$bHjvWHCAaQTikDaAg4RNd.MLxH1AauBGtnF4orizTtuTaxsSJ4rtW', 'hospital', NULL,           NULL,  NULL,        NULL,           1),
(4, 'Dr. Anjali Thapa', 'admin@valley.com',   '$2b$10$bHjvWHCAaQTikDaAg4RNd.MLxH1AauBGtnF4orizTtuTaxsSJ4rtW', 'hospital', NULL,           NULL,  NULL,        NULL,           2);

INSERT INTO bed_categories (id, hospital_id, name, total) VALUES
(1, 1, 'ICU',            10),
(2, 1, 'General Ward',   20),
(3, 1, 'Private Room',   8),
(4, 2, 'HDU',            6),
(5, 2, 'General Ward',   15),
(6, 3, 'General Ward',   12);

INSERT INTO beds (category_id, hospital_id, status) VALUES
(1,1,'available'),(1,1,'available'),(1,1,'available'),(1,1,'available'),(1,1,'available'),(1,1,'available'),
(1,1,'occupied'),(1,1,'occupied'),(1,1,'occupied'),(1,1,'occupied'),
(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),(2,1,'available'),
(2,1,'occupied'),(2,1,'occupied'),(2,1,'occupied'),(2,1,'occupied'),(2,1,'occupied'),(2,1,'occupied'),(2,1,'occupied'),
(3,1,'available'),(3,1,'available'),(3,1,'available'),(3,1,'available'),(3,1,'available'),
(3,1,'occupied'),(3,1,'occupied'),(3,1,'occupied'),
(4,2,'available'),(4,2,'available'),(4,2,'available'),
(4,2,'occupied'),(4,2,'occupied'),(4,2,'occupied'),
(5,2,'available'),(5,2,'available'),(5,2,'available'),(5,2,'available'),(5,2,'available'),(5,2,'available'),(5,2,'available'),(5,2,'available'),(5,2,'available'),
(5,2,'occupied'),(5,2,'occupied'),(5,2,'occupied'),(5,2,'occupied'),(5,2,'occupied'),(5,2,'occupied'),
(6,3,'available'),(6,3,'available'),(6,3,'available'),(6,3,'available'),(6,3,'available'),(6,3,'available'),(6,3,'available'),
(6,3,'occupied'),(6,3,'occupied'),(6,3,'occupied'),(6,3,'occupied'),(6,3,'occupied');


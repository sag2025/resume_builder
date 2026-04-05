CREATE DATABASE IF NOT EXISTS resume_builder
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resume_builder;

-- One row per anonymous visitor, identified by PHP session ID
CREATE TABLE anonymous_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(128) UNIQUE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- One row per project.
-- resume_json and resume_html are NULL until user clicks Accept or closes window.
CREATE TABLE user_projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  project_name VARCHAR(200) NOT NULL,
  resume_json JSON DEFAULT NULL,
  resume_html LONGTEXT DEFAULT NULL,
  is_accepted TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES anonymous_users(id) ON DELETE CASCADE
);
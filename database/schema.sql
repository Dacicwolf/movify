-- Movify AI Video Generator – Database Schema
-- Database: ai_video_generator

CREATE DATABASE IF NOT EXISTS ai_video_generator
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ai_video_generator;

-- -------------------------------------------------------
-- Users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    verification_code VARCHAR(6) NULL,
    is_verified TINYINT(1) DEFAULT 0,
    credits INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Password Resets
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- Videos
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    prompt TEXT NULL,
    image_path VARCHAR(255) NULL,
    model_used VARCHAR(50) NOT NULL,
    resolution VARCHAR(20) NOT NULL,
    duration INT NOT NULL,
    format VARCHAR(20) NOT NULL,
    video_url VARCHAR(255) NOT NULL,
    credits_deducted INT NOT NULL,
    status VARCHAR(20) DEFAULT 'processing',
    queue_id VARCHAR(255) NULL,
    status_url VARCHAR(512) NULL,
    response_url VARCHAR(512) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

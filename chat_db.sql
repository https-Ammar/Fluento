-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS chat_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE chat_db;

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    is_online TINYINT(1) DEFAULT 0,
    last_seen DATETIME DEFAULT NULL,
    notifications_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الرسائل
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    type ENUM('text', 'image', 'video') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE
);

-- ✅ جدول المحادثات المخفية (لإخفاء الشات من طرف واحد فقط)
CREATE TABLE IF NOT EXISTS hidden_chats (
    user_id INT NOT NULL,
    hidden_with_id INT NOT NULL,
    PRIMARY KEY (user_id, hidden_with_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (hidden_with_id) REFERENCES users (id) ON DELETE CASCADE
);
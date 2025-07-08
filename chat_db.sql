-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS chat_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

USE chat_db;

-- جدول المستخدمين (كما هو)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_code VARCHAR(8) UNIQUE NOT NULL, -- كود ثابت من 8 أرقام
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default.jpg',
    is_online TINYINT(1) DEFAULT 0,
    last_seen DATETIME DEFAULT NULL,
    notifications_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- جدول الرسائل مع إضافة عمود read_status لتتبع القراءة
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    type ENUM('text', 'image', 'video') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_status TINYINT(1) DEFAULT 0, -- 0 = غير مقروءة، 1 = مقروءة
    FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_conversation (
        sender_id,
        receiver_id,
        created_at
    )
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- جدول المحادثات المخفية (للمستخدم الذي يريد اخفاء المحادثة فقط)
CREATE TABLE IF NOT EXISTS hidden_chats (
    user_id INT NOT NULL,
    hidden_with_id INT NOT NULL,
    PRIMARY KEY (user_id, hidden_with_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (hidden_with_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- جدول تسجيلات المستخدمين (كما هو)
CREATE TABLE IF NOT EXISTS registrations_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    quality VARCHAR(50) DEFAULT 'standard', -- جودة الحساب
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- جدول الستوري (كما هو)
CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    media_path VARCHAR(255) NOT NULL,
    caption TEXT DEFAULT NULL,
    media_type ENUM('image', 'video') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT(
        CURRENT_TIMESTAMP + INTERVAL 1 DAY
    ),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- جدول المحادثات المحذوفة (للسماح بحذف المحادثة عند مستخدم معين فقط)
CREATE TABLE IF NOT EXISTS deleted_chats (
    user_id INT NOT NULL,
    chat_with_id INT NOT NULL,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, chat_with_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (chat_with_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    media_path VARCHAR(255) NOT NULL,
    post_text TEXT,
    media_type ENUM('image', 'video') NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE
);
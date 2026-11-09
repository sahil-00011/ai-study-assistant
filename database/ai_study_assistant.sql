-- ============================================================
-- AI STUDY ASSISTANT - DATABASE SCHEMA
-- Compatible with MySQL 8.0+ and MariaDB 10.4+ (XAMPP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `ai_study_assistant`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `ai_study_assistant`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `avatar` VARCHAR(255) NULL,
  `academic_level` VARCHAR(50) NOT NULL DEFAULT 'Undergraduate',
  `institution` VARCHAR(150) NULL,
  `subject_interests` TEXT NULL,
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `remember_token` VARCHAR(64) NULL,
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_user_email` (`email`),
  INDEX `idx_user_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: user_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `theme_preference` ENUM('light', 'dark') NOT NULL DEFAULT 'light',
  `email_notifications` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: subjects
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `icon` VARCHAR(50) NOT NULL DEFAULT 'bi-book',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: chat_conversations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_conversations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT 'New Conversation',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_chat_conv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_conv_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: chat_messages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `conversation_id` INT NOT NULL,
  `sender` ENUM('user', 'assistant') NOT NULL,
  `message` MEDIUMTEXT NOT NULL,
  `tokens_used` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_chat_msg_conv` FOREIGN KEY (`conversation_id`) REFERENCES `chat_conversations` (`id`) ON DELETE CASCADE,
  INDEX `idx_msg_conv` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `topic` VARCHAR(200) NOT NULL,
  `academic_level` VARCHAR(50) NOT NULL DEFAULT 'College/University',
  `format` VARCHAR(50) NOT NULL DEFAULT 'detailed',
  `language` VARCHAR(50) NOT NULL DEFAULT 'English',
  `content` MEDIUMTEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notes_user` (`user_id`),
  INDEX `idx_notes_subject` (`subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: quizzes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quizzes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `topic` VARCHAR(200) NOT NULL,
  `difficulty` ENUM('easy', 'medium', 'hard') NOT NULL DEFAULT 'medium',
  `question_count` INT NOT NULL DEFAULT 5,
  `question_type` ENUM('mcq', 'true_false') NOT NULL DEFAULT 'mcq',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_quiz_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_quiz_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: quiz_questions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `question` TEXT NOT NULL,
  `options` JSON NOT NULL,
  `correct_answer` VARCHAR(255) NOT NULL,
  `explanation` TEXT NULL,
  `question_order` INT NOT NULL DEFAULT 1,
  CONSTRAINT `fk_qq_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  INDEX `idx_qq_quiz` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: quiz_attempts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quiz_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `total_questions` INT NOT NULL DEFAULT 0,
  `correct_answers` INT NOT NULL DEFAULT 0,
  `score_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `time_taken_seconds` INT NOT NULL DEFAULT 0,
  `completed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_qa_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_qa_user` (`user_id`),
  INDEX `idx_qa_quiz` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: quiz_answers
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quiz_answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `attempt_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `selected_answer` VARCHAR(255) NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_qans_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qans_question` FOREIGN KEY (`question_id`) REFERENCES `quiz_questions` (`id`) ON DELETE CASCADE,
  INDEX `idx_qans_attempt` (`attempt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: study_tasks
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `study_tasks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT 'General',
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `due_date` DATE NULL,
  `status` ENUM('pending', 'completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_tasks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_tasks_user` (`user_id`),
  INDEX `idx_tasks_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: pdf_documents
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pdf_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `stored_filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT NOT NULL DEFAULT 0,
  `extracted_text` LONGTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pdf_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_pdf_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: pdf_summaries
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pdf_summaries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `summary_type` VARCHAR(50) NOT NULL DEFAULT 'comprehensive',
  `summary_content` MEDIUMTEXT NOT NULL,
  `key_points` MEDIUMTEXT NULL,
  `revision_notes` MEDIUMTEXT NULL,
  `practice_questions` MEDIUMTEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_psum_doc` FOREIGN KEY (`document_id`) REFERENCES `pdf_documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_psum_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_psum_user` (`user_id`),
  INDEX `idx_psum_doc` (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: activity_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `activity_type` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_act_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_act_user` (`user_id`),
  INDEX `idx_act_type` (`activity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data: Sample Subjects
-- --------------------------------------------------------
INSERT INTO `subjects` (`name`, `code`, `description`, `icon`, `is_active`) VALUES
('Computer Science', 'CS101', 'Core computer science principles, hardware, and fundamentals', 'bi-laptop', 1),
('Data Structures & Algorithms', 'CS201', 'Arrays, linked lists, trees, graphs, sorting, and algorithmic complexity', 'bi-diagram-3', 1),
('Database Management Systems', 'CS301', 'Relational models, SQL, normalization, transactions, and indexing', 'bi-database', 1),
('Operating Systems', 'CS302', 'Processes, threads, CPU scheduling, synchronization, and memory management', 'bi-cpu', 1),
('Computer Networks', 'CS303', 'OSI model, TCP/IP, routing protocols, sockets, and network security', 'bi-hdd-network', 1),
('Software Engineering', 'CS304', 'SDLC, Agile, testing, system architecture, and design patterns', 'bi-kanban', 1),
('Machine Learning & AI', 'AI401', 'Supervised/unsupervised learning, neural networks, and NLP', 'bi-robot', 1),
('Web Technologies', 'IT205', 'HTML, CSS, JavaScript, PHP, REST APIs, and full-stack development', 'bi-code-slash', 1),
('Engineering Mathematics', 'MTH101', 'Calculus, linear algebra, probability, and discrete mathematics', 'bi-calculator', 1),
('Cybersecurity', 'CS405', 'Cryptography, network defense, vulnerabilities, and authentication', 'bi-shield-check', 1)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- --------------------------------------------------------
-- Seed Data: Default Admin User
-- Email: admin@studyassistant.ai
-- Password: Admin@12345
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `academic_level`, `institution`, `status`) VALUES
(1, 'System Administrator', 'admin@studyassistant.ai', '$2y$10$D7N/UBtRd9EiHrWX3QYcFuvr7BQVWkkoeR2c6ohF.7SdSFRRRX9Ci', 'admin', 'Faculty / Admin', 'AI Study Assistant Platform', 'active')
ON DUPLICATE KEY UPDATE `email`=`email`;

INSERT INTO `user_settings` (`user_id`, `theme_preference`, `email_notifications`) VALUES
(1, 'light', 1)
ON DUPLICATE KEY UPDATE `user_id`=`user_id`;


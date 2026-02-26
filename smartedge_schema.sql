-- ============================================================
-- SmartEdge ML Learning Sandbox - Complete Database Schema
-- Run this in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS `smartedge_ml`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `smartedge_ml`;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(100) NOT NULL,
  `email`        VARCHAR(150) NOT NULL UNIQUE,
  `password`     VARCHAR(255) NOT NULL,
  `role`         ENUM('admin','student') DEFAULT 'student',
  `avatar`       VARCHAR(255) DEFAULT NULL,
  `otp`          VARCHAR(6)   DEFAULT NULL,
  `otp_expiry`   DATETIME     DEFAULT NULL,
  `is_verified`  TINYINT(1)   DEFAULT 0,
  `is_active`    TINYINT(1)   DEFAULT 1,
  `xp_points`    INT UNSIGNED DEFAULT 0,
  `level`        INT UNSIGNED DEFAULT 1,
  `badges`       JSON         DEFAULT NULL,
  `last_login`   DATETIME     DEFAULT NULL,
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ESP32 DEVICES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `devices` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id`    VARCHAR(50)  NOT NULL UNIQUE COMMENT 'ESP32 unique hardware ID',
  `device_name`  VARCHAR(100) NOT NULL,
  `description`  TEXT         DEFAULT NULL,
  `owner_id`     INT UNSIGNED DEFAULT NULL,
  `is_approved`  TINYINT(1)   DEFAULT 0,
  `is_online`    TINYINT(1)   DEFAULT 0,
  `firmware_ver` VARCHAR(20)  DEFAULT NULL,
  `ip_address`   VARCHAR(45)  DEFAULT NULL,
  `last_seen`    DATETIME     DEFAULT NULL,
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SENSOR DATA TABLE  (MQTT → Backend → DB)
-- ============================================================
CREATE TABLE IF NOT EXISTS `sensor_data` (
  `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id`    VARCHAR(50)  NOT NULL,
  `topic`        VARCHAR(100) NOT NULL,
  `sensor_type`  ENUM('water_level','mic','temperature','humidity','generic') NOT NULL,
  `value`        FLOAT        NOT NULL,
  `unit`         VARCHAR(20)  DEFAULT NULL,
  `raw_payload`  TEXT         DEFAULT NULL,
  `session_id`   VARCHAR(50)  DEFAULT NULL COMMENT 'Groups related readings into sessions',
  `recorded_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_device` (`device_id`),
  INDEX `idx_sensor_type` (`sensor_type`),
  INDEX `idx_recorded_at` (`recorded_at`),
  INDEX `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ML EXPERIMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `experiments` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED DEFAULT NULL,
  `title`           VARCHAR(150) NOT NULL,
  `description`     TEXT         DEFAULT NULL,
  `experiment_type` ENUM('sound_fan','water_level','predictive_ml','custom') DEFAULT 'custom',
  `level`           TINYINT UNSIGNED DEFAULT 1,
  `status`          ENUM('draft','running','completed','failed') DEFAULT 'draft',
  `config`          JSON         DEFAULT NULL COMMENT 'learning_rate, threshold, epochs etc.',
  `result_data`     JSON         DEFAULT NULL COMMENT 'Accuracy, loss, predictions etc.',
  `accuracy`        FLOAT        DEFAULT NULL,
  `dataset_id`      INT UNSIGNED DEFAULT NULL,
  `session_id`      VARCHAR(50)  DEFAULT NULL,
  `started_at`      DATETIME     DEFAULT NULL,
  `completed_at`    DATETIME     DEFAULT NULL,
  `created_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ML TRAINING STEPS  (for gradient descent animation)
-- ============================================================
CREATE TABLE IF NOT EXISTS `training_steps` (
  `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `experiment_id` INT UNSIGNED NOT NULL,
  `epoch`         INT UNSIGNED NOT NULL,
  `loss`          FLOAT        NOT NULL,
  `accuracy`      FLOAT        NOT NULL,
  `val_loss`      FLOAT        DEFAULT NULL,
  `val_accuracy`  FLOAT        DEFAULT NULL,
  `weights`       JSON         DEFAULT NULL,
  `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_experiment` (`experiment_id`),
  FOREIGN KEY (`experiment_id`) REFERENCES `experiments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATASETS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `datasets` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `device_id`   VARCHAR(50)  DEFAULT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `source`      ENUM('mqtt','upload','generated') DEFAULT 'mqtt',
  `file_path`   VARCHAR(255) DEFAULT NULL,
  `row_count`   INT UNSIGNED DEFAULT 0,
  `features`    JSON         DEFAULT NULL,
  `session_id`  VARCHAR(50)  DEFAULT NULL,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- MQTT COMMANDS LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `mqtt_commands` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id`   VARCHAR(50)  NOT NULL,
  `topic`       VARCHAR(100) NOT NULL,
  `command`     VARCHAR(50)  NOT NULL,
  `payload`     TEXT         DEFAULT NULL,
  `issued_by`   INT UNSIGNED DEFAULT NULL COMMENT 'user_id or NULL for ML auto',
  `source`      ENUM('user','ml_model','automation') DEFAULT 'user',
  `status`      ENUM('sent','acknowledged','failed') DEFAULT 'sent',
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_device` (`device_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- HARDWARE ACTIONS LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `hardware_actions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id`   VARCHAR(50)  NOT NULL,
  `actuator`    ENUM('fan','pump','servo','led','relay') NOT NULL,
  `action`      VARCHAR(50)  NOT NULL COMMENT 'ON, OFF, angle:90, speed:50 etc.',
  `triggered_by`ENUM('manual','ml_prediction','automation','schedule') DEFAULT 'manual',
  `confidence`  FLOAT        DEFAULT NULL COMMENT 'ML prediction confidence 0.0 to 1.0',
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_device` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- REPLAY SESSIONS TABLE  (Learning Replay Mode)
-- ============================================================
CREATE TABLE IF NOT EXISTS `replay_sessions` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `experiment_id` INT UNSIGNED DEFAULT NULL,
  `session_id`  VARCHAR(50)  NOT NULL UNIQUE,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `data_points` INT UNSIGNED DEFAULT 0,
  `duration_ms` INT UNSIGNED DEFAULT 0,
  `file_path`   VARCHAR(255) DEFAULT NULL,
  `is_public`   TINYINT(1)   DEFAULT 0,
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- CHATBOT HISTORY
-- ============================================================
CREATE TABLE IF NOT EXISTS `chatbot_history` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `session_id`  VARCHAR(50)  NOT NULL,
  `role`        ENUM('user','assistant') NOT NULL,
  `message`     TEXT         NOT NULL,
  `context`     JSON         DEFAULT NULL COMMENT 'ML params context at time of message',
  `created_at`  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_session` (`user_id`, `session_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- GAMIFICATION - USER PROGRESS
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_progress` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL UNIQUE,
  `level_1_done` TINYINT(1) DEFAULT 0,
  `level_2_done` TINYINT(1) DEFAULT 0,
  `level_3_done` TINYINT(1) DEFAULT 0,
  `total_xp`     INT UNSIGNED DEFAULT 0,
  `current_level`TINYINT UNSIGNED DEFAULT 1,
  `badges`       JSON DEFAULT NULL,
  `streak_days`  INT UNSIGNED DEFAULT 0,
  `last_active`  DATE DEFAULT NULL,
  `updated_at`   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED DEFAULT NULL COMMENT 'NULL = broadcast to all',
  `title`      VARCHAR(150) NOT NULL,
  `message`    TEXT NOT NULL,
  `type`       ENUM('info','success','warning','error') DEFAULT 'info',
  `is_read`    TINYINT(1) DEFAULT 0,
  `link`       VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ACTIVITY LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `action`      VARCHAR(100) NOT NULL,
  `details`     TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `user_agent`  VARCHAR(255) DEFAULT NULL,
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PIPELINE EXECUTION LOG  (for visualizer)
-- ============================================================
CREATE TABLE IF NOT EXISTS `pipeline_executions` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `experiment_id` INT UNSIGNED DEFAULT NULL,
  `stage`         ENUM('sensor','mqtt','backend','ml_model','prediction','hardware') NOT NULL,
  `status`        ENUM('running','completed','failed') DEFAULT 'running',
  `data_snapshot` JSON DEFAULT NULL,
  `latency_ms`    INT UNSIGNED DEFAULT 0,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DEFAULT ADMIN USER  (password: Admin@123)
-- Change password after first login!
-- ============================================================
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`, `is_verified`, `is_active`, `created_at`)
VALUES (
  'Super Admin',
  'admin@smartedge.local',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin',
  1,
  1,
  NOW()
);

-- ============================================================
-- SAMPLE ESP32 DEVICE
-- ============================================================
INSERT IGNORE INTO `devices` (`device_id`, `device_name`, `description`, `is_approved`, `is_online`)
VALUES ('ESP32_001', 'Main Lab ESP32', 'Primary sensor node with water pump, fan, mic, servo', 1, 0);

-- Create user_progress for admin
INSERT IGNORE INTO `user_progress` (`user_id`, `total_xp`) 
SELECT id, 0 FROM users WHERE email = 'admin@smartedge.local';

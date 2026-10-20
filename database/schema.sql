-- StudentHub schema (MySQL 5.7+ / MariaDB 10.3+)
-- Select your database first (e.g. studenthub_db) and then import this file.
-- setup.php runs this file automatically.

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(191) NOT NULL,
    mobile        VARCHAR(20)  NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE         DEFAULT NULL,
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS courses (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    course_name VARCHAR(100) NOT NULL,
    description TEXT         NOT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enrollments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    course_id   INT UNSIGNED NOT NULL,
    enrolled_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_enrollment (user_id, course_id),
    KEY idx_enrollments_course (course_id),
    CONSTRAINT fk_enroll_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_enroll_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

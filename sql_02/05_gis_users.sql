SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =====================================================
-- ตารางที่ 5: gis_users (ผู้ใช้งาน)
-- ★ Import ก่อนตารางอื่นในกลุ่ม Auth
-- ไม่มี Foreign Key
-- =====================================================

DROP TABLE IF EXISTS `gis_users`;

CREATE TABLE `gis_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'password_hash() PHP',
  `full_name` VARCHAR(100) NOT NULL COMMENT 'ชื่อ-นามสกุล',
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(500) DEFAULT NULL COMMENT 'รูปโปรไฟล์',
  `department` VARCHAR(100) DEFAULT NULL COMMENT 'แผนก/ฝ่าย',
  `position` VARCHAR(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '1=ใช้งาน 0=ระงับ',
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `login_count` INT(11) DEFAULT 0,
  `password_changed_at` DATETIME DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ผู้ใช้งานระบบ GIS';

-- =====================================================
-- ผู้ใช้เริ่มต้น
-- password ทุกคน = "P@ssw0rd" (เปลี่ยนหลัง login ครั้งแรก)
-- hash ด้วย password_hash('P@ssw0rd', PASSWORD_BCRYPT)
-- =====================================================
INSERT INTO `gis_users` (`id`, `username`, `email`, `password_hash`, `full_name`, `department`, `position`, `is_active`) VALUES
(1, 'superadmin', 'admin@rangsit.go.th', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'ศูนย์เทคโนโลยี', 'Super Admin', 1),
(2, 'admin_it', 'it@rangsit.go.th', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'เจ้าหน้าที่ IT', 'ศูนย์เทคโนโลยี', 'Admin', 1),
(3, 'editor_infra', 'infra@rangsit.go.th', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'เจ้าหน้าที่สาธารณูปโภค', 'กองช่าง', 'Editor', 1),
(4, 'viewer_01', 'viewer@rangsit.go.th', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูข้อมูล', 'สำนักปลัด', 'Viewer', 1);

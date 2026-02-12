-- =====================================================
-- ตารางที่ 11A: gis_sessions (จัดการ Session login)
-- FK → gis_users.id
-- =====================================================

DROP TABLE IF EXISTS `gis_sessions`;

CREATE TABLE `gis_sessions` (
  `id` VARCHAR(128) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `payload` TEXT DEFAULT NULL,
  `last_activity` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_activity` (`last_activity`),
  CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `gis_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Session ผู้ใช้งาน';


-- =====================================================
-- ตารางที่ 11B: gis_audit_log (บันทึกทุกการกระทำ)
-- FK → gis_users.id
--
-- ★ สำคัญมาก: ใครทำอะไร เมื่อไหร่ กับข้อมูลอะไร
-- =====================================================

DROP TABLE IF EXISTS `gis_audit_log`;

CREATE TABLE `gis_audit_log` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL COMMENT 'ใครทำ (NULL=system)',
  `action` VARCHAR(50) NOT NULL COMMENT 'login/logout/create/edit/delete/import/export/assign_role',
  `resource_type` VARCHAR(50) DEFAULT NULL COMMENT 'marker/layer/category/user/role',
  `resource_id` INT(11) DEFAULT NULL COMMENT 'ID ของ resource',
  `resource_title` VARCHAR(255) DEFAULT NULL COMMENT 'ชื่อ resource ณ ตอนนั้น',
  `details` JSON DEFAULT NULL COMMENT 'รายละเอียดการเปลี่ยนแปลง (old→new)',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_resource` (`resource_type`, `resource_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `gis_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit Log บันทึกทุกการกระทำ';

-- ตัวอย่าง audit log
INSERT INTO `gis_audit_log` (`user_id`, `action`, `resource_type`, `resource_id`, `resource_title`, `details`, `ip_address`) VALUES
(1, 'system_init', 'system', NULL, 'ติดตั้งระบบ GIS', '{"version":"1.0","tables_created":11}', '127.0.0.1');

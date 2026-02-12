-- =====================================================
-- ตารางที่ 8: gis_user_roles (ผูก User ↔ Role)
-- FK → gis_users.id + gis_roles.id
-- ★ 1 user มีได้หลาย role
-- =====================================================

DROP TABLE IF EXISTS `gis_user_roles`;

CREATE TABLE `gis_user_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `role_id` INT(11) NOT NULL,
  `assigned_by` INT(11) DEFAULT NULL COMMENT 'ใครเป็นคนกำหนด',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  KEY `idx_role` (`role_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `gis_users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `gis_roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ผูกผู้ใช้กับบทบาท (M:M)';

-- กำหนด role ให้ผู้ใช้เริ่มต้น
INSERT INTO `gis_user_roles` (`user_id`, `role_id`, `assigned_by`) VALUES
(1, 1, NULL),   -- superadmin   → Super Admin
(2, 2, 1),      -- admin_it     → Admin
(3, 3, 1),      -- editor_infra → Layer Manager
(4, 5, 1);      -- viewer_01    → Viewer

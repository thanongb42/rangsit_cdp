-- =====================================================
-- ตารางที่ 6: gis_roles (บทบาท/ตำแหน่ง)
-- ★ ไม่มี Foreign Key — import ก่อน role assignments
-- =====================================================

DROP TABLE IF EXISTS `gis_roles`;

CREATE TABLE `gis_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL,
  `role_slug` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `is_system` TINYINT(1) DEFAULT 0 COMMENT '1=ห้ามลบ (system role)',
  `priority` INT(11) DEFAULT 0 COMMENT 'ยิ่งสูง=สิทธิ์เยอะ ใช้ตัดสินเมื่อมีหลาย role',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_slug` (`role_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='บทบาทผู้ใช้งาน';

-- =====================================================
-- Roles เริ่มต้น — ออกแบบ 5 ระดับ
-- =====================================================
INSERT INTO `gis_roles` (`id`, `role_name`, `role_slug`, `description`, `is_system`, `priority`) VALUES
(1, 'Super Admin',    'super_admin',    'สิทธิ์สูงสุด จัดการทุกอย่างได้ รวมถึง user/role', 1, 100),
(2, 'Admin',          'admin',          'จัดการ layer/marker/category ทั้งหมด แต่ไม่จัดการ role', 1, 80),
(3, 'Layer Manager',  'layer_manager',  'จัดการ layer ที่ได้รับมอบหมาย เพิ่ม/แก้/ลบ marker ได้', 1, 60),
(4, 'Editor',         'editor',         'เพิ่ม/แก้ไข marker ใน layer ที่ได้รับสิทธิ์ แต่ลบไม่ได้', 1, 40),
(5, 'Viewer',         'viewer',         'ดูข้อมูลและแผนที่เฉพาะ layer/category ที่ได้รับสิทธิ์', 1, 20);

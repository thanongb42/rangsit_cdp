-- =====================================================
-- ตารางที่ 10A: gis_user_layer_access
-- ★ จำกัดสิทธิ์ยิบย่อยระดับ Layer ต่อ User
-- FK → gis_users.id + gis_layers.id
--
-- หลักการ:
-- - Super Admin + Admin = เข้าถึงทุก layer (ไม่ต้องมี record)
-- - Layer Manager / Editor / Viewer = ถ้าไม่มี record = ไม่เห็น layer นั้น
-- - ถ้ามี record = เห็น layer + ทำได้ตาม permissions ที่กำหนด
-- =====================================================

DROP TABLE IF EXISTS `gis_user_layer_access`;

CREATE TABLE `gis_user_layer_access` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `layer_id` INT(11) NOT NULL,
  `can_view` TINYINT(1) DEFAULT 1 COMMENT 'ดูได้',
  `can_create` TINYINT(1) DEFAULT 0 COMMENT 'เพิ่มหมุดได้',
  `can_edit` TINYINT(1) DEFAULT 0 COMMENT 'แก้ไขหมุดได้',
  `can_delete` TINYINT(1) DEFAULT 0 COMMENT 'ลบหมุดได้',
  `can_export` TINYINT(1) DEFAULT 0 COMMENT 'Export ได้',
  `can_import` TINYINT(1) DEFAULT 0 COMMENT 'Import ได้',
  `granted_by` INT(11) DEFAULT NULL COMMENT 'ใครให้สิทธิ์',
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL COMMENT 'หมดอายุเมื่อไหร่ (NULL=ไม่หมด)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_layer` (`user_id`, `layer_id`),
  KEY `idx_layer` (`layer_id`),
  CONSTRAINT `fk_ula_user` FOREIGN KEY (`user_id`) REFERENCES `gis_users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ula_layer` FOREIGN KEY (`layer_id`) REFERENCES `gis_layers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='สิทธิ์เข้าถึง Layer รายบุคคล';

-- ตัวอย่าง: editor_infra (user=3) เข้าถึง layer ลำโพง ได้ครบ
--           editor_infra (user=3) เข้าถึง layer ตู้น้ำ ดู+แก้ ได้ แต่ลบไม่ได้
INSERT INTO `gis_user_layer_access` (`user_id`, `layer_id`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_export`, `can_import`, `granted_by`) VALUES
(3, 1, 1, 1, 1, 1, 1, 1, 1),  -- editor_infra → ลำโพง: full
(3, 2, 1, 1, 1, 0, 1, 0, 1),  -- editor_infra → ตู้น้ำ: ดู+เพิ่ม+แก้+export (ลบ/import ไม่ได้)
(4, 1, 1, 0, 0, 0, 1, 0, 1),  -- viewer_01 → ลำโพง: ดู+export เท่านั้น
(4, 2, 1, 0, 0, 0, 1, 0, 1);  -- viewer_01 → ตู้น้ำ: ดู+export เท่านั้น


-- =====================================================
-- ตารางที่ 10B: gis_user_category_access
-- ★ จำกัดสิทธิ์ระดับ Category ต่อ User
-- FK → gis_users.id + gis_categories.id
--
-- หลักการ:
-- - ถ้า user มี category access = เห็นทุก layer ใน category นั้น
--   (override layer access เฉพาะ can_view)
-- - ใช้เป็น shortcut: แทนที่จะกำหนดทีละ layer
--   กำหนดทั้ง category ทีเดียว
-- =====================================================

DROP TABLE IF EXISTS `gis_user_category_access`;

CREATE TABLE `gis_user_category_access` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `category_id` INT(11) NOT NULL,
  `can_view` TINYINT(1) DEFAULT 1,
  `can_manage_layers` TINYINT(1) DEFAULT 0 COMMENT 'จัดการ layer ใน category นี้ได้',
  `can_create_markers` TINYINT(1) DEFAULT 0 COMMENT 'เพิ่มหมุดใน layer ทุกอันของ category',
  `can_edit_markers` TINYINT(1) DEFAULT 0,
  `can_delete_markers` TINYINT(1) DEFAULT 0,
  `granted_by` INT(11) DEFAULT NULL,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_category` (`user_id`, `category_id`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `fk_uca_user` FOREIGN KEY (`user_id`) REFERENCES `gis_users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uca_cat` FOREIGN KEY (`category_id`) REFERENCES `gis_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='สิทธิ์เข้าถึง Category รายบุคคล';

-- ตัวอย่าง: editor_infra ดูแล category ระบบสาธารณูปโภค ทั้งหมด
INSERT INTO `gis_user_category_access` (`user_id`, `category_id`, `can_view`, `can_manage_layers`, `can_create_markers`, `can_edit_markers`, `can_delete_markers`, `granted_by`) VALUES
(3, 1, 1, 1, 1, 1, 1, 1),  -- editor_infra → สาธารณูปโภค: full
(4, 1, 1, 0, 0, 0, 0, 1),  -- viewer_01 → สาธารณูปโภค: ดูเท่านั้น
(4, 2, 1, 0, 0, 0, 0, 1);  -- viewer_01 → บริการสาธารณะ: ดูเท่านั้น

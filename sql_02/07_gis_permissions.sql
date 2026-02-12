-- =====================================================
-- ตารางที่ 7: gis_permissions (รายการสิทธิ์ย่อย)
-- ★ ไม่มี Foreign Key — import ก่อน role_permissions
--
-- ออกแบบเป็น resource:action
-- resource = category, layer, marker, image, user, role, system
-- action   = view, create, edit, delete, manage, export, import
-- =====================================================

DROP TABLE IF EXISTS `gis_permissions`;

CREATE TABLE `gis_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `permission_key` VARCHAR(80) NOT NULL COMMENT 'resource:action เช่น layer:create',
  `resource` VARCHAR(30) NOT NULL COMMENT 'กลุ่ม resource',
  `action` VARCHAR(30) NOT NULL COMMENT 'action',
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permission_key` (`permission_key`),
  KEY `idx_resource` (`resource`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='รายการสิทธิ์ทั้งหมด';

INSERT INTO `gis_permissions` (`id`, `permission_key`, `resource`, `action`, `description`) VALUES
-- === Category ===
( 1, 'category:view',     'category', 'view',    'ดูรายการหมวดหมู่'),
( 2, 'category:create',   'category', 'create',  'สร้างหมวดหมู่ใหม่'),
( 3, 'category:edit',     'category', 'edit',    'แก้ไขหมวดหมู่'),
( 4, 'category:delete',   'category', 'delete',  'ลบหมวดหมู่'),

-- === Layer ===
( 5, 'layer:view',        'layer',    'view',    'ดูชั้นข้อมูล'),
( 6, 'layer:create',      'layer',    'create',  'สร้าง Layer ใหม่'),
( 7, 'layer:edit',        'layer',    'edit',    'แก้ไขการตั้งค่า Layer'),
( 8, 'layer:delete',      'layer',    'delete',  'ลบ Layer'),
( 9, 'layer:toggle',      'layer',    'toggle',  'เปิด/ปิดการแสดง Layer'),

-- === Marker ===
(10, 'marker:view',       'marker',   'view',    'ดูหมุดบนแผนที่'),
(11, 'marker:create',     'marker',   'create',  'ปักหมุดใหม่'),
(12, 'marker:edit',       'marker',   'edit',    'แก้ไขข้อมูลหมุด'),
(13, 'marker:delete',     'marker',   'delete',  'ลบหมุด'),
(14, 'marker:import',     'marker',   'import',  'Import ข้อมูลหมุดจาก Excel/CSV'),
(15, 'marker:export',     'marker',   'export',  'Export ข้อมูลหมุดเป็น Excel/GeoJSON/KML'),

-- === Image ===
(16, 'image:upload',      'image',    'upload',  'อัปโหลดรูปภาพแนบหมุด'),
(17, 'image:delete',      'image',    'delete',  'ลบรูปภาพ'),

-- === User Management ===
(18, 'user:view',         'user',     'view',    'ดูรายชื่อผู้ใช้'),
(19, 'user:create',       'user',     'create',  'สร้างผู้ใช้ใหม่'),
(20, 'user:edit',         'user',     'edit',    'แก้ไขข้อมูลผู้ใช้'),
(21, 'user:delete',       'user',     'delete',  'ลบ/ระงับผู้ใช้'),
(22, 'user:assign_role',  'user',     'assign_role', 'กำหนด Role ให้ผู้ใช้'),

-- === Role Management ===
(23, 'role:view',         'role',     'view',    'ดู Role ทั้งหมด'),
(24, 'role:create',       'role',     'create',  'สร้าง Role ใหม่'),
(25, 'role:edit',         'role',     'edit',    'แก้ไข Role'),
(26, 'role:delete',       'role',     'delete',  'ลบ Role'),
(27, 'role:assign_perm',  'role',     'assign_perm', 'กำหนด Permission ให้ Role'),

-- === System ===
(28, 'system:settings',   'system',   'settings', 'ตั้งค่าระบบ'),
(29, 'system:audit_log',  'system',   'audit_log', 'ดู Audit Log'),
(30, 'system:backup',     'system',   'backup',   'สำรองข้อมูล');

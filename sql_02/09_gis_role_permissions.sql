-- =====================================================
-- ตารางที่ 9: gis_role_permissions (ผูก Role ↔ Permission)
-- FK → gis_roles.id + gis_permissions.id
-- ★ กำหนดว่าแต่ละ Role ทำอะไรได้บ้าง
-- =====================================================

DROP TABLE IF EXISTS `gis_role_permissions`;

CREATE TABLE `gis_role_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_id` INT(11) NOT NULL,
  `permission_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_perm` (`role_id`, `permission_id`),
  KEY `idx_perm` (`permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `gis_roles`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `gis_permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='สิทธิ์ที่ Role มี (M:M)';

-- =====================================================
-- Super Admin (role=1) → ได้ทุกสิทธิ์ (1-30)
-- =====================================================
INSERT INTO `gis_role_permissions` (`role_id`, `permission_id`) VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),
(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),
(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30);

-- =====================================================
-- Admin (role=2) → ทุกอย่างยกเว้น role management + system
-- =====================================================
INSERT INTO `gis_role_permissions` (`role_id`, `permission_id`) VALUES
(2,1),(2,2),(2,3),(2,4),       -- category: ครบ
(2,5),(2,6),(2,7),(2,8),(2,9), -- layer: ครบ
(2,10),(2,11),(2,12),(2,13),(2,14),(2,15), -- marker: ครบ
(2,16),(2,17),                 -- image: ครบ
(2,18),(2,19),(2,20),(2,21),(2,22), -- user: ครบ
(2,23),                        -- role: ดูได้อย่างเดียว
(2,29);                        -- system: ดู audit log ได้

-- =====================================================
-- Layer Manager (role=3) → จัดการ layer+marker ใน scope ที่ได้รับ
-- =====================================================
INSERT INTO `gis_role_permissions` (`role_id`, `permission_id`) VALUES
(3,1),                          -- category: ดู
(3,5),(3,7),(3,9),              -- layer: ดู + แก้ไข + toggle
(3,10),(3,11),(3,12),(3,13),(3,14),(3,15), -- marker: ครบ
(3,16),(3,17);                  -- image: ครบ

-- =====================================================
-- Editor (role=4) → เพิ่ม/แก้ marker ในที่ได้รับสิทธิ์ ลบไม่ได้
-- =====================================================
INSERT INTO `gis_role_permissions` (`role_id`, `permission_id`) VALUES
(4,1),                          -- category: ดู
(4,5),                          -- layer: ดู
(4,10),(4,11),(4,12),           -- marker: ดู + เพิ่ม + แก้ (ลบไม่ได้)
(4,16);                         -- image: upload ได้

-- =====================================================
-- Viewer (role=5) → ดูอย่างเดียว + export
-- =====================================================
INSERT INTO `gis_role_permissions` (`role_id`, `permission_id`) VALUES
(5,1),                          -- category: ดู
(5,5),                          -- layer: ดู
(5,10),                         -- marker: ดู
(5,15);                         -- marker: export ได้

-- =====================================================
-- ตารางที่ 1: gis_categories (หมวดหมู่)
-- Import ก่อนเพราะไม่มี Foreign Key
-- =====================================================

DROP TABLE IF EXISTS `gis_categories`;

CREATE TABLE `gis_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'ชื่อหมวดหมู่',
  `slug` VARCHAR(100) NOT NULL COMMENT 'URL slug',
  `color` VARCHAR(7) DEFAULT '#94a3b8' COMMENT 'สี HEX',
  `icon_class` VARCHAR(50) DEFAULT NULL COMMENT 'Font Awesome class',
  `description` TEXT DEFAULT NULL,
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='หมวดหมู่ชั้นข้อมูล GIS';

-- ข้อมูลเริ่มต้น
INSERT INTO `gis_categories` (`id`, `name`, `slug`, `color`, `icon_class`, `description`, `sort_order`) VALUES
(1, 'ระบบสาธารณูปโภค', 'infrastructure', '#3b82f6', 'fa-tools', 'ระบบสาธารณูปโภคพื้นฐาน เช่น ไฟฟ้า ประปา เสียงตามสาย', 1),
(2, 'บริการสาธารณะ', 'public-service', '#10b981', 'fa-hand-holding-heart', 'จุดบริการประชาชน เช่น ตู้น้ำดื่ม ศูนย์บริการ', 2),
(3, 'สถานที่สำคัญ', 'landmark', '#f59e0b', 'fa-landmark', 'สถานที่สำคัญ วัด โรงเรียน สำนักงาน', 3),
(4, 'ความปลอดภัย', 'safety', '#ef4444', 'fa-shield-alt', 'กล้อง CCTV ป้อมยาม จุดตรวจ', 4),
(5, 'สิ่งแวดล้อม', 'environment', '#8b5cf6', 'fa-leaf', 'สวนสาธารณะ จุดคัดแยกขยะ พื้นที่สีเขียว', 5);

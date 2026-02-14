SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET collation_connection = 'utf8mb4_general_ci';

-- =====================================================
-- Rangsit CDP - Full Database Re-import
-- Run this file in phpMyAdmin to fix Thai encoding
-- =====================================================

ALTER DATABASE `rangsit_cdp` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 0;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='หมวดหมู่ชั้นข้อมูล GIS';

-- ข้อมูลเริ่มต้น
INSERT INTO `gis_categories` (`id`, `name`, `slug`, `color`, `icon_class`, `description`, `sort_order`) VALUES
(1, 'ระบบสาธารณูปโภค', 'infrastructure', '#3b82f6', 'fa-tools', 'ระบบสาธารณูปโภคพื้นฐาน เช่น ไฟฟ้า ประปา เสียงตามสาย', 1),
(2, 'บริการสาธารณะ', 'public-service', '#10b981', 'fa-hand-holding-heart', 'จุดบริการประชาชน เช่น ตู้น้ำดื่ม ศูนย์บริการ', 2),
(3, 'สถานที่สำคัญ', 'landmark', '#f59e0b', 'fa-landmark', 'สถานที่สำคัญ วัด โรงเรียน สำนักงาน', 3),
(4, 'ความปลอดภัย', 'safety', '#ef4444', 'fa-shield-alt', 'กล้อง CCTV ป้อมยาม จุดตรวจ', 4),
(5, 'สิ่งแวดล้อม', 'environment', '#8b5cf6', 'fa-leaf', 'สวนสาธารณะ จุดคัดแยกขยะ พื้นที่สีเขียว', 5);

-- =====================================================
-- ตารางที่ 2: gis_layers (ชั้นข้อมูลแผนที่)
-- Import หลัง gis_categories
-- FK → gis_categories.id
-- =====================================================

DROP TABLE IF EXISTS `gis_layers`;

CREATE TABLE `gis_layers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `category_id` INT(11) DEFAULT NULL COMMENT 'หมวดหมู่',
  `layer_name` VARCHAR(100) NOT NULL COMMENT 'ชื่อ Layer',
  `layer_slug` VARCHAR(100) NOT NULL COMMENT 'URL slug',
  `description` TEXT DEFAULT NULL,
  `icon_class` VARCHAR(50) DEFAULT 'fa-map-marker-alt' COMMENT 'Font Awesome icon',
  `marker_color` VARCHAR(7) DEFAULT '#3b82f6' COMMENT 'สี HEX ของหมุด',
  `marker_shape` ENUM('circle','square','diamond','star') DEFAULT 'circle',
  `is_visible` TINYINT(1) DEFAULT 1 COMMENT '1=แสดง 0=ซ่อน',
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_layer_slug` (`layer_slug`),
  KEY `idx_category` (`category_id`),
  CONSTRAINT `fk_layer_category` FOREIGN KEY (`category_id`) REFERENCES `gis_categories`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ชั้นข้อมูลแผนที่ GIS';

-- ข้อมูลเริ่มต้น: 2 layer ที่มีอยู่แล้ว
INSERT INTO `gis_layers` (`id`, `category_id`, `layer_name`, `layer_slug`, `description`, `icon_class`, `marker_color`, `marker_shape`, `sort_order`) VALUES
(1, 1, 'จุดติดตั้งระบบเสียงตามสาย', 'speakers', 'ระบบกระจายเสียงชนิดไร้สาย เทศบาลเมืองรังสิต', 'fa-broadcast-tower', '#6c5ce7', 'circle', 1),
(2, 2, 'จุดติดตั้งตู้น้ำดื่ม', 'water-kiosks', 'ตู้น้ำดื่มสาธารณะ เทศบาลเมืองรังสิต', 'fa-tint', '#00b894', 'square', 2);

-- =====================================================
-- ตารางที่ 3: gis_markers (หมุดทุก Layer รวมกัน)
-- Import หลัง gis_layers
-- FK → gis_layers.id
-- ★ มี SPATIAL INDEX สำหรับค้นหาเร็ว
-- =====================================================

DROP TABLE IF EXISTS `gis_markers`;

CREATE TABLE `gis_markers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `layer_id` INT(11) NOT NULL COMMENT 'สังกัด Layer ไหน',
  `title` VARCHAR(255) NOT NULL COMMENT 'ชื่อจุด',
  `description` TEXT DEFAULT NULL COMMENT 'รายละเอียด',
  `latitude` DOUBLE NOT NULL,
  `longitude` DOUBLE NOT NULL,
  `coordinates` POINT NOT NULL SRID 4326 COMMENT 'Spatial Point',
  `properties` JSON DEFAULT NULL COMMENT 'ข้อมูลเสริมเฉพาะ layer (เช่น kiosk_code, device_count)',
  `status` ENUM('active','inactive','maintenance') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  SPATIAL INDEX `idx_coordinates` (`coordinates`),
  KEY `idx_layer_status` (`layer_id`, `status`),
  CONSTRAINT `fk_marker_layer` FOREIGN KEY (`layer_id`) REFERENCES `gis_layers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='หมุดพิกัด GIS ทุก Layer';

-- =====================================================
-- Migrate ข้อมูลลำโพง 110 จุด → layer_id = 1
-- =====================================================
INSERT INTO `gis_markers` (`layer_id`, `title`, `description`, `latitude`, `longitude`, `coordinates`, `properties`, `status`) VALUES
(1, 'จุดที่ 1', 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 13.986146, 100.608983, ST_GeomFromText('POINT(100.608983 13.986146)', 4326), '{"zone_group":"ซอยรังสิต-ปทุมธานี17","device_count":5,"point_number":1}', 'active'),
(1, 'จุดที่ 2', 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 13.985542, 100.609037, ST_GeomFromText('POINT(100.609037 13.985542)', 4326), '{"zone_group":"ซอยรังสิต-ปทุมธานี17","device_count":5,"point_number":2}', 'active'),
(1, 'จุดที่ 3', 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 13.984684, 100.609135, ST_GeomFromText('POINT(100.609135 13.984684)', 4326), '{"zone_group":"ซอยรังสิต-ปทุมธานี17","device_count":5,"point_number":3}', 'active'),
(1, 'จุดที่ 4', 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 13.984301, 100.609115, ST_GeomFromText('POINT(100.609115 13.984301)', 4326), '{"zone_group":"ซอยรังสิต-ปทุมธานี17","device_count":5,"point_number":4}', 'active'),
(1, 'จุดที่ 5', 'ซอยรังสิต-ปทุมธานี17 มีทั้งหมด5ตัว', 13.983539, 100.609219, ST_GeomFromText('POINT(100.609219 13.983539)', 4326), '{"zone_group":"ซอยรังสิต-ปทุมธานี17","device_count":5,"point_number":5}', 'active'),
(1, 'จุดที่ 6', 'ชุมชนซอย83 11ตัว', 13.973244, 100.630384, ST_GeomFromText('POINT(100.630384 13.973244)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":6}', 'active'),
(1, 'จุดที่ 7', 'ชุมชนซอย83 11ตัว', 13.975583, 100.630200, ST_GeomFromText('POINT(100.630200 13.975583)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":7}', 'active'),
(1, 'จุดที่ 8', 'ชุมชนซอย83 11ตัว', 13.977291, 100.630067, ST_GeomFromText('POINT(100.630067 13.977291)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":8}', 'active'),
(1, 'จุดที่ 9', 'ชุมชนซอย83 11ตัว', 13.978779, 100.629961, ST_GeomFromText('POINT(100.629961 13.978779)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":9}', 'active'),
(1, 'จุดที่ 10', 'ชุมชนซอย83 11ตัว', 13.981234, 100.629677, ST_GeomFromText('POINT(100.629677 13.981234)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":10}', 'active'),
(1, 'จุดที่ 11', 'ชุมชนซอย83 11ตัว', 13.983062, 100.629544, ST_GeomFromText('POINT(100.629544 13.983062)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":11}', 'active'),
(1, 'จุดที่ 12', 'ชุมชนซอย83 11ตัว', 13.984465, 100.629409, ST_GeomFromText('POINT(100.629409 13.984465)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":12}', 'active'),
(1, 'จุดที่ 13', 'ชุมชนซอย83 11ตัว', 13.978666, 100.629492, ST_GeomFromText('POINT(100.629492 13.978666)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":13}', 'active'),
(1, 'จุดที่ 14', 'ชุมชนซอย83 11ตัว', 13.977364, 100.629619, ST_GeomFromText('POINT(100.629619 13.977364)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":14}', 'active'),
(1, 'จุดที่ 15', 'ชุมชนซอย83 11ตัว', 13.974967, 100.629812, ST_GeomFromText('POINT(100.629812 13.974967)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":15}', 'active'),
(1, 'จุดที่ 16', 'ชุมชนซอย83 11ตัว', 13.973008, 100.629958, ST_GeomFromText('POINT(100.629958 13.973008)', 4326), '{"zone_group":"ชุมชนซอย83","device_count":11,"point_number":16}', 'active'),
(1, 'จุดที่ 17', 'จุดติดตั้ง 17', 13.976846, 100.623361, ST_GeomFromText('POINT(100.623361 13.976846)', 4326), '{"point_number":17}', 'active'),
(1, 'จุดที่ 18', 'จุดติดตั้ง 18', 13.978176, 100.623137, ST_GeomFromText('POINT(100.623137 13.978176)', 4326), '{"point_number":18}', 'active'),
(1, 'จุดที่ 19', 'จุดติดตั้ง 19', 13.985044, 100.608272, ST_GeomFromText('POINT(100.608272 13.985044)', 4326), '{"point_number":19}', 'active'),
(1, 'จุดที่ 20', 'จุดติดตั้ง 20', 13.984105, 100.608119, ST_GeomFromText('POINT(100.608119 13.984105)', 4326), '{"point_number":20}', 'active'),
(1, 'จุดที่ 21', 'จุดติดตั้ง 21', 13.983500, 100.608625, ST_GeomFromText('POINT(100.608625 13.983500)', 4326), '{"point_number":21}', 'active'),
(1, 'จุดที่ 22', 'ทั้งหมด7ตัว ซอยสร้างบุญรวมจุดที่22-23', 13.984417, 100.608689, ST_GeomFromText('POINT(100.608689 13.984417)', 4326), '{"zone_group":"ซอยสร้างบุญ","device_count":7,"point_number":22}', 'active'),
(1, 'จุดที่ 23', 'ทั้งหมด7ตัว ซอยสร้างบุญรวมจุดที่22-23', 13.986043, 100.608502, ST_GeomFromText('POINT(100.608502 13.986043)', 4326), '{"zone_group":"ซอยสร้างบุญ","device_count":7,"point_number":23}', 'active'),
(1, 'จุดที่ 24', 'รอบ 2เนฟ', 13.982228, 100.606856, ST_GeomFromText('POINT(100.606856 13.982228)', 4326), '{"zone_group":"รอบ2","point_number":24}', 'active'),
(1, 'จุดที่ 25', 'จุดติดตั้ง 25', 13.994206, 100.611625, ST_GeomFromText('POINT(100.611625 13.994206)', 4326), '{"point_number":25}', 'active'),
(1, 'จุดที่ 26', 'จุดติดตั้ง 26', 13.992734, 100.611501, ST_GeomFromText('POINT(100.611501 13.992734)', 4326), '{"point_number":26}', 'active'),
(1, 'จุดที่ 27', 'จุดติดตั้ง 27', 13.995550, 100.611695, ST_GeomFromText('POINT(100.611695 13.995550)', 4326), '{"point_number":27}', 'active'),
(1, 'จุดที่ 28', 'ชุมชนเดชาพัฒนา87', 13.973092, 100.604954, ST_GeomFromText('POINT(100.604954 13.973092)', 4326), '{"zone_group":"ชุมชนเดชาพัฒนา87","point_number":28}', 'active'),
(1, 'จุดที่ 29', 'จุดติดตั้ง 29', 13.971030, 100.604905, ST_GeomFromText('POINT(100.604905 13.971030)', 4326), '{"zone_group":"ชุมชนเดชาพัฒนา87","point_number":29}', 'active'),
(1, 'จุดที่ 30', 'จุดติดตั้ง 30', 13.969464, 100.605087, ST_GeomFromText('POINT(100.605087 13.969464)', 4326), '{"zone_group":"ชุมชนเดชาพัฒนา87","point_number":30}', 'active'),
(1, 'จุดที่ 31', 'จุดติดตั้ง 31', 13.969722, 100.606633, ST_GeomFromText('POINT(100.606633 13.969722)', 4326), '{"zone_group":"ชุมชนเดชาพัฒนา87","point_number":31}', 'active'),
(1, 'จุดที่ 32', 'รอบ 3 จุดที่ 1 รป8', 13.993287, 100.610436, ST_GeomFromText('POINT(100.610436 13.993287)', 4326), '{"zone_group":"รป8","point_number":32}', 'active'),
(1, 'จุดที่ 33', 'จุดที่ 2 รป8', 13.993715, 100.610593, ST_GeomFromText('POINT(100.610593 13.993715)', 4326), '{"zone_group":"รป8","point_number":33}', 'active'),
(1, 'จุดที่ 34', 'จุดที่ 3 รป8', 13.992888, 100.610420, ST_GeomFromText('POINT(100.610420 13.992888)', 4326), '{"zone_group":"รป8","point_number":34}', 'active'),
(1, 'จุดที่ 35', 'จุดที่ 4 รป8', 13.992409, 100.610422, ST_GeomFromText('POINT(100.610422 13.992409)', 4326), '{"zone_group":"รป8","point_number":35}', 'active'),
(1, 'จุดที่ 36', 'จุดที่5 รป8', 13.991973, 100.610165, ST_GeomFromText('POINT(100.610165 13.991973)', 4326), '{"zone_group":"รป8","point_number":36}', 'active'),
(1, 'จุดที่ 37', 'จุดที่1ซอย14 รป54', 13.977681, 100.653918, ST_GeomFromText('POINT(100.653918 13.977681)', 4326), '{"zone_group":"รป54","point_number":37}', 'active'),
(1, 'จุดที่ 38', 'จุดที่2 ซอย12 รป54', 13.978345, 100.653892, ST_GeomFromText('POINT(100.653892 13.978345)', 4326), '{"zone_group":"รป54","point_number":38}', 'active'),
(1, 'จุดที่ 39', 'จุดที่3ซอย10 รป54', 13.979070, 100.653965, ST_GeomFromText('POINT(100.653965 13.979070)', 4326), '{"zone_group":"รป54","point_number":39}', 'active'),
(1, 'จุดที่ 40', 'จุดที่4ซอย8 รป54', 13.979887, 100.653992, ST_GeomFromText('POINT(100.653992 13.979887)', 4326), '{"zone_group":"รป54","point_number":40}', 'active'),
(1, 'จุดที่ 41', 'จุดที่5ซอย6 รป54', 13.980652, 100.654033, ST_GeomFromText('POINT(100.654033 13.980652)', 4326), '{"zone_group":"รป54","point_number":41}', 'active'),
(1, 'จุดที่ 42', 'จุดที่6 ซอย4 รป54', 13.981469, 100.654078, ST_GeomFromText('POINT(100.654078 13.981469)', 4326), '{"zone_group":"รป54","point_number":42}', 'active'),
(1, 'จุดที่ 43', 'จุดที่7ซอย2 รป54', 13.982269, 100.654120, ST_GeomFromText('POINT(100.654120 13.982269)', 4326), '{"zone_group":"รป54","point_number":43}', 'active'),
(1, 'จุดที่ 44', 'จุดที่8 รป54', 13.982630, 100.654478, ST_GeomFromText('POINT(100.654478 13.982630)', 4326), '{"zone_group":"รป54","point_number":44}', 'active'),
(1, 'จุดที่ 45', 'จุดที่9 รป54', 13.981760, 100.654746, ST_GeomFromText('POINT(100.654746 13.981760)', 4326), '{"zone_group":"รป54","point_number":45}', 'active'),
(1, 'จุดที่ 46', 'จุดที่10 รป54', 13.981012, 100.654700, ST_GeomFromText('POINT(100.654700 13.981012)', 4326), '{"zone_group":"รป54","point_number":46}', 'active'),
(1, 'จุดที่ 47', 'จุดที่11 รป54', 13.980214, 100.654670, ST_GeomFromText('POINT(100.654670 13.980214)', 4326), '{"zone_group":"รป54","point_number":47}', 'active'),
(1, 'จุดที่ 48', 'จุดที่12 รป54', 13.979412, 100.654642, ST_GeomFromText('POINT(100.654642 13.979412)', 4326), '{"zone_group":"รป54","point_number":48}', 'active'),
(1, 'จุดที่ 49', 'จุดที่13 รป54', 13.978647, 100.654575, ST_GeomFromText('POINT(100.654575 13.978647)', 4326), '{"zone_group":"รป54","point_number":49}', 'active'),
(1, 'จุดที่ 50', 'จุดที่14 รป54', 13.977979, 100.654560, ST_GeomFromText('POINT(100.654560 13.977979)', 4326), '{"zone_group":"รป54","point_number":50}', 'active'),
(1, 'จุดที่ 51', 'จุดที่1 ซอย4วรุณพร', 13.991008, 100.655247, ST_GeomFromText('POINT(100.655247 13.991008)', 4326), '{"zone_group":"วรุณพร","point_number":51}', 'active'),
(1, 'จุดที่ 52', 'จุดที่2 ซอย5 วรุณพร', 13.990750, 100.654965, ST_GeomFromText('POINT(100.654965 13.990750)', 4326), '{"zone_group":"วรุณพร","point_number":52}', 'active'),
(1, 'จุดที่ 53', 'จุดที่3 ซอย6 วรุณพร', 13.990474, 100.654834, ST_GeomFromText('POINT(100.654834 13.990474)', 4326), '{"zone_group":"วรุณพร","point_number":53}', 'active'),
(1, 'จุดที่ 54', 'จุดที่4 ซอย7 วรุณพร', 13.990175, 100.654747, ST_GeomFromText('POINT(100.654747 13.990175)', 4326), '{"zone_group":"วรุณพร","point_number":54}', 'active'),
(1, 'จุดที่ 55', 'จุดที่5 ซอย3 วรุณพร', 13.991314, 100.654892, ST_GeomFromText('POINT(100.654892 13.991314)', 4326), '{"zone_group":"วรุณพร","point_number":55}', 'active'),
(1, 'จุดที่ 56', 'จุด6 วรุณพร', 13.991761, 100.654363, ST_GeomFromText('POINT(100.654363 13.991761)', 4326), '{"zone_group":"วรุณพร","point_number":56}', 'active'),
(1, 'จุดที่ 57', 'รอบที่ 4 จุดที่1 ซอยที่1 รป14', 13.987498, 100.605667, ST_GeomFromText('POINT(100.605667 13.987498)', 4326), '{"zone_group":"รป14","point_number":57}', 'active'),
(1, 'จุดที่ 58', 'จุดที่2 ซอยที่2 รป14', 13.987952, 100.605528, ST_GeomFromText('POINT(100.605528 13.987952)', 4326), '{"zone_group":"รป14","point_number":58}', 'active'),
(1, 'จุดที่ 59', 'จุดที่3 ซอยที่3 รป14', 13.988378, 100.605335, ST_GeomFromText('POINT(100.605335 13.988378)', 4326), '{"zone_group":"รป14","point_number":59}', 'active'),
(1, 'จุดที่ 60', 'จุดที่4 ซอยที่4 รป14', 13.988794, 100.605186, ST_GeomFromText('POINT(100.605186 13.988794)', 4326), '{"zone_group":"รป14","point_number":60}', 'active'),
(1, 'จุดที่ 61', 'จุดที่5 ซอยที่5 รป14', 13.989226, 100.605001, ST_GeomFromText('POINT(100.605001 13.989226)', 4326), '{"zone_group":"รป14","point_number":61}', 'active'),
(1, 'จุดที่ 62', 'จุดที่6 ซอยที่6 รป14', 13.989686, 100.604889, ST_GeomFromText('POINT(100.604889 13.989686)', 4326), '{"zone_group":"รป14","point_number":62}', 'active'),
(1, 'จุดที่ 63', 'จุดที่7 ซอยที่7 รป14', 13.990127, 100.604926, ST_GeomFromText('POINT(100.604926 13.990127)', 4326), '{"zone_group":"รป14","point_number":63}', 'active'),
(1, 'จุดที่ 64', 'จุดที่8 ซอยที่8 รป14', 13.990551, 100.604663, ST_GeomFromText('POINT(100.604663 13.990551)', 4326), '{"zone_group":"รป14","point_number":64}', 'active'),
(1, 'จุดที่ 65', 'จุดที่9 ซอย9 รป14', 13.990997, 100.604636, ST_GeomFromText('POINT(100.604636 13.990997)', 4326), '{"zone_group":"รป14","point_number":65}', 'active'),
(1, 'จุดที่ 66', 'จุดที่10 ซอย10 รป14', 13.991460, 100.604607, ST_GeomFromText('POINT(100.604607 13.991460)', 4326), '{"zone_group":"รป14","point_number":66}', 'active'),
(1, 'จุดที่ 67', 'จุดที่1ซอย12 รป12', 13.992930, 100.607890, ST_GeomFromText('POINT(100.607890 13.992930)', 4326), '{"zone_group":"รป12","point_number":67}', 'active'),
(1, 'จุดที่ 68', 'จุดที่2 ซอย11', 13.992472, 100.608098, ST_GeomFromText('POINT(100.608098 13.992472)', 4326), '{"zone_group":"รป12","point_number":68}', 'active'),
(1, 'จุดที่ 69', 'จุดที่3', 13.991813, 100.608082, ST_GeomFromText('POINT(100.608082 13.991813)', 4326), '{"zone_group":"รป12","point_number":69}', 'active'),
(1, 'จุดที่ 70', 'จุดที่4 ซอยที่8 รป12', 13.991147, 100.608164, ST_GeomFromText('POINT(100.608164 13.991147)', 4326), '{"zone_group":"รป12","point_number":70}', 'active'),
(1, 'จุดที่ 71', 'จุดติดตั้ง 71', 13.990736, 100.608738, ST_GeomFromText('POINT(100.608738 13.990736)', 4326), '{"zone_group":"รป12","point_number":71}', 'active'),
(1, 'จุดที่ 72', 'จุดที่6', 13.989743, 100.608403, ST_GeomFromText('POINT(100.608403 13.989743)', 4326), '{"zone_group":"รป12","point_number":72}', 'active'),
(1, 'จุดที่ 73', 'จุดที่7 ซอยที่4 รป10', 13.989380, 100.608878, ST_GeomFromText('POINT(100.608878 13.989380)', 4326), '{"zone_group":"รป10","point_number":73}', 'active'),
(1, 'จุดที่ 74', 'จุดที่8', 13.988410, 100.608613, ST_GeomFromText('POINT(100.608613 13.988410)', 4326), '{"zone_group":"รป10","point_number":74}', 'active'),
(1, 'จุดที่ 75', 'จุดที่9 ซอย1 รป10', 13.988016, 100.609096, ST_GeomFromText('POINT(100.609096 13.988016)', 4326), '{"zone_group":"รป10","point_number":75}', 'active'),
(1, 'จุดที่ 76', 'รอบที่ 5 ชุมชนรณชัย รน41', 14.005443, 100.647788, ST_GeomFromText('POINT(100.647788 14.005443)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":76}', 'active'),
(1, 'จุดที่ 77', 'ชุมชนรณชัย จุดที่2 รน41', 14.004105, 100.647816, ST_GeomFromText('POINT(100.647816 14.004105)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":77}', 'active'),
(1, 'จุดที่ 78', 'ชุมชนรณชัย รน41 จุดที่3', 14.002356, 100.647829, ST_GeomFromText('POINT(100.647829 14.002356)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":78}', 'active'),
(1, 'จุดที่ 79', 'ชุมชนรณชัย รน41 จุดที่4', 14.000886, 100.647821, ST_GeomFromText('POINT(100.647821 14.000886)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":79}', 'active'),
(1, 'จุดที่ 80', 'ชุมชนรณชัย รน41 จุดที่5', 13.999587, 100.647826, ST_GeomFromText('POINT(100.647826 13.999587)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":80}', 'active'),
(1, 'จุดที่ 81', 'ชุมชนรณชัย รน41 จุดที่6', 13.998014, 100.647828, ST_GeomFromText('POINT(100.647828 13.998014)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":81}', 'active'),
(1, 'จุดที่ 82', 'ชุมชนรณชัย รน41 จุดที่7', 13.996594, 100.647828, ST_GeomFromText('POINT(100.647828 13.996594)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":82}', 'active'),
(1, 'จุดที่ 83', 'ชุมชนรณชัย รน41 จุดที่8', 13.994765, 100.647814, ST_GeomFromText('POINT(100.647814 13.994765)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":83}', 'active'),
(1, 'จุดที่ 84', 'ชุมชนรณชัย รน41 จุดที่9', 13.993179, 100.647858, ST_GeomFromText('POINT(100.647858 13.993179)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":84}', 'active'),
(1, 'จุดที่ 85', 'ชุมชนรณชัย รน41 จุดที่10', 13.992310, 100.647858, ST_GeomFromText('POINT(100.647858 13.992310)', 4326), '{"zone_group":"ชุมชนรณชัย รน41","point_number":85}', 'active'),
(1, 'จุดที่ 86', 'ซอย20 เมน2 จุดที่1', 13.996136, 100.604141, ST_GeomFromText('POINT(100.604141 13.996136)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":86}', 'active'),
(1, 'จุดที่ 87', 'ซอย20 เมน2 จุดที่2', 13.995419, 100.603569, ST_GeomFromText('POINT(100.603569 13.995419)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":87}', 'active'),
(1, 'จุดที่ 88', 'ซอย20 เมน2 จุดที่3', 13.995743, 100.605607, ST_GeomFromText('POINT(100.605607 13.995743)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":88}', 'active'),
(1, 'จุดที่ 89', 'ซอย20 เมน2 จุดที่4', 13.996007, 100.607399, ST_GeomFromText('POINT(100.607399 13.996007)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":89}', 'active'),
(1, 'จุดที่ 90', 'ซอย20 เมน2 จุดที่5', 13.996089, 100.606357, ST_GeomFromText('POINT(100.606357 13.996089)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":90}', 'active'),
(1, 'จุดที่ 91', 'ซอย20 เมน2 จุดที่6', 13.996319, 100.605834, ST_GeomFromText('POINT(100.605834 13.996319)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":91}', 'active'),
(1, 'จุดที่ 92', 'ซอย20 เมน2 จุดที่7', 13.994528, 100.606578, ST_GeomFromText('POINT(100.606578 13.994528)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":92}', 'active'),
(1, 'จุดที่ 93', 'ซอย20 เมน2 จุดที่8', 13.994549, 100.603876, ST_GeomFromText('POINT(100.603876 13.994549)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":93}', 'active'),
(1, 'จุดที่ 94', 'ซอย20 เมน2 จุดที่9', 13.995010, 100.603987, ST_GeomFromText('POINT(100.603987 13.995010)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":94}', 'active'),
(1, 'จุดที่ 95', 'ซอย20 เมน2 จุดที่10', 13.994455, 100.606152, ST_GeomFromText('POINT(100.606152 13.994455)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":95}', 'active'),
(1, 'จุดที่ 96', 'ซอย20 เมน2 จุดที่11', 13.994040, 100.606483, ST_GeomFromText('POINT(100.606483 13.994040)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":96}', 'active'),
(1, 'จุดที่ 97', 'ซอย20 เมน2 จุดที่12', 13.993607, 100.606501, ST_GeomFromText('POINT(100.606501 13.993607)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":97}', 'active'),
(1, 'จุดที่ 98', 'ซอย20 เมน2 จุดที่13', 13.993143, 100.606491, ST_GeomFromText('POINT(100.606491 13.993143)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":98}', 'active'),
(1, 'จุดที่ 99', 'ซอย20 เมน2 จุดที่14', 13.992773, 100.607172, ST_GeomFromText('POINT(100.607172 13.992773)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":99}', 'active'),
(1, 'จุดที่ 100', 'ซอย20 เมน2 จุดที่15', 13.992274, 100.606809, ST_GeomFromText('POINT(100.606809 13.992274)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":100}', 'active'),
(1, 'จุดที่ 101', 'ซอย20 เมน2 จุดที่16', 13.991785, 100.606707, ST_GeomFromText('POINT(100.606707 13.991785)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":101}', 'active'),
(1, 'จุดที่ 102', 'ซอย20 เมน2 จุดที่17', 13.991361, 100.606917, ST_GeomFromText('POINT(100.606917 13.991361)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":102}', 'active'),
(1, 'จุดที่ 103', 'ซอย20 เมน2 จุดที่18', 13.991010, 100.607548, ST_GeomFromText('POINT(100.607548 13.991010)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":103}', 'active'),
(1, 'จุดที่ 104', 'ซอย20 เมน2 จุดที่19', 13.990417, 100.606698, ST_GeomFromText('POINT(100.606698 13.990417)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":104}', 'active'),
(1, 'จุดที่ 105', 'เมน2 จุดที่20', 13.989933, 100.606712, ST_GeomFromText('POINT(100.606712 13.989933)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":105}', 'active'),
(1, 'จุดที่ 106', 'ซอย20 เมน2 จุดที่21', 13.989591, 100.607390, ST_GeomFromText('POINT(100.607390 13.989591)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":106}', 'active'),
(1, 'จุดที่ 107', 'ซอย20 เมน2 จุดที่22', 13.989066, 100.606987, ST_GeomFromText('POINT(100.606987 13.989066)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":107}', 'active'),
(1, 'จุดที่ 108', 'ซอย20 เมน2 จุดที่23', 13.988744, 100.607721, ST_GeomFromText('POINT(100.607721 13.988744)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":108}', 'active'),
(1, 'จุดที่ 109', 'ซอย20 เมน2 จุดที่24', 13.988207, 100.607384, ST_GeomFromText('POINT(100.607384 13.988207)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":109}', 'active'),
(1, 'จุดที่ 110', 'ซอย20 เมน2 จุดที่25', 13.987850, 100.608081, ST_GeomFromText('POINT(100.608081 13.987850)', 4326), '{"zone_group":"ซอย20 เมน2","point_number":110}', 'active');

-- =====================================================
-- Migrate ข้อมูลตู้น้ำ 30 จุด → layer_id = 2
-- =====================================================
INSERT INTO `gis_markers` (`layer_id`, `title`, `description`, `latitude`, `longitude`, `coordinates`, `properties`, `status`) VALUES
(2, 'RSC0001', 'โถงชั้น 1 หน้าห้อง RSSC สำนักงานเทศบาลรังสิต', 13.987992927445, 100.60951463888, ST_GeomFromText('POINT(100.60951463888 13.987992927445)', 4326), '{"kiosk_code":"RSC0001","kiosk_count":1}', 'active'),
(2, 'RSC0002', 'โรงเรียนมัธยมนครรังสิต', 13.980344741823, 100.65586465155, ST_GeomFromText('POINT(100.65586465155 13.980344741823)', 4326), '{"kiosk_code":"RSC0002","kiosk_count":1}', 'active'),
(2, 'RSC0003', 'โรงเรียนดวงกมล', 13.984674615799, 100.63959619971, ST_GeomFromText('POINT(100.63959619971 13.984674615799)', 4326), '{"kiosk_code":"RSC0003","kiosk_count":1}', 'active'),
(2, 'RSC0004', 'ตลาดรังสิต', 13.985252418403, 100.61410188675, ST_GeomFromText('POINT(100.61410188675 13.985252418403)', 4326), '{"kiosk_code":"RSC0004","kiosk_count":1}', 'active'),
(2, 'RSC0005', 'บ้านเอื้ออาทรรังสิต คลอง 1', 13.985793779964, 100.62856435776, ST_GeomFromText('POINT(100.62856435776 13.985793779964)', 4326), '{"kiosk_code":"RSC0005","kiosk_count":1}', 'active'),
(2, 'RSC0006', 'หน้าศูนย์การค้าฟิวเจอร์พาร์ครังสิต', 13.989304522498, 100.61577647552, ST_GeomFromText('POINT(100.61577647552 13.989304522498)', 4326), '{"kiosk_code":"RSC0006","kiosk_count":1}', 'active'),
(2, 'RSC0007', 'สำนักงานเทศบาลนครรังสิต (อาคารใหม่)', 13.987696968498, 100.60924549514, ST_GeomFromText('POINT(100.60924549514 13.987696968498)', 4326), '{"kiosk_code":"RSC0007","kiosk_count":1}', 'active'),
(2, 'RSC0008', 'โรงพยาบาลประชาธิปัตย์', 13.990008024498, 100.60481965498, ST_GeomFromText('POINT(100.60481965498 13.990008024498)', 4326), '{"kiosk_code":"RSC0008","kiosk_count":1}', 'active'),
(2, 'RSC0009', 'วัดศิริจันทร์', 13.998025181232, 100.60706803204, ST_GeomFromText('POINT(100.60706803204 13.998025181232)', 4326), '{"kiosk_code":"RSC0009","kiosk_count":1}', 'active'),
(2, 'RSC0010', 'โรงเรียนอนุบาลเมืองรังสิต (สนามฟุตบอล)', 13.990791285752, 100.60971714792, ST_GeomFromText('POINT(100.60971714792 13.990791285752)', 4326), '{"kiosk_code":"RSC0010","kiosk_count":1}', 'active'),
(2, 'RSC0011', 'หน้าบริษัท อินเด็กซ์ อินเตอร์เนชั่นแนล กรุ๊ป จำกัด', 13.994662024768, 100.61091828426, ST_GeomFromText('POINT(100.61091828426 13.994662024768)', 4326), '{"kiosk_code":"RSC0011","kiosk_count":1}', 'active'),
(2, 'RSC0012', 'ชุมชนหมู่บ้านชมฟ้า', 13.996903735424, 100.64680069802, ST_GeomFromText('POINT(100.64680069802 13.996903735424)', 4326), '{"kiosk_code":"RSC0012","kiosk_count":1}', 'active'),
(2, 'RSC0013', 'ชุมชนหมู่บ้านรัตนโกสินทร์ 200 ปี', 13.993299476798, 100.64825758902, ST_GeomFromText('POINT(100.64825758902 13.993299476798)', 4326), '{"kiosk_code":"RSC0013","kiosk_count":1}', 'active'),
(2, 'RSC0014', 'หมู่บ้านทิพย์พิมาน ซอย 3', 13.978267700872, 100.65365832814, ST_GeomFromText('POINT(100.65365832814 13.978267700872)', 4326), '{"kiosk_code":"RSC0014","kiosk_count":1}', 'active'),
(2, 'RSC0015', 'ศูนย์พัฒนาเด็กเล็กวัดเขียนเขต', 13.97393226648, 100.63002665542, ST_GeomFromText('POINT(100.63002665542 13.97393226648)', 4326), '{"kiosk_code":"RSC0015","kiosk_count":1}', 'active'),
(2, 'RSC0016', 'ชุมชนหมู่บ้านธงชัย', 14.003424073, 100.64773693456, ST_GeomFromText('POINT(100.64773693456 14.003424073)', 4326), '{"kiosk_code":"RSC0016","kiosk_count":1}', 'active'),
(2, 'RSC0017', 'สำนักทะเบียนท้องถิ่น เทศบาลนครรังสิต', 13.987493685472, 100.60887316992, ST_GeomFromText('POINT(100.60887316992 13.987493685472)', 4326), '{"kiosk_code":"RSC0017","kiosk_count":1}', 'active'),
(2, 'RSC0018', 'ศูนย์แพทย์ชุมชนเทศบาลนครรังสิต ซอย 83', 13.975653953652, 100.63046625946, ST_GeomFromText('POINT(100.63046625946 13.975653953652)', 4326), '{"kiosk_code":"RSC0018","kiosk_count":1}', 'active'),
(2, 'RSC0019', 'ศูนย์บริการสาธารณสุข ซอย 59', 13.99062426534, 100.65508839284, ST_GeomFromText('POINT(100.65508839284 13.99062426534)', 4326), '{"kiosk_code":"RSC0019","kiosk_count":1}', 'active'),
(2, 'RSC0020', 'อุทยานบัว 100 ปี สมเด็จพระเทพฯ', 13.993252, 100.609656, ST_GeomFromText('POINT(100.609656 13.993252)', 4326), '{"kiosk_code":"RSC0020","kiosk_count":1}', 'active'),
(2, 'RSC0021', 'ชุมชนหมู่บ้านไทยสมบูรณ์ 2', 13.976831626414, 100.62339478276, ST_GeomFromText('POINT(100.62339478276 13.976831626414)', 4326), '{"kiosk_code":"RSC0021","kiosk_count":1}', 'active'),
(2, 'RSC0022', 'สนามกีฬาเฉลิมพระเกียรติ', 13.989068, 100.611118, ST_GeomFromText('POINT(100.611118 13.989068)', 4326), '{"kiosk_code":"RSC0022","kiosk_count":1}', 'active'),
(2, 'RSC0023', 'อาคารรวมใจ ชุมชนซอย 87 (เดชาพัฒนา)', 13.972068, 100.604952, ST_GeomFromText('POINT(100.604952 13.972068)', 4326), '{"kiosk_code":"RSC0023","kiosk_count":1}', 'active'),
(2, 'RSC0024', 'หน้าปากซอยรังสิต-ปทุมธานี 12', 13.99316, 100.60674, ST_GeomFromText('POINT(100.60674 13.99316)', 4326), '{"kiosk_code":"RSC0024","kiosk_count":1}', 'active'),
(2, 'RSC0025', 'หน้าตลาดรังสิต (ฝั่งตรงข้าม)', 13.98606, 100.61434, ST_GeomFromText('POINT(100.61434 13.98606)', 4326), '{"kiosk_code":"RSC0025","kiosk_count":1}', 'active'),
(2, 'RSC0026', 'ชุมชนหมู่บ้านฟ้าลากูน', 13.980505039378, 100.6405377388, ST_GeomFromText('POINT(100.6405377388 13.980505039378)', 4326), '{"kiosk_code":"RSC0026","kiosk_count":1}', 'active'),
(2, 'RSC0027', 'อาคารอเนกประสงค์ชุมชนหมู่บ้านเปรมปรีด์คันทรีโฮม', 13.982868650941, 100.6544813701, ST_GeomFromText('POINT(100.6544813701 13.982868650941)', 4326), '{"kiosk_code":"RSC0027","kiosk_count":1}', 'active'),
(2, 'RSC0028', 'อาคารอเนกประสงค์ชุมชนรัตนปทุม', 13.995437992574, 100.64054237518, ST_GeomFromText('POINT(100.64054237518 13.995437992574)', 4326), '{"kiosk_code":"RSC0028","kiosk_count":1}', 'active'),
(2, 'RSC0029', 'บ้านเอื้ออาทรรังสิต คลอง 1 (ศูนย์การศึกษาพิเศษ)', 13.979156568949, 100.62897292674, ST_GeomFromText('POINT(100.62897292674 13.979156568949)', 4326), '{"kiosk_code":"RSC0029","kiosk_count":1}', 'active'),
(2, 'RSC0030', 'บ้านเอื้ออาทรรังสิต คลอง 1 (จุดติดตั้งอาคาร 40)', 13.975524050197, 100.6294652448, ST_GeomFromText('POINT(100.6294652448 13.975524050197)', 4326), '{"kiosk_code":"RSC0030","kiosk_count":1}', 'active');

-- =====================================================
-- ตารางที่ 4: gis_marker_images (รูปภาพแนบหมุด)
-- Import สุดท้าย
-- FK → gis_markers.id
-- =====================================================

DROP TABLE IF EXISTS `gis_marker_images`;

CREATE TABLE `gis_marker_images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `marker_id` INT(11) NOT NULL COMMENT 'หมุดที่แนบรูป',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'path ไฟล์รูป',
  `file_name` VARCHAR(255) DEFAULT NULL COMMENT 'ชื่อไฟล์ต้นฉบับ',
  `file_size` INT(11) DEFAULT NULL COMMENT 'ขนาดไฟล์ (bytes)',
  `caption` VARCHAR(255) DEFAULT NULL COMMENT 'คำอธิบายรูป',
  `sort_order` INT(11) DEFAULT 0,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_marker` (`marker_id`),
  CONSTRAINT `fk_image_marker` FOREIGN KEY (`marker_id`) REFERENCES `gis_markers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='รูปภาพแนบหมุด GIS';

-- ยังไม่มีข้อมูลรูปภาพ - จะเพิ่มผ่าน Admin Panel ภายหลัง

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='บทบาทผู้ใช้งาน';

-- =====================================================
-- Roles เริ่มต้น — ออกแบบ 5 ระดับ
-- =====================================================
INSERT INTO `gis_roles` (`id`, `role_name`, `role_slug`, `description`, `is_system`, `priority`) VALUES
(1, 'Super Admin',    'super_admin',    'สิทธิ์สูงสุด จัดการทุกอย่างได้ รวมถึง user/role', 1, 100),
(2, 'Admin',          'admin',          'จัดการ layer/marker/category ทั้งหมด แต่ไม่จัดการ role', 1, 80),
(3, 'Layer Manager',  'layer_manager',  'จัดการ layer ที่ได้รับมอบหมาย เพิ่ม/แก้/ลบ marker ได้', 1, 60),
(4, 'Editor',         'editor',         'เพิ่ม/แก้ไข marker ใน layer ที่ได้รับสิทธิ์ แต่ลบไม่ได้', 1, 40),
(5, 'Viewer',         'viewer',         'ดูข้อมูลและแผนที่เฉพาะ layer/category ที่ได้รับสิทธิ์', 1, 20);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='รายการสิทธิ์ทั้งหมด';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='ผูกผู้ใช้กับบทบาท (M:M)';

-- กำหนด role ให้ผู้ใช้เริ่มต้น
INSERT INTO `gis_user_roles` (`user_id`, `role_id`, `assigned_by`) VALUES
(1, 1, NULL),   -- superadmin   → Super Admin
(2, 2, 1),      -- admin_it     → Admin
(3, 3, 1),      -- editor_infra → Layer Manager
(4, 5, 1);      -- viewer_01    → Viewer

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='สิทธิ์ที่ Role มี (M:M)';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='สิทธิ์เข้าถึง Layer รายบุคคล';

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='สิทธิ์เข้าถึง Category รายบุคคล';

-- ตัวอย่าง: editor_infra ดูแล category ระบบสาธารณูปโภค ทั้งหมด
INSERT INTO `gis_user_category_access` (`user_id`, `category_id`, `can_view`, `can_manage_layers`, `can_create_markers`, `can_edit_markers`, `can_delete_markers`, `granted_by`) VALUES
(3, 1, 1, 1, 1, 1, 1, 1),  -- editor_infra → สาธารณูปโภค: full
(4, 1, 1, 0, 0, 0, 0, 1),  -- viewer_01 → สาธารณูปโภค: ดูเท่านั้น
(4, 2, 1, 0, 0, 0, 0, 1);  -- viewer_01 → บริการสาธารณะ: ดูเท่านั้น

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Session ผู้ใช้งาน';


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Audit Log บันทึกทุกการกระทำ';

-- ตัวอย่าง audit log
INSERT INTO `gis_audit_log` (`user_id`, `action`, `resource_type`, `resource_id`, `resource_title`, `details`, `ip_address`) VALUES
(1, 'system_init', 'system', NULL, 'ติดตั้งระบบ GIS', '{"version":"1.0","tables_created":11}', '127.0.0.1');

-- =====================================================
-- ตารางที่ 12: VIEW สำหรับตรวจสอบสิทธิ์
-- ★ ไม่ใส่ DEFINER ตามที่บอก
-- =====================================================

-- -------------------------------------------------
-- VIEW 1: ดูสิทธิ์รวมของ user (role + permissions)
-- ใช้ใน PHP: SELECT * FROM v_user_permissions WHERE user_id = ?
-- -------------------------------------------------
CREATE OR REPLACE VIEW `v_user_permissions` AS
SELECT
    u.id AS user_id,
    u.username,
    u.full_name,
    r.id AS role_id,
    r.role_name,
    r.role_slug,
    r.priority AS role_priority,
    p.id AS permission_id,
    p.permission_key,
    p.resource,
    p.action
FROM gis_users u
JOIN gis_user_roles ur ON ur.user_id = u.id
JOIN gis_roles r ON r.id = ur.role_id
JOIN gis_role_permissions rp ON rp.role_id = r.id
JOIN gis_permissions p ON p.id = rp.permission_id
WHERE u.is_active = 1;


-- -------------------------------------------------
-- VIEW 2: ดูสิทธิ์ layer ของ user
-- รวม: role-level + layer-level + category-level
-- ใช้ใน PHP: SELECT * FROM v_user_layer_access WHERE user_id = ? AND layer_id = ?
-- -------------------------------------------------
CREATE OR REPLACE VIEW `v_user_layer_access` AS
SELECT
    u.id AS user_id,
    u.username,
    l.id AS layer_id,
    l.layer_name,
    l.layer_slug,
    c.id AS category_id,
    c.name AS category_name,

    -- สิทธิ์สูงสุดจากทุกแหล่ง (role + layer access + category access)
    CASE
        WHEN EXISTS (
            SELECT 1 FROM gis_user_roles ur2
            JOIN gis_roles r2 ON r2.id = ur2.role_id
            WHERE ur2.user_id = u.id AND r2.priority >= 80
        ) THEN 1
        WHEN ula.can_view = 1 THEN 1
        WHEN uca.can_view = 1 THEN 1
        ELSE 0
    END AS can_view,

    CASE
        WHEN EXISTS (
            SELECT 1 FROM gis_user_roles ur2
            JOIN gis_roles r2 ON r2.id = ur2.role_id
            WHERE ur2.user_id = u.id AND r2.priority >= 80
        ) THEN 1
        WHEN ula.can_create = 1 THEN 1
        WHEN uca.can_create_markers = 1 THEN 1
        ELSE 0
    END AS can_create,

    CASE
        WHEN EXISTS (
            SELECT 1 FROM gis_user_roles ur2
            JOIN gis_roles r2 ON r2.id = ur2.role_id
            WHERE ur2.user_id = u.id AND r2.priority >= 80
        ) THEN 1
        WHEN ula.can_edit = 1 THEN 1
        WHEN uca.can_edit_markers = 1 THEN 1
        ELSE 0
    END AS can_edit,

    CASE
        WHEN EXISTS (
            SELECT 1 FROM gis_user_roles ur2
            JOIN gis_roles r2 ON r2.id = ur2.role_id
            WHERE ur2.user_id = u.id AND r2.priority >= 80
        ) THEN 1
        WHEN ula.can_delete = 1 THEN 1
        WHEN uca.can_delete_markers = 1 THEN 1
        ELSE 0
    END AS can_delete,

    CASE
        WHEN EXISTS (
            SELECT 1 FROM gis_user_roles ur2
            JOIN gis_roles r2 ON r2.id = ur2.role_id
            WHERE ur2.user_id = u.id AND r2.priority >= 80
        ) THEN 1
        WHEN ula.can_export = 1 THEN 1
        ELSE 0
    END AS can_export,

    CASE
        WHEN EXISTS (
            SELECT 1 FROM gis_user_roles ur2
            JOIN gis_roles r2 ON r2.id = ur2.role_id
            WHERE ur2.user_id = u.id AND r2.priority >= 80
        ) THEN 1
        WHEN ula.can_import = 1 THEN 1
        ELSE 0
    END AS can_import

FROM gis_users u
CROSS JOIN gis_layers l
LEFT JOIN gis_categories c ON c.id = l.category_id
LEFT JOIN gis_user_layer_access ula
    ON ula.user_id = u.id AND ula.layer_id = l.id
    AND (ula.expires_at IS NULL OR ula.expires_at > NOW())
LEFT JOIN gis_user_category_access uca
    ON uca.user_id = u.id AND uca.category_id = l.category_id
    AND (uca.expires_at IS NULL OR uca.expires_at > NOW())
WHERE u.is_active = 1;


-- -------------------------------------------------
-- VIEW 3: สรุปจำนวน user แต่ละ role
-- ใช้ใน Admin Dashboard
-- -------------------------------------------------
CREATE OR REPLACE VIEW `v_role_summary` AS
SELECT
    r.id AS role_id,
    r.role_name,
    r.role_slug,
    r.priority,
    COUNT(ur.user_id) AS user_count,
    (SELECT COUNT(*) FROM gis_role_permissions rp WHERE rp.role_id = r.id) AS permission_count
FROM gis_roles r
LEFT JOIN gis_user_roles ur ON ur.role_id = r.id
GROUP BY r.id, r.role_name, r.role_slug, r.priority;

SET FOREIGN_KEY_CHECKS = 1;
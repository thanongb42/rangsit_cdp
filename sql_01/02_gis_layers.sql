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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ชั้นข้อมูลแผนที่ GIS';

-- ข้อมูลเริ่มต้น: 2 layer ที่มีอยู่แล้ว
INSERT INTO `gis_layers` (`id`, `category_id`, `layer_name`, `layer_slug`, `description`, `icon_class`, `marker_color`, `marker_shape`, `sort_order`) VALUES
(1, 1, 'จุดติดตั้งระบบเสียงตามสาย', 'speakers', 'ระบบกระจายเสียงชนิดไร้สาย เทศบาลเมืองรังสิต', 'fa-broadcast-tower', '#6c5ce7', 'circle', 1),
(2, 2, 'จุดติดตั้งตู้น้ำดื่ม', 'water-kiosks', 'ตู้น้ำดื่มสาธารณะ เทศบาลเมืองรังสิต', 'fa-tint', '#00b894', 'square', 2);

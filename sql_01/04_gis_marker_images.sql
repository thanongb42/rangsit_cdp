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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='รูปภาพแนบหมุด GIS';

-- ยังไม่มีข้อมูลรูปภาพ - จะเพิ่มผ่าน Admin Panel ภายหลัง

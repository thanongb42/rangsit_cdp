-- =====================================================
-- Migration: cameras → gis_markers (layer: cctv)
-- Source: rangsit_doocam.cameras (5 records)
-- Target: gis_markers with layer_id for "cctv"
-- Run: mysql -u root rangsit_cdp < sql_03/migrate_cameras_to_gis_markers.sql
-- =====================================================

SET NAMES utf8mb4;

-- Step 1: Ensure CCTV layer exists (category_id=4 ความปลอดภัย)
-- If layer_slug 'cctv' or layer id=3 already exists, skip insert
INSERT IGNORE INTO `gis_layers` (`id`, `category_id`, `layer_name`, `layer_slug`, `description`, `icon_class`, `marker_color`, `marker_shape`, `sort_order`)
VALUES (3, 4, 'กล้อง CCTV', 'cctv', 'กล้องวงจรปิดตรวจการณ์ เทศบาลนครรังสิต', 'fa-video', '#ef4444', 'diamond', 3);

-- Step 2: Insert camera data into gis_markers (layer_id=3 → cctv)
-- Mapping: cameras.name → title, cameras.stream_url → properties.stream_url
INSERT INTO `gis_markers` (`layer_id`, `title`, `description`, `latitude`, `longitude`, `coordinates`, `properties`, `status`) VALUES
(3, 'กล้องตรวจวัดระดับน้ำ สะพานแดง', 'กล้อง CCTV ตรวจวัดระดับน้ำ สะพานแดง', 13.98605405, 100.62576681, ST_GeomFromText('POINT(100.62576681 13.98605405)'), '{"camera_id":1,"stream_url":"http://user7:rangsit1029@118.174.138.142:1029/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1","is_active":true}', 'active'),
(3, 'หน้าหมู่บ้าน รัตนโกสินทร์ 200 ปี', 'กล้อง CCTV หน้าหมู่บ้าน รัตนโกสินทร์ 200 ปี', 13.98705348, 100.60629934, ST_GeomFromText('POINT(100.60629934 13.98705348)'), '{"camera_id":2,"stream_url":"http://user7:rangsit1025@118.174.138.142:1025/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1","is_active":true}', 'active'),
(3, 'โค้งห้างฟิวเจอร์พาร์ค รพ.เปาโล', 'กล้อง CCTV โค้งห้างฟิวเจอร์พาร์ค รพ.เปาโล', 13.98526803, 100.61866701, ST_GeomFromText('POINT(100.61866701 13.98526803)'), '{"camera_id":3,"stream_url":"http://user7:rangsit1033@118.174.138.142:1033/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1","is_active":true}', 'active'),
(3, 'สะพานแดง', 'กล้อง CCTV สะพานแดง', 13.98646527, 100.62641323, ST_GeomFromText('POINT(100.62641323 13.98646527)'), '{"camera_id":4,"stream_url":"http://user7:rangsit1031@118.174.138.142:1031/stw-cgi/video.cgi?msubmenu=stream&action=view&Profile=1","is_active":true}', 'active'),
(3, 'เมืองปทุม', 'กล้อง CCTV เมืองปทุม', 14.02283238, 100.53555608, ST_GeomFromText('POINT(100.53555608 14.02283238)'), '{"camera_id":5,"stream_url":"http://101.109.253.60:8999/","is_active":true}', 'active');

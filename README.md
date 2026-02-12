# Rangsit CDP - Community Data Platform

**ระบบ Web GIS สำหรับเทศบาลนครรังสิต**

ระบบแพลตฟอร์มข้อมูลชุมชน (Community Data Platform) ที่ใช้เทคโนโลยี Web GIS เพื่อจัดการข้อมูลเชิงพื้นที่ของเทศบาลนครรังสิต เช่น จุดติดตั้งระบบเสียงตามสาย, ตู้น้ำดื่มสาธารณะ และอื่นๆ

## Tech Stack

| ส่วน | เครื่องมือ | หมายเหตุ |
|------|-----------|----------|
| Map Engine | Leaflet.js 1.9 | เบา, plugin ecosystem ใหญ่ |
| Base Maps | OpenStreetMap / ESRI / CartoDB | ฟรี ไม่ต้อง API key |
| Backend | PHP 8.x | รันบน XAMPP / shared hosting |
| Database | MySQL 8.x + Spatial Index | รองรับ GeoJSON + POINT geometry |
| Data Format | GeoJSON | มาตรฐาน OGC |
| UI Framework | TailwindCSS + Font Awesome | Admin panel |
| Drawing | Leaflet.Draw | ปักหมุด/วาด polygon บนแผนที่ |
| Clustering | Leaflet.markercluster | รวมหมุดซ้อนกัน |

## System Architecture

```
Browser (Leaflet.js)
  │
  ├── GET  /api/layers.php              → รายการ Layer ทั้งหมด
  ├── GET  /api/geojson.php?layer=5     → GeoJSON FeatureCollection
  ├── POST /api/markers.php             → เพิ่มหมุดใหม่ + upload รูป
  ├── PUT  /api/markers.php?id=123      → แก้ไขหมุด
  └── DELETE /api/markers.php?id=123    → ลบหมุด

PHP API (GeoJSON Output)
  │
  └── MySQL (InnoDB + SPATIAL INDEX)
        ├── gis_categories      → หมวดหมู่/สี/ไอคอน
        ├── gis_layers          → ชั้นข้อมูลแผนที่
        ├── gis_markers         → หมุดทั้งหมด + POINT geometry
        └── gis_marker_images   → รูปภาพแนบหมุด
```

## Database Schema

โปรเจคนี้ใช้ MySQL Spatial Index สำหรับข้อมูลเชิงพื้นที่ ประกอบด้วย:

**Core Tables (sql_01/)**
- `gis_categories` - หมวดหมู่ชั้นข้อมูล
- `gis_layers` - ชั้นข้อมูลแผนที่ (เช่น ลำโพง, ตู้น้ำ)
- `gis_markers` - หมุดทั้งหมด พร้อม SPATIAL INDEX
- `gis_marker_images` - รูปภาพแนบหมุด

**Auth & Access Control (sql_02/)**
- `gis_users` - ผู้ใช้งานระบบ
- `gis_roles` - บทบาท (admin/editor/viewer)
- `gis_permissions` - สิทธิ์การใช้งาน
- `gis_user_roles` - เชื่อมผู้ใช้กับบทบาท
- `gis_role_permissions` - เชื่อมบทบาทกับสิทธิ์
- `gis_access_control` - ควบคุมการเข้าถึง
- `gis_sessions_audit` - บันทึกการใช้งาน

## Project Structure

```
cdp/
├── admin-template.php            Admin dashboard template
├── template-layout/              Layout components
│   ├── header.php
│   ├── sidebar.php
│   ├── navbar.php
│   ├── footer.php
│   └── scripts.php
├── sql_01/                       Core GIS tables
│   ├── 01_gis_categories.sql
│   ├── 02_gis_layers.sql
│   ├── 03_gis_markers.sql
│   └── 04_gis_marker_images.sql
├── sql_02/                       Auth & access control
│   ├── 05_gis_users.sql
│   ├── 06_gis_roles.sql
│   ├── 07_gis_permissions.sql
│   ├── 08_gis_user_roles.sql
│   ├── 09_gis_role_permissions.sql
│   ├── 10_gis_access_control.sql
│   ├── 11_gis_sessions_audit.sql
│   └── 12_gis_views.sql
└── webgis_development_plan.html  แผนพัฒนาระบบ (เอกสารอ้างอิง)
```

## Development Roadmap

| Phase | รายละเอียด |
|-------|-----------|
| 1. Foundation | สร้าง Database Schema + GeoJSON API endpoints |
| 2. Map Viewer | หน้าแผนที่หลัก + Layer Control + MarkerCluster |
| 3. Admin Panel | CRUD Layer/Marker + ปักหมุด + Import/Export |
| 4. Auth & Roles | Login + สิทธิ์ (admin/editor/viewer) + Audit log |
| 5. Advanced | Bounding Box query, Polygon, Dashboard, Mobile/Offline |

## Initial Data

- จุดติดตั้งระบบเสียงตามสาย: 110 จุด
- จุดติดตั้งตู้น้ำดื่มสาธารณะ: 30 จุด

## Requirements

- PHP 8.x
- MySQL 8.x (with Spatial extension)
- Apache (XAMPP recommended for local development)

## License

This project is developed for Rangsit Municipality.

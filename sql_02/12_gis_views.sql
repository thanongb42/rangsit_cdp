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

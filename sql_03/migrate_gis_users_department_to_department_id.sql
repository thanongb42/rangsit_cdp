SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

START TRANSACTION;

ALTER TABLE `gis_users`
  ADD COLUMN `department_id` INT(11) NULL AFTER `avatar`;

UPDATE `gis_users` u
LEFT JOIN `departments` d
  ON (
    d.`department_name` COLLATE utf8mb4_general_ci = u.`department` COLLATE utf8mb4_general_ci
    OR d.`department_code` COLLATE utf8mb4_general_ci = u.`department` COLLATE utf8mb4_general_ci
    OR CAST(d.`department_id` AS CHAR) COLLATE utf8mb4_general_ci = u.`department` COLLATE utf8mb4_general_ci
  )
SET u.`department_id` = d.`department_id`
WHERE u.`department_id` IS NULL
  AND u.`department` IS NOT NULL;

ALTER TABLE `gis_users`
  ADD KEY `idx_department_id` (`department_id`);

ALTER TABLE `gis_users`
  ADD CONSTRAINT `fk_gis_users_department`
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`)
  ON UPDATE CASCADE
  ON DELETE SET NULL;

ALTER TABLE `gis_users`
  DROP COLUMN `department`;

COMMIT;

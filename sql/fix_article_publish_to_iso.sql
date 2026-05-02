-- ================================================================
-- ปรับข้อมูล tb_article.article_publish ให้เป็นรูปแบบมาตรฐาน ISO YYYY-MM-DD
-- ================================================================
--
-- ปัญหา:
--   ฟิลด์ article_publish เก็บเป็น string หลาย format ปนกัน
--     - DD-MM-YYYY  (ตามค่า default ของฟอร์มเดิม)
--     - YYYY-MM-DD  (ISO)
--     - DD/MM/YYYY
--   ทำให้การกรองช่วงวันที่และเรียงลำดับทำงานผิดพลาด
--
-- เป้าหมาย: แปลงทุกแถวให้เป็น YYYY-MM-DD ทั้งหมด
--
-- คำแนะนำการรัน:
--   1) BACKUP ก่อนเสมอ (ส่วนที่ 1)
--   2) ตรวจสอบรายการที่จะถูกแก้ (ส่วนที่ 2 - SELECT preview)
--   3) เปิด transaction → รัน UPDATE → COMMIT (ส่วนที่ 3)
--   4) ตรวจสอบหลังแก้ (ส่วนที่ 4)
--   5) ถ้าผิดให้ ROLLBACK หรือใช้ตาราง backup กู้คืน
--
-- ทดสอบบน MySQL/MariaDB ตั้งแต่เวอร์ชัน 5.5+ (ใช้ STR_TO_DATE / DATE_FORMAT / REGEXP)
-- ================================================================


-- ================================================================
-- ส่วนที่ 1) สำรองข้อมูล (BACKUP)
-- ================================================================
-- สร้างตาราง backup ที่ระบุวันที่เพื่อกู้คืนถ้าจำเป็น
DROP TABLE IF EXISTS `tb_article_backup_20260429`;
CREATE TABLE `tb_article_backup_20260429` AS
SELECT * FROM `tb_article`;

-- (เปลี่ยนวันที่ใน suffix ตามวันที่รันจริงได้)
-- ตรวจสอบจำนวนแถวที่สำรอง:
SELECT COUNT(*) AS backup_rows FROM `tb_article_backup_20260429`;


-- ================================================================
-- ส่วนที่ 2) PREVIEW — รายการที่ format ไม่ใช่ ISO
-- ================================================================
-- 2.1 แถวที่เป็น DD-MM-YYYY (เช่น '29-04-2025')
SELECT
    article_id,
    article_publish AS current_value,
    DATE_FORMAT(STR_TO_DATE(article_publish, '%d-%m-%Y'), '%Y-%m-%d') AS will_become
FROM tb_article
WHERE article_publish REGEXP '^[0-3][0-9]-[0-1][0-9]-[0-9]{4}$'
  AND STR_TO_DATE(article_publish, '%d-%m-%Y') IS NOT NULL
ORDER BY article_id DESC
LIMIT 100;

-- 2.2 แถวที่เป็น DD/MM/YYYY (เช่น '29/04/2025')
SELECT
    article_id,
    article_publish AS current_value,
    DATE_FORMAT(STR_TO_DATE(article_publish, '%d/%m/%Y'), '%Y-%m-%d') AS will_become
FROM tb_article
WHERE article_publish REGEXP '^[0-3][0-9]/[0-1][0-9]/[0-9]{4}$'
  AND STR_TO_DATE(article_publish, '%d/%m/%Y') IS NOT NULL
ORDER BY article_id DESC
LIMIT 100;

-- 2.3 แถวที่เป็น ISO อยู่แล้ว (ไม่ต้องแก้)
SELECT
    COUNT(*) AS already_iso_count
FROM tb_article
WHERE article_publish REGEXP '^[0-9]{4}-[0-1][0-9]-[0-3][0-9]$';

-- 2.4 แถวที่ format แปลก / parse ไม่ได้ (ต้องแก้มือ)
SELECT
    article_id,
    article_publish AS unknown_format
FROM tb_article
WHERE article_publish IS NOT NULL
  AND TRIM(article_publish) <> ''
  AND article_publish NOT REGEXP '^[0-9]{4}-[0-1][0-9]-[0-3][0-9]$'
  AND article_publish NOT REGEXP '^[0-3][0-9]-[0-1][0-9]-[0-9]{4}$'
  AND article_publish NOT REGEXP '^[0-3][0-9]/[0-1][0-9]/[0-9]{4}$';


-- ================================================================
-- ส่วนที่ 3) UPDATE — แปลงให้เป็น ISO
-- ================================================================
-- 🔒 ใช้ transaction กันเสียหาย
START TRANSACTION;

-- 3.1 DD-MM-YYYY → YYYY-MM-DD
UPDATE tb_article
SET article_publish = DATE_FORMAT(STR_TO_DATE(article_publish, '%d-%m-%Y'), '%Y-%m-%d')
WHERE article_publish REGEXP '^[0-3][0-9]-[0-1][0-9]-[0-9]{4}$'
  AND STR_TO_DATE(article_publish, '%d-%m-%Y') IS NOT NULL;

-- 3.2 DD/MM/YYYY → YYYY-MM-DD
UPDATE tb_article
SET article_publish = DATE_FORMAT(STR_TO_DATE(article_publish, '%d/%m/%Y'), '%Y-%m-%d')
WHERE article_publish REGEXP '^[0-3][0-9]/[0-1][0-9]/[0-9]{4}$'
  AND STR_TO_DATE(article_publish, '%d/%m/%Y') IS NOT NULL;

-- 3.3 (ถ้ามี) แปลง datetime ที่มีเวลาต่อท้าย → ตัดเหลือแค่ DATE
UPDATE tb_article
SET article_publish = DATE_FORMAT(STR_TO_DATE(article_publish, '%Y-%m-%d %H:%i:%s'), '%Y-%m-%d')
WHERE article_publish REGEXP '^[0-9]{4}-[0-1][0-9]-[0-3][0-9] [0-2][0-9]:[0-5][0-9]:[0-5][0-9]$'
  AND STR_TO_DATE(article_publish, '%Y-%m-%d %H:%i:%s') IS NOT NULL;

-- ตรวจสอบจำนวนแถวที่ผ่านแต่ละ UPDATE
-- ROW_COUNT() จะคืนจำนวนแถวที่อัปเดตจริงในคำสั่งล่าสุด
-- ถ้าทุกอย่าง OK → COMMIT, ถ้าไม่ → ROLLBACK
COMMIT;
-- ROLLBACK;  -- ใช้บรรทัดนี้แทน COMMIT ถ้าต้องการยกเลิก


-- ================================================================
-- ส่วนที่ 4) VERIFY — ตรวจสอบหลังแก้
-- ================================================================
-- 4.1 ทุกแถวควรเป็น YYYY-MM-DD แล้ว
SELECT
    SUM(CASE WHEN article_publish REGEXP '^[0-9]{4}-[0-1][0-9]-[0-3][0-9]$'    THEN 1 ELSE 0 END) AS iso_count,
    SUM(CASE WHEN article_publish REGEXP '^[0-3][0-9]-[0-1][0-9]-[0-9]{4}$'   THEN 1 ELSE 0 END) AS dmy_dash_count,
    SUM(CASE WHEN article_publish REGEXP '^[0-3][0-9]/[0-1][0-9]/[0-9]{4}$'   THEN 1 ELSE 0 END) AS dmy_slash_count,
    SUM(CASE WHEN article_publish IS NULL OR TRIM(article_publish) = ''       THEN 1 ELSE 0 END) AS null_or_empty,
    COUNT(*) AS total_rows
FROM tb_article;

-- 4.2 รายการที่ยังไม่เป็น ISO (ถ้ามี → ต้องแก้มือ)
SELECT article_id, article_publish AS still_not_iso
FROM tb_article
WHERE article_publish IS NOT NULL
  AND TRIM(article_publish) <> ''
  AND article_publish NOT REGEXP '^[0-9]{4}-[0-1][0-9]-[0-3][0-9]$';

-- 4.3 ตัวอย่างผลลัพธ์ (10 แถวล่าสุด)
SELECT article_id, article_publish, article_th
FROM tb_article
ORDER BY article_id DESC
LIMIT 10;


-- ================================================================
-- ส่วนที่ 5) (Optional) เพิ่ม CHECK constraint กันข้อมูลใหม่ที่ format ผิด
-- ================================================================
-- ⚠ ต้องใช้ MySQL 8.0.16+ หรือ MariaDB 10.2+
-- ALTER TABLE tb_article
-- ADD CONSTRAINT chk_article_publish_iso
-- CHECK (article_publish IS NULL OR article_publish REGEXP '^[0-9]{4}-[0-1][0-9]-[0-3][0-9]$');


-- ================================================================
-- ส่วนที่ 6) (กรณีฉุกเฉิน) กู้คืนจาก backup
-- ================================================================
-- TRUNCATE tb_article;
-- INSERT INTO tb_article SELECT * FROM tb_article_backup_20260429;
-- DROP TABLE tb_article_backup_20260429;  -- ลบ backup เมื่อมั่นใจแล้ว


-- ================================================================
-- หมายเหตุ:
--   ถ้าระบบเก็บปี พ.ศ. ในข้อมูลบางแถว (เช่น '29-04-2568')
--   STR_TO_DATE จะ parse year 2568 ตามตรรกะปกติ ไม่ใช่บั๊ก
--   แต่ถ้าต้องการแปลง 2568 → 2025 ให้ใช้ UPDATE เพิ่มเติม:
--
-- UPDATE tb_article
-- SET article_publish = DATE_FORMAT(
--     DATE_SUB(STR_TO_DATE(article_publish, '%Y-%m-%d'), INTERVAL 543 YEAR),
--     '%Y-%m-%d'
-- )
-- WHERE article_publish REGEXP '^25[0-9]{2}-[0-1][0-9]-[0-3][0-9]$';
-- ================================================================

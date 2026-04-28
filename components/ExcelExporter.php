<?php

namespace app\components;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\web\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * ExcelExporter
 * ----------------------------------------------------------------------------
 * Component กลางสำหรับ export ข้อมูลจาก ActiveDataProvider / Query / iterable
 * ออกเป็นไฟล์ .xlsx โดยใช้ PhpSpreadsheet
 *
 * การใช้งาน:
 *
 * \app\components\ExcelExporter::export(
 *     $dataProvider,
 *     [
 *         ['header' => 'ลำดับ', 'value' => function ($model, $index) {
 *             return $index + 1;
 *         }],
 *         ['header' => 'ชื่อโครงการ', 'value' => 'projectNameTH'],
 *         ['header' => 'หน่วยงาน', 'value' => 'hasorg.org_name'],
 *         ['header' => 'ปีงบประมาณ (พ.ศ.)', 'value' => function ($m) {
 *             return $m->projectYearsubmit ?: '';
 *         }],
 *     ],
 *     [
 *         'filename'   => 'researchpro_' . date('Ymd_His'),
 *         'sheetTitle' => 'งานวิจัย',
 *         'title'      => 'รายการโครงการวิจัย',
 *     ]
 * );
 */
class ExcelExporter
{
    /**
     * สร้างและส่งไฟล์ .xlsx เป็น Response กลับให้ผู้ใช้ดาวน์โหลด
     *
     * @param ActiveDataProvider|ActiveQuery|Query|iterable $source แหล่งข้อมูล
     * @param array $columns รายการคอลัมน์ แต่ละช่องเป็น array ที่มี key:
     *      - header: string  หัวคอลัมน์
     *      - value:  string|callable
     *                  - string: ชื่อ attribute หรือ dot path เช่น "user.uname"
     *                  - callable: function($model, $index){ return ...; }
     *      - format: 'string'|'number'|'date' (default: 'string')
     * @param array $options
     *      - filename:   string  (no extension) default: export_<datetime>
     *      - sheetTitle: string  default: Data
     *      - title:      string  หัวเรื่องที่ merge แถวบนสุด (optional)
     *      - subtitle:   string  หัวเรื่องรองที่ merge แถวที่ 2 (optional)
     *      - response:   yii\web\Response  default: Yii::$app->response
     * @return Response
     */
    public static function export($source, array $columns, array $options = [])
    {
        $filename   = $options['filename']   ?? ('export_' . date('Ymd_His'));
        $sheetTitle = $options['sheetTitle'] ?? 'Data';
        $title      = $options['title']      ?? null;
        $subtitle   = $options['subtitle']   ?? null;
        $response   = $options['response']   ?? Yii::$app->response;

        // sanitize filename (กัน path traversal / ตัวอักษรอันตราย)
        $filename = self::sanitizeFilename($filename);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Research System')
            ->setTitle($title ?? $sheetTitle)
            ->setSubject($title ?? $sheetTitle);

        $sheet = $spreadsheet->getActiveSheet();
        // sheet title ใน xlsx ต้องไม่เกิน 31 ตัวอักษร และห้าม : \ / ? * [ ]
        $sheet->setTitle(self::sanitizeSheetTitle($sheetTitle));

        $colCount = count($columns);
        if ($colCount === 0) {
            throw new \InvalidArgumentException('ต้องระบุ columns อย่างน้อย 1 คอลัมน์');
        }
        $lastColLetter = self::columnLetter($colCount);

        $rowIndex = 1;

        // ============== Title row (optional) ==============
        if ($title !== null && $title !== '') {
            $sheet->setCellValue('A' . $rowIndex, $title);
            $sheet->mergeCells("A{$rowIndex}:{$lastColLetter}{$rowIndex}");
            $sheet->getStyle("A{$rowIndex}")
                ->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$rowIndex}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rowIndex++;
        }

        if ($subtitle !== null && $subtitle !== '') {
            $sheet->setCellValue('A' . $rowIndex, $subtitle);
            $sheet->mergeCells("A{$rowIndex}:{$lastColLetter}{$rowIndex}");
            $sheet->getStyle("A{$rowIndex}")
                ->getFont()->setItalic(true)->setSize(11);
            $sheet->getStyle("A{$rowIndex}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $rowIndex++;
        }

        // ============== Header row ==============
        $headerRow = $rowIndex;
        $col = 1;
        foreach ($columns as $c) {
            $cellRef = self::columnLetter($col) . $headerRow;
            $sheet->setCellValueExplicit(
                $cellRef,
                (string)($c['header'] ?? ''),
                DataType::TYPE_STRING
            );
            $col++;
        }
        $headerRange = "A{$headerRow}:{$lastColLetter}{$headerRow}";
        $headerStyle = $sheet->getStyle($headerRange);
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD9E1F2');
        $headerStyle->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $rowIndex++;
        $dataStartRow = $rowIndex;

        // ============== Data rows ==============
        $models = self::resolveModels($source);
        $i = 0;
        foreach ($models as $model) {
            $col = 1;
            foreach ($columns as $c) {
                $cellRef = self::columnLetter($col) . $rowIndex;
                $rawValue = self::extractValue($model, $c['value'] ?? null, $i);
                $format = $c['format'] ?? 'string';

                self::writeCell($sheet, $cellRef, $rawValue, $format);
                $col++;
            }
            $rowIndex++;
            $i++;
        }
        $dataEndRow = $rowIndex - 1;

        // ============== Auto width + Border ==============
        for ($k = 1; $k <= $colCount; $k++) {
            $sheet->getColumnDimension(self::columnLetter($k))->setAutoSize(true);
        }

        if ($dataEndRow >= $headerRow) {
            $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$dataEndRow}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        // freeze header row
        $sheet->freezePane('A' . ($headerRow + 1));

        // ============== ส่ง Response ==============
        return self::sendResponse($spreadsheet, $filename, $response);
    }

    /* ====================================================================
     *  Internal helpers
     * ==================================================================== */

    protected static function resolveModels($source)
    {
        if ($source instanceof ActiveDataProvider) {
            // ปิด pagination เพื่อให้ได้ข้อมูลครบทุกแถวตาม filter ปัจจุบัน
            $source->pagination = false;
            return $source->getModels();
        }
        if ($source instanceof ActiveQuery || $source instanceof Query) {
            return $source->all();
        }
        // assume iterable / array
        return $source ?? [];
    }

    /**
     * ดึงค่าจาก model ตามนิยามคอลัมน์
     *  - callable: เรียก function($model, $index)
     *  - string ที่มีจุด: dot path เช่น "user.uname"
     *  - string ปกติ: attribute name
     */
    protected static function extractValue($model, $valueDef, int $index = 0)
    {
        if ($valueDef === null) {
            return '';
        }

        if (is_callable($valueDef)) {
            try {
                return $valueDef($model, $index);
            } catch (\Throwable $e) {
                return '';
            }
        }

        if (is_string($valueDef)) {
            if (strpos($valueDef, '.') !== false) {
                $cur = $model;
                foreach (explode('.', $valueDef) as $p) {
                    if (is_object($cur)) {
                        $cur = $cur->{$p} ?? null;
                    } elseif (is_array($cur)) {
                        $cur = $cur[$p] ?? null;
                    } else {
                        $cur = null;
                    }
                    if ($cur === null) {
                        break;
                    }
                }
                return $cur;
            }

            if (is_object($model)) {
                return $model->{$valueDef} ?? '';
            }
            if (is_array($model)) {
                return $model[$valueDef] ?? '';
            }
        }

        return '';
    }

    protected static function writeCell($sheet, string $cellRef, $value, string $format): void
    {
        if ($value === null) {
            $sheet->setCellValueExplicit($cellRef, '', DataType::TYPE_STRING);
            return;
        }

        switch ($format) {
            case 'number':
                if (is_numeric($value)) {
                    $sheet->setCellValueExplicit($cellRef, (string)$value, DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueExplicit($cellRef, (string)$value, DataType::TYPE_STRING);
                }
                break;

            case 'date':
                $str = (string)$value;
                $sheet->setCellValueExplicit($cellRef, self::sanitizeFormula($str), DataType::TYPE_STRING);
                break;

            case 'string':
            default:
                $str = is_scalar($value) ? (string)$value : '';
                $sheet->setCellValueExplicit($cellRef, self::sanitizeFormula($str), DataType::TYPE_STRING);
                break;
        }
    }

    /**
     * ป้องกัน Excel/CSV formula injection
     * ถ้าค่าขึ้นต้นด้วย = + - @ tab \r ให้ prefix ด้วย ' (single quote)
     */
    protected static function sanitizeFormula(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $first = $value[0];
        if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }
        return $value;
    }

    protected static function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^\p{L}\p{N}_\-\.]+/u', '_', $name);
        $name = trim($name, '._-');
        return $name === '' ? 'export' : $name;
    }

    protected static function sanitizeSheetTitle(string $title): string
    {
        $title = preg_replace('/[\/\\\\\?\*\[\]\:]/u', ' ', $title);
        $title = trim($title);
        if (mb_strlen($title) > 31) {
            $title = mb_substr($title, 0, 31);
        }
        return $title === '' ? 'Data' : $title;
    }

    /**
     * แปลงเลขคอลัมน์ → ตัวอักษร (1=A, 27=AA)
     */
    protected static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int)(($index - $mod) / 26);
        }
        return $letter;
    }

    protected static function sendResponse(Spreadsheet $spreadsheet, string $filename, Response $response): Response
    {
        // เขียนไฟล์ลง temp แล้วส่งกลับเป็น raw content
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        try {
            $writer = new Xlsx($spreadsheet);
            $writer->save($tmp);
            $content = file_get_contents($tmp);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        $response->format  = Response::FORMAT_RAW;
        $response->headers->set(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="' . $filename . '.xlsx"'
        );
        $response->headers->set('Cache-Control', 'max-age=0');
        $response->headers->set('Pragma', 'public');
        $response->content = $content;
        return $response;
    }

    /**
     * ดึงข้อมูลผู้ร่วมดำเนินงาน/ผู้เขียนร่วม จากตาราง work_contributor แบบ batch
     * (1 query สำหรับ ref_id ทุกตัวในหน้านั้น แทนที่จะ query ต่อแถว)
     *
     * @param string $refType  หนึ่งใน 'article','researchpro','academic_service','utilization'
     * @param array  $refIds   array ของ ref_id (เช่น projectID, article_id, ...)
     * @param array  $opts
     *      - showRole: bool (default true)  แสดงชื่อบทบาทท้ายชื่อ
     *      - showPct:  bool (default true)  แสดง % ท้ายชื่อ
     *      - separator: string (default '; ')
     *      - excludeUsernames: array  รายชื่อ username ที่ไม่ต้องการแสดง
     *                                  (เช่น ตัดเจ้าของรายการออก เพราะมีคอลัมน์อยู่แล้ว)
     * @return array  map [ref_id (int) => "ชื่อ1 (บทบาท 50%); ชื่อ2 ..."]
     */
    public static function fetchContributorsMap(string $refType, array $refIds, array $opts = []): array
    {
        $refIds = array_values(array_unique(array_filter(array_map('intval', $refIds))));
        if (empty($refIds)) {
            return [];
        }

        $showRole  = $opts['showRole']  ?? true;
        $showPct   = $opts['showPct']   ?? true;
        $separator = $opts['separator'] ?? '; ';
        $excludeUsernames = array_map('strval', $opts['excludeUsernames'] ?? []);

        // ดึง contributor พร้อม join ตาราง user
        $rows = (new \yii\db\Query())
            ->select([
                'wc.ref_id',
                'wc.username',
                'wc.role_code',
                'wc.contribution_pct',
                'wc.sort_order',
                'wc.wc_id',
                'u.uname',
                'u.luname',
            ])
            ->from(['wc' => 'work_contributor'])
            ->leftJoin(['u' => 'tb_user'], 'u.username = wc.username')
            ->where(['wc.ref_type' => $refType, 'wc.ref_id' => $refIds])
            ->orderBy(['wc.ref_id' => SORT_ASC, 'wc.sort_order' => SORT_ASC, 'wc.wc_id' => SORT_ASC])
            ->all();

        if (empty($rows)) {
            return [];
        }

        // ดึงชื่อบทบาท (cache ใน static)
        static $roleCache = null;
        if ($roleCache === null) {
            try {
                $roleCache = \app\models\WorkContributorRole::items() ?: [];
            } catch (\Throwable $e) {
                $roleCache = [];
            }
        }

        $map = [];
        foreach ($rows as $r) {
            $username = (string)($r['username'] ?? '');
            if ($username !== '' && in_array($username, $excludeUsernames, true)) {
                continue; // ข้ามชื่อที่ขอให้ไม่แสดง
            }

            $name = trim(((string)($r['uname'] ?? '')) . ' ' . ((string)($r['luname'] ?? '')));
            if ($name === '') {
                $name = $username !== '' ? $username : '-';
            }

            $extra = [];
            if ($showRole && !empty($r['role_code'])) {
                $extra[] = $roleCache[$r['role_code']] ?? $r['role_code'];
            }
            if ($showPct && $r['contribution_pct'] !== null && $r['contribution_pct'] !== '') {
                $extra[] = number_format((float)$r['contribution_pct'], 0) . '%';
            }
            if (!empty($extra)) {
                $name .= ' (' . implode(' ', $extra) . ')';
            }

            $rid = (int)$r['ref_id'];
            $map[$rid][] = $name;
        }

        foreach ($map as $rid => $names) {
            $map[$rid] = implode($separator, $names);
        }

        return $map;
    }

    /**
     * แปลงปี ค.ศ. → พ.ศ.
     */
    public static function toBuddhistYear($year): string
    {
        if ($year === null || $year === '') return '';
        return (string)((int)$year + 543);
    }

    /**
     * แปลงวันที่รูปแบบ Y-m-d → d/m/พ.ศ.
     */
    public static function formatThaiDate($date): string
    {
        if (empty($date)) return '';
        $ts = strtotime((string)$date);
        if ($ts === false) return (string)$date;
        $d  = date('d', $ts);
        $m  = date('m', $ts);
        $y  = (int)date('Y', $ts) + 543;
        return "{$d}/{$m}/{$y}";
    }
}

<?php

namespace app\components;

use Yii;
use yii\base\Component;

class SciProfileService extends Component
{
    /** cache time (วินาที) */
    public int $ttl = 86400; // 24 ชม.

    /**
     * คืนค่าเฉพาะ: academic_type_name, first_name, last_name, dept_name, faculty_name
     * โดยเชื่อมจาก personal_id (= username)
     */
    public function getByPersonalId(string $personalId): ?array
    {
        $personalId = trim($personalId);
        if ($personalId === '') return null;

        $cacheKey = 'hrm_profile_' . $personalId;
        $cache = Yii::$app->cache;

        if ($cache) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) return $cached;
        }

        $resp = Yii::$app->apiClient->get('/authen/list-profiles', [
            'personal_id' => $personalId,
        ])->send();

        if (!$resp->isOk) {
            Yii::warning(['list-profiles not ok', 'code' => $resp->statusCode, 'pid' => $personalId], __METHOD__);
            return null;
        }

        $json = $resp->data;

        $row = (is_array($json) && !empty($json['data']) && is_array($json['data']))
            ? ($json['data'][0] ?? null)
            : null;

        if (!is_array($row)) return null;

        $picked = [
            'academic_type_name' => $row['academic_type_name'] ?? null,
            'first_name'         => $row['first_name'] ?? null,
            'last_name'          => $row['last_name'] ?? null,
            'dept_name'          => $row['dept_name'] ?? null,
            'faculty_name'       => $row['faculty_name'] ?? null,
        ];

        if ($cache) {
            $cache->set($cacheKey, $picked, $this->ttl);
        }

        return $picked;
    }

    /** ดึงหลายคน (ใช้ cache ลดการยิงซ้ำ) */
    public function getMap(array $personalIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('trim', $personalIds))));
        $map = [];
        foreach ($ids as $pid) {
            $map[$pid] = $this->getByPersonalId($pid);
        }
        return $map;
    }

    /** เผื่อทำปุ่ม refresh */
    public function clearCache(string $personalId): void
    {
        $personalId = trim($personalId);
        if ($personalId !== '' && Yii::$app->cache) {
            Yii::$app->cache->delete('hrm_profile_' . $personalId);
        }
    }
}

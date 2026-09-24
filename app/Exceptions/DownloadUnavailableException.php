<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * ส่งไฟล์ให้ลูกค้าไม่ได้ในตอนนี้ — controller เลือกเองว่าจะตอบเป็น JSON (แอป) หรือพากลับหน้าเว็บ
 *
 * ข้อความทั้งสองแบบห้ามเอ่ยถึง GitHub: ลูกค้าต้องไม่รู้ว่าไฟล์เก็บอยู่ที่ไหน
 */
class DownloadUnavailableException extends RuntimeException
{
    private function __construct(
        public readonly int $status,
        string $error,
        public readonly string $customerMessage,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct($error);
    }

    /** เวอร์ชันนี้ไม่มีไฟล์ให้โหลดเลย (release ไม่มี asset, เวอร์ชันที่สร้างมือไม่มีลิงก์) */
    public static function missing(): self
    {
        return new self(404, 'No file available for this version', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
    }

    /** มีไฟล์ แต่ดึงจากต้นทางไม่ได้ตอนนี้ — ลองใหม่ได้ */
    public static function unreachable(): self
    {
        return new self(502, 'Could not get the file, please try again later', 'ดาวน์โหลดไม่สำเร็จชั่วคราว กรุณาลองใหม่อีกครั้ง');
    }

    /** ช่องส่งไฟล์เต็ม (config downloads.max_concurrent_streams) */
    public static function busy(int $retryAfter): self
    {
        return new self(
            503,
            'Too many downloads right now, please try again in a minute',
            'ขณะนี้มีผู้ดาวน์โหลดพร้อมกันจำนวนมาก กรุณาลองใหม่อีกสักครู่',
            $retryAfter,
        );
    }
}

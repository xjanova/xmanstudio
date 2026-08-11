@include('errors.layout', [
    'code' => 500,
    'titleTh' => 'ระบบขัดข้อง',
    'titleEn' => 'Server error',
    'bodyTh' => 'เกิดข้อผิดพลาดฝั่งเซิร์ฟเวอร์ ทีมงานได้รับแจ้งแล้ว กรุณาลองใหม่อีกครั้งในอีกสักครู่',
    'bodyEn' => 'Something went wrong on our side. The team has been notified.',
    'art' => 'hero-error',
])

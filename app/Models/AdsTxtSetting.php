<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdsTxtSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ads_txt_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'content',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get the singleton instance of ads.txt settings.
     */
    public static function getInstance(): self
    {
        $setting = self::first();

        if (! $setting) {
            $setting = self::create([
                'content' => implode("\n", [
                    '# Google AdSense',
                    '# google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0',
                    '',
                    '# Add your ads.txt entries here',
                    '# Format: domain, publisher ID, relationship, certification authority ID',
                ]),
                'enabled' => true,
            ]);
        }

        return $setting;
    }
}

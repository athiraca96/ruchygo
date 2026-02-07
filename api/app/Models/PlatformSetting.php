<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, $value): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public static function getMultiple(array $keys): array
    {
        $settings = static::whereIn('key', $keys)->pluck('value', 'key')->toArray();

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $settings[$key] ?? null;
        }

        return $result;
    }

    public static function setMultiple(array $data): void
    {
        foreach ($data as $key => $value) {
            static::setValue($key, $value);
        }
    }
}

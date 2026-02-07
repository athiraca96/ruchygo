<?php

namespace App\Services;

use App\Models\PlatformSetting;

class PlatformSettingService
{
    public function get(string $key, $default = null)
    {
        $value = PlatformSetting::getValue($key);

        if ($value === null) {
            // Check config defaults
            $configDefault = config("platform.defaults.{$key}");
            return $configDefault ?? $default;
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        PlatformSetting::setValue($key, $value);
    }

    public function getMultiple(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function setMultiple(array $data): void
    {
        PlatformSetting::setMultiple($data);
    }

    public function getPlatformFeePercentage(): float
    {
        return (float) $this->get('platform_fee_percentage', 5);
    }

    public function getShippingFee(): float
    {
        return (float) $this->get('shipping_fee', 50);
    }

    public function getFreeShippingThreshold(): float
    {
        return (float) $this->get('free_shipping_threshold', 500);
    }

    public function getReturnWindowDays(): int
    {
        return (int) $this->get('return_window_days', 7);
    }

    public function getReplacementWindowDays(): int
    {
        return (int) $this->get('replacement_window_days', 7);
    }

    public function calculatePlatformFee(float $subtotal): float
    {
        return round($subtotal * ($this->getPlatformFeePercentage() / 100), 2);
    }

    public function calculateShippingFee(float $subtotal): float
    {
        if ($subtotal >= $this->getFreeShippingThreshold()) {
            return 0;
        }
        return $this->getShippingFee();
    }

    public function getAllSettings(): array
    {
        $keys = [
            'platform_fee_percentage',
            'shipping_fee',
            'free_shipping_threshold',
            'return_window_days',
            'replacement_window_days',
        ];

        return $this->getMultiple($keys);
    }
}

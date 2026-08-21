<?php

namespace App\Enums;

enum CityAreaEnum: string {
    // الدول
    case SYRIA = 'سوريا';
    case SAUDI_ARABIA = 'السعودية';
    case UAE = 'الإمارات';
    case EGYPT = 'مصر';
    case JORDAN = 'الأردن';

    /**
     * جلب كل الدول مع مدنها
     */
    public static function allCountries(): array {
        return [
            'سوريا' => ['دمشق', 'حلب', 'اللاذقية', 'حمص', 'حماة', 'طرطوس'],
            'السعودية' => ['الرياض', 'جدة', 'مكة المكرمة', 'الدمام', 'المدينة المنورة'],
            'الإمارات' => ['دبي', 'أبوظبي', 'الشارقة', 'العين'],
            'مصر' => ['القاهرة', 'الإسكندرية', 'الجيزة'],
            'الأردن' => ['عمان', 'إربد', 'الزرقاء'],
        ];
    }

    /**
     * جلب كل الدول
     */
    public static function countries(): array {
        return array_keys(self::allCountries());
    }

    /**
     * جلب مدن دولة معينة
     */
    public static function citiesOf(string $country): array {
        return self::allCountries()[$country] ?? [];
    }

    /**
     * جلب كل المدن
     */
    public static function allCities(): array {
        $cities = [];
        foreach (self::allCountries() as $countryCities) {
            $cities = array_merge($cities, $countryCities);
        }
        return $cities;
    }

    /**
     * التحقق من صحة الدولة
     */
    public static function isValidCountry(string $country): bool {
        return in_array($country, self::countries());
    }

    /**
     * التحقق من صحة المدينة
     */
    public static function isValidCity(string $city): bool {
        return in_array($city, self::allCities());
    }
}

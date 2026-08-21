<?php

namespace App\Http\Controllers;

use App\Enums\CityAreaEnum;
use Illuminate\Http\Request;

class LocationController extends Controller {
    /**
     * عرض كل الدول مع مدنها
     */
    public function countries() {
        return response()->json([
            'success' => true,
            'message' => 'Countries retrieved successfully',
            'data' => CityAreaEnum::allCountries()
        ]);
    }

    /**
     * عرض مدن دولة معينة
     */
    public function cities(Request $request) {
        $validated = $request->validate([
            'country' => 'required|string'
        ]);

        $cities = CityAreaEnum::citiesOf($validated['country']);

        if (empty($cities)) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cities retrieved successfully',
            'data' => $cities
        ]);
    }
}

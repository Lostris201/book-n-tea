<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Legacy\LegacyMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function __construct(private readonly LegacyMenu $menu) {}

    public function index(): JsonResponse
    {
        return response()->json($this->menu->export());
    }

    /** Transitional: legacy admin.js saveAll(). Filament replaces this; can return 410 after cutover. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        if (! is_array($data['products'] ?? null) || ! array_is_list($data['products'])) {
            return response()->json(['error' => 'Geçersiz menü verisi.'], 400);
        }

        $count = $this->menu->import($data);

        return response()->json(['success' => true, 'count' => $count]);
    }
}

<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/test-upload', function (Request $request) {
    try {
        $path = $request->file('test')->store('presentations', 'public');
        return response()->json(['success' => true, 'path' => $path]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    }
});

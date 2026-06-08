<?php

namespace App\Http\Controllers;

class APKController extends Controller
{
    public function download()
    {
        $filePath = public_path('apk/app-release.apk');

        if (! file_exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="'.'magic-builders.apk"',
        ]);
    }
}

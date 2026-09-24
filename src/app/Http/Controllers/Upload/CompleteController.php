<?php

namespace App\Http\Controllers\Upload;

use App\Domain\Media\Services\CompleteUploadService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompleteController extends Controller
{
    public function store(Request $request, string $ulid, CompleteUploadService $service): JsonResponse
    {
        $media = $service->complete($request->user(), $ulid);

        return response()->json(['status' => $media->status->value]);
    }
}

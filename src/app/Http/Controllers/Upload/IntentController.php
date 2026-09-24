<?php

namespace App\Http\Controllers\Upload;

use App\Domain\Media\Data\UploadIntentData;
use App\Domain\Media\Enums\MediaCollection;
use App\Domain\Media\Services\UploadIntentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\UploadIntentRequest;
use Illuminate\Http\JsonResponse;

class IntentController extends Controller
{
    public function store(UploadIntentRequest $request, UploadIntentService $service): JsonResponse
    {
        $data = new UploadIntentData(
            collection: MediaCollection::from($request->validated('collection')),
            filename: $request->validated('filename'),
            size: (int) $request->validated('size'),
            declaredMime: $request->validated('mime'),
        );

        $ticket = $service->create($request->user(), $data);

        return response()->json($ticket->toArray(), 201);
    }
}

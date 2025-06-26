<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{

    public DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function index()
    {
        $docs = $this->documentService->listActive();
        return DocumentResource::collection($docs)->toArray(request());
    }

    public function store(Request $request)
    {
        $doc = $this->documentService->store($request);
        return response()->json(['message' => 'PDF saved successfully.', 'data' => $doc], 201);
    }

    public function update(Request $request, $id)
    {
        $doc = $this->documentService->update($request, $id);
        return response()->json(['message' => 'PDF updated successfully.', 'data' => $doc]);
    }

    public function destroy($id)
    {
        $this->documentService->trash($id);
        return response()->json(['message' => 'Document moved to trash']);
    }

    public function trash($id)
    {
        $this->documentService->trash($id);
        return response()->json(['message' => 'Document trashed']);
    }

    public function archive($id)
    {
        $this->documentService->archive($id);
        return response()->json(['message' => 'Document archived']);
    }

    public function restore($id)
    {
        $this->documentService->restore($id);
        return response()->json(['message' => 'Document restored']);
    }

    public function forceDelete($id)
    {
        $this->documentService->forceDelete($id);
        return response()->json(['message' => 'Document permanently deleted']);
    }

    public function listArchive()
    {
        return $this->documentService->listArchive();
    }

    public function listTrash()
    {
        return $this->documentService->listTrash();
    }
}

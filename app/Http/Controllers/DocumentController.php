<?php

namespace App\Http\Controllers;

use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\SharedDocument;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class   DocumentController extends Controller
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
        $isResave = $request->boolean('isResave');

        if ($isResave) {
            $doc = $this->documentService->updateSharedDocument($request);
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Shared PDF re-saved successfully.',
                    'data' => $doc
                ], Response::HTTP_OK);
            } else {
                return view('thank-you');
            }
        }

        $doc = $this->documentService->store($request);
        return response()->json([
            'message' => 'PDF saved successfully.',
            'data' => $doc
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $doc = $this->documentService->update($request, $id);
        return response()->json([
            'message' => 'PDF updated successfully.',
            'data' => $doc
        ], Response::HTTP_OK);
    }

    public function shareDocument(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required',
            'employee_id' => 'required|exists:employees,id',
        ]);

        try {
            $doc = $this->documentService->share($validated);
            return response()->json([
                'message' => 'PDF shared successfully.',
                'data' => $doc
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Document sharing failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeView($shared_document_id, $document_pdf_id, $employee_id)
    {
        $shared_document = SharedDocument::find($shared_document_id);

        if ($shared_document->isExpired()) {
            return view('link-expired');
        }

        $vueUrl = env('FRONTEND_URL') . "/view-document?shared_document_id=$shared_document_id&document_pdf_id=$document_pdf_id&employee_id=$employee_id&is_employee=true";

        return redirect()->away($vueUrl);
    }

    public function track()
    {
        $doc = $this->documentService->trackDocument();

        return response()->json($doc, Response::HTTP_OK);
    }

    public function destroy($id)
    {
        $this->documentService->trash($id);
        return response()->json([
            'message' => 'Document moved to trash'
        ], Response::HTTP_OK);
    }

    public function trash($id)
    {
        $this->documentService->trash($id);
        return response()->json([
            'message' => 'Document trashed'
        ], Response::HTTP_OK);
    }

    public function archive($id)
    {
        $this->documentService->archive($id);
        return response()->json([
            'message' => 'Document archived'
        ], Response::HTTP_OK);
    }

    public function restore($id)
    {
        $this->documentService->restore($id);
        return response()->json([
            'message' => 'Document restored'
        ], Response::HTTP_OK);
    }

    public function forceDelete($id)
    {
        $this->documentService->forceDelete($id);
        return response()->json([
            'message' => 'Document permanently deleted'
        ], Response::HTTP_OK);
    }

    public function listArchive()
    {
        $docs = $this->documentService->listArchive();
        return response()->json($docs, Response::HTTP_OK);
    }

    public function listTrash()
    {
        $docs = $this->documentService->listTrash();
        return response()->json($docs, Response::HTTP_OK);
    }
}

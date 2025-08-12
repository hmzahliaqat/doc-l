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

class DocumentController extends Controller
{
    public DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Download a document from the specified path
     *
     * @param string $path The path to the document
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadDocument($path)
    {
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists($fullPath)) {
            return response()->json([
                'message' => 'File not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . basename($fullPath) . '"'
        ]);
    }

    public function index()
    {
        $docs = $this->documentService->listActive();
        return DocumentResource::collection($docs)->toArray(request());
    }

    public function externalDoc($id)
    {
       $doc = Document::where('pdf_id', $id)->firstOrFail();

       return response()->json($doc, Response::HTTP_OK);

    }

    public function store(Request $request)
    {
        $isResave = $request->boolean('isResave');

        if ($isResave) {
            try {
                $doc = $this->documentService->updateSharedDocument($request);
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Shared PDF re-saved successfully.',
                        'data' => $doc
                    ], Response::HTTP_OK);
                } else {
                    return view('thank-you');
                }
            } catch (\Exception $e) {
                Log::error('Failed to update shared document', ['error' => $e->getMessage()]);
                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'Failed to update shared document: ' . $e->getMessage()
                    ], Response::HTTP_NOT_FOUND);
                } else {
                    return view('error', ['message' => 'Failed to update shared document: ' . $e->getMessage()]);
                }
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
            'employee_id' => 'required_without:employee_ids|exists:employees,id',
            'employee_ids' => 'required_without:employee_id|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            $shares = $this->documentService->share($validated);
            return response()->json([
                'success' => true,
                'message' => 'Document shared successfully',
                'shares' => $shares
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Document sharing failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Share multiple documents with one or more employees
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkShareDocuments(Request $request)
    {
        $validated = $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'required',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            $result = $this->documentService->shareMultipleDocuments($validated);

            return response()->json([
                'success' => true,
                'message' => 'Documents shared successfully',
                'total_shares' => $result['total_shares'],
                'shares' => $result['shares']
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Bulk document sharing failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function employeeView($shared_document_id, $document_pdf_id, $employee_id)
    {
        $shared_document = SharedDocument::find($shared_document_id);

        if (!$shared_document) {
            Log::error('Shared document not found', ['shared_document_id' => $shared_document_id]);
            return view('error', ['message' => 'Shared document not found. It may have been deleted or the link is invalid.']);
        }

        if ($shared_document->isExpired()) {
            return view('link-expired');
        }

        // Ensure consistent case for the route path
        $vueUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') . "/view-document?shared_document_id=$shared_document_id&document_pdf_id=$document_pdf_id&employee_id=$employee_id&is_employee=true";

        // Log the redirect URL for debugging
        Log::info('Redirecting to frontend', ['url' => $vueUrl]);

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

    /**
     * Get all signed documents (where status = 1)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listSigned()
    {
        $docs = $this->documentService->listSigned();
        return response()->json($docs, Response::HTTP_OK);
    }

    /**
     * Send a reminder email for a shared document
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remindDocument(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:shared_documents,id',
        ]);

        try {
            $this->documentService->remindEmployee($validated['id']);
            return response()->json([
                'message' => 'Reminder email sent successfully.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Document reminder failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function download(Request $request)
    {
        $path = $request->file_name;
        Log::info('Downloading file: ' . $path);

        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $file = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        $filename = basename($path);

        if (!file_exists($file)) {
            return response()->json(['error' => 'File not found on disk'], 404);
        }

        // Get file info
        $fileSize = filesize($file);
        $mimeType = mime_content_type($file);

        // Use file() for standard downloads
        return response()->file($file, [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Special method for cross-origin downloads that bypasses CSRF protection
     * and adds explicit CORS headers
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadCors(Request $request)
    {
        $path = $request->file_name;
        Log::info('Cross-origin downloading file: ' . $path);

        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $file = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        $filename = basename($path);

        if (!file_exists($file)) {
            return response()->json(['error' => 'File not found on disk'], 404);
        }

        // Get file info
        $fileSize = filesize($file);
        $mimeType = mime_content_type($file);

        // Create a streaming response for better cross-origin handling
        $stream = fopen($file, 'rb');

        return response()->stream(
            function() use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            },
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Access-Control-Allow-Origin' => config('cors.allowed_origins')[0],
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Type, Content-Length'
            ]
        );
    }
}

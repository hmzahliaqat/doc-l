<?php

namespace App\Services;

use App\Http\Requests\ShareDocumentRequest;
use App\Mail\ShareDocumentMail;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Partial;
use App\Models\SharedDocument;
use App\Traits\LogsDocumentActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class DocumentService
{
    use LogsDocumentActions;
    public function listActive()
    {
        return Document::where('user_id', Auth::id())->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'PDFId' => 'required|string|unique:documents,pdf_id',
            'name' => 'required|string',
            'PDFBase64' => 'required|string',
            'pages' => 'required|integer',
            'canvas' => 'nullable|array',
            'updateDate' => 'required',
        ]);

        $base64 = $data['PDFBase64'];
        if (str_starts_with($base64, 'data:')) {
            [, $base64] = explode(',', $base64, 2);
        }

        $decoded = base64_decode($base64);
        if (!$decoded) {
            throw ValidationException::withMessages(['PDFBase64' => 'Invalid PDF base64 encoding.']);
        }

        $filename = $data['PDFId'] . '.pdf';
        $filePath = 'pdfs/' . $filename;
        Storage::disk('public')->put($filePath, $decoded);

     $document = Document::create([
            'pdf_id' => $data['PDFId'],
            'name' => $data['name'],
            'file_path' => $filePath,
            'pages' => $data['pages'],
            'canvas' => $data['canvas'] ?? null,
            'user_id' => Auth::id(),
            'update_date' => $data['updateDate'],
        ]);

        $this->logDocumentAction(Auth::id(), $document->id, null, 'created');

        return $document;
    }

    public function update(Request $request, string $pdfId)
    {
        $document = Document::where('pdf_id', $pdfId)->firstOrFail();

        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'pages' => 'sometimes|required|integer',
            'canvas' => 'nullable|array',
            'PDFBase64' => 'nullable|string',
        ]);

        if (!empty($data['PDFBase64'])) {
            $base64 = $data['PDFBase64'];
            if (str_starts_with($base64, 'data:')) {
                [, $base64] = explode(',', $base64, 2);
            }

            $decoded = base64_decode($base64);
            if (!$decoded) {
                throw ValidationException::withMessages(['PDFBase64' => 'Invalid PDF base64 encoding.']);
            }

            $filePath = $document->file_path ?? ('pdfs/' . $document->pdf_id . '.pdf');
            Storage::disk('public')->put($filePath, $decoded);
            $data['file_path'] = $filePath;
        }
        $this->logDocumentAction(Auth::id(), $document->id, null, 'updated');

        $document->update($data);
        return $document;
    }

    public function share(array $data)
    {
        $document = Document::where('pdf_id', $data['document_id'])->first();

        if (!$document) {
            throw new \Exception("Document not found.");
        }

        // Check if the authenticated user has access to this document
        if ($document->user_id !== Auth::id()) {
            throw new \Exception("You don't have permission to share this document.");
        }

        // Convert single employee ID to array for consistent processing
        $employeeIds = [];
        if (isset($data['employee_id'])) {
            $employeeIds = [$data['employee_id']];
        } elseif (isset($data['employee_ids'])) {
            $employeeIds = $data['employee_ids'];
        }

        $shares = [];

        foreach ($employeeIds as $employeeId) {
            $existingShare = SharedDocument::where([
                'document_id' => $document->id,
                'employee_id' => $employeeId
            ])->first();

            if ($existingShare && $existingShare->isExpired()) {
                $existingShare->delete();
                $existingShare = null;
            }

            if ($existingShare) {
                // Include existing share in response
                $shares[] = [
                    'document_id' => $document->pdf_id,
                    'employee_id' => $employeeId,
                    'shared_at' => $existingShare->created_at->toIso8601String()
                ];
            } else {
                // Create new share
                $sharedDocument = $this->shareWithEmployee($document->id, $employeeId);

                $shares[] = [
                    'document_id' => $document->pdf_id,
                    'employee_id' => $employeeId,
                    'shared_at' => $sharedDocument->created_at->toIso8601String()
                ];

                $this->logDocumentAction(Auth::id(), $document->id, $employeeId, 'shared');
            }
        }

        return $shares;
    }


    protected function shareWithEmployee(int $documentId, int $employeeId): SharedDocument
    {
        try {
            DB::beginTransaction();
            $employee = Employee::find($employeeId);
            if (!$employee) {
                throw new \Exception("Employee not found.");
            }

            $sharedDocument = SharedDocument::create([
                'user_id' => $employee->user_id,
                'document_id' => $documentId,
                'employee_id' => $employeeId,
                'access_hash' => hash('sha256', Str::random(40) . time() . config('app.key')),
                'status' => 0,
            ]);

            $document_pdf_id = Document::where('id', $documentId)->value('pdf_id');

            Mail::to($employee->email)->send(new ShareDocumentMail($sharedDocument->id, $document_pdf_id, $employeeId, 'mail'));
            DB::commit();
            return $sharedDocument;
        }
        catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Share multiple documents with one or more employees
     *
     * @param array $data
     * @return array
     */
    public function shareMultipleDocuments(array $data)
    {
        $documentIds = $data['document_ids'] ?? [];
        $employeeIds = $data['employee_ids'] ?? [];

        if (empty($documentIds) || empty($employeeIds)) {
            throw new \Exception("Document IDs and employee IDs are required.");
        }

        $allShares = [];
        $totalShares = 0;
        $unauthorizedDocuments = [];

        foreach ($documentIds as $documentId) {
            $document = Document::where('pdf_id', $documentId)->first();

            if (!$document) {
                continue; // Skip documents that don't exist
            }

            // Check if the authenticated user has access to this document
            if ($document->user_id !== Auth::id()) {
                $unauthorizedDocuments[] = $documentId;
                continue; // Skip documents the user doesn't have permission to share
            }

            foreach ($employeeIds as $employeeId) {
                $existingShare = SharedDocument::where([
                    'document_id' => $document->id,
                    'employee_id' => $employeeId
                ])->first();

                if ($existingShare && $existingShare->isExpired()) {
                    $existingShare->delete();
                    $existingShare = null;
                }

                if ($existingShare) {
                    // Include existing share in response
                    $allShares[] = [
                        'document_id' => $document->pdf_id,
                        'employee_id' => $employeeId,
                        'shared_at' => $existingShare->created_at->toIso8601String()
                    ];
                } else {
                    // Create new share
                    try {
                        $sharedDocument = $this->shareWithEmployee($document->id, $employeeId);

                        $allShares[] = [
                            'document_id' => $document->pdf_id,
                            'employee_id' => $employeeId,
                            'shared_at' => $sharedDocument->created_at->toIso8601String()
                        ];

                        $this->logDocumentAction(Auth::id(), $document->id, $employeeId, 'shared');
                        $totalShares++;
                    } catch (\Exception $e) {
                        // Log error but continue with other shares
                        \Log::error("Failed to share document {$document->pdf_id} with employee {$employeeId}: " . $e->getMessage());
                    }
                }
            }
        }

        // If there were unauthorized documents, log them
        if (!empty($unauthorizedDocuments)) {
            \Log::warning("User " . Auth::id() . " attempted to share documents they don't own", [
                'unauthorized_documents' => $unauthorizedDocuments
            ]);
        }

        return [
            'total_shares' => $totalShares,
            'shares' => $allShares
        ];
    }

    /**
     * Send a reminder email to an employee for a shared document
     *
     * @param int $sharedDocumentId The ID of the shared document
     * @return bool Whether the reminder was sent successfully
     * @throws \Exception If the shared document or employee is not found
     */
    public function remindEmployee(int $sharedDocumentId): bool
    {
        $sharedDocument = SharedDocument::find($sharedDocumentId);

        if (!$sharedDocument) {
            throw new \Exception("Shared document not found.");
        }

        $employee = Employee::find($sharedDocument->employee_id);

        if (!$employee) {
            throw new \Exception("Employee not found.");
        }

        $document_pdf_id = Document::where('id', $sharedDocument->document_id)->value('pdf_id');

        Mail::to($employee->email)->send(new ShareDocumentMail($sharedDocument->id, $document_pdf_id, $sharedDocument->employee_id, 'reminder'));

        $this->logDocumentAction(Auth::id(), $sharedDocument->document_id, $sharedDocument->employee_id, 'reminded');

        return true;
    }


    public function updateSharedDocument(Request $request)
    {
        $pdfBase64 = $request->input('PDFBase64');

        $randomSuffix = uniqid('', true); // or Str::uuid()
        $originalName = $request->input('name', 'document');
        $fileName = $originalName . '-' . $randomSuffix . '.pdf';

        $path = 'signed_documents/' . $fileName;

        $base64Str = preg_replace('/^data:application\/pdf;base64,/', '', $pdfBase64);

        Storage::disk('public')->put($path, base64_decode($base64Str));

        $shared_document = SharedDocument::find($request->shared_document_id);

        if (!$shared_document) {
            throw new \Exception("Shared document not found with ID: " . $request->shared_document_id);
        }

        $shared_document->shared_document_name = $fileName;
        $shared_document->file_path = $path;
        $shared_document->canvas = $request->canvas;
        $shared_document->pages = $request->pages;
        $shared_document->status = 1;
        $shared_document->signed_at = now();
        $shared_document->valid_for = 0;
        $shared_document->save();

        return $shared_document;
    }

    public function trackDocument()
    {

        $users = SharedDocument::where('user_id', Auth::id())->with('user', 'document', 'employee')->get();

        $totalSharedDocumentCount = SharedDocument::where('user_id', Auth::id())->get()->count();
        $totalSignedDocumentCount = SharedDocument::where('user_id', Auth::id())->where('status', 1)->get()->count();
        $totalPendingDocumentCount = SharedDocument::where('user_id', Auth::id())->where('status', 0)->get()->count();

        return ([
            'users' => $users,
            'totalDocuments' => $totalSharedDocumentCount,
            'totalSignedDocuments' => $totalSignedDocumentCount,
            'totalPendingDocuments' => $totalPendingDocumentCount,
        ]);

    }


    public function storeSignatures($data)
    {
        $document_id = Document::where('pdf_id', $data['pdf_id'])->value('id');

        $input = $data['partials'];
        $fileType = $data['type'];

        if (str_starts_with($input, 'data:')) {
            [, $base64] = explode(',', $input, 2);
            $decoded = base64_decode($base64, true);

            if (!$decoded) {
                throw ValidationException::withMessages(['partials' => 'Invalid base64 image data.']);
            }

            $filename = 'partial-' . Str::random(10) . '.png';
            $filePath = 'partials/' . $filename;

            Storage::disk('public')->put($filePath, $decoded);

            return Partial::create([
                'document_id' => $document_id,
                'employee_id' => $data['employee_id'] ?? null,
                'user_id' => Auth::id(),
                'file_path' => $filePath,
                'file_type' => $fileType,
                'literal' => null,
            ]);
        }

        return Partial::create([
            'document_id' => $document_id,
            'employee_id' => $data['employee_id'] ?? null,
            'user_id' => Auth::id(),
            'file_path' => $input,
            'file_type' => $fileType,
        ]);
    }


    public function trash(string $pdfId)
    {
        $doc = Document::where('pdf_id', $pdfId)->firstOrFail();
        $doc->delete();
        return $doc;
    }

    public function archive(string $pdfId)
    {
        $doc = Document::where('pdf_id', $pdfId)->firstOrFail();
        $doc->update(['is_archived' => true]);
        return $doc;
    }

    public function restore(string $pdfId)
    {
        $doc = Document::onlyTrashed()->where('pdf_id', $pdfId)->firstOrFail();
        $doc->restore();
        return $doc;
    }

    public function forceDelete(string $pdfId)
    {
        $doc = Document::onlyTrashed()->where('pdf_id', $pdfId)->firstOrFail();

        if ($doc->file_path && Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $doc->forceDelete();
        return $doc;
    }

    public function listArchive()
    {
        return Document::where('is_archived', true)->get();
    }

    public function listTrash()
    {
        return Document::onlyTrashed()->get();
    }

    /**
     * Get all signed documents (where status = 1)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listSigned()
    {
        return SharedDocument::getSigned();
    }
}

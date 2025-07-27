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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class DocumentService
{
    use LogsDocumentActions;
    public function listActive()
    {
        return Document::where('is_archived', false)->get();
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

        return Document::create([
            'pdf_id' => $data['PDFId'],
            'name' => $data['name'],
            'file_path' => $filePath,
            'pages' => $data['pages'],
            'canvas' => $data['canvas'] ?? null,
            'user_id' => 1, // Replace with auth()->id()
            'update_date' => $data['updateDate'],
        ]);
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

        $document->update($data);
        return $document;
    }

    public function share(array $data)
    {
        $document = Document::where('pdf_id', $data['document_id'])->first();

        if (!$document) {
            throw new \Exception("Document not found.");
        }

        $existingShare = SharedDocument::where([
            'document_id' => $document->id,
            'employee_id' => $data['employee_id']
        ])->first();

        if ($existingShare && $existingShare->isExpired()) {
            $existingShare->delete();
        }

        $alreadyShared = SharedDocument::where([
            'document_id' => $document->id,
            'employee_id' => $data['employee_id']
        ])->first();

        if ($alreadyShared) {
            throw new \Exception("Document already shared with this employee.");
        }

        $this->shareWithEmployee($document->id, $data['employee_id']);
        $this->logDocumentAction(Auth::id(), $document->id, $data['employee_id'], 'shared');

        return $document;
    }


    protected function shareWithEmployee(int $documentId, int $employeeId): SharedDocument
    {
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

        return $sharedDocument;
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

        $users = SharedDocument::with('user', 'document', 'employee')->get();

        $totalSharedDocumentCount = SharedDocument::get()->count();
        $totalSignedDocumentCount = SharedDocument::where('status', 1)->get()->count();
        $totalPendingDocumentCount = SharedDocument::where('status', 0)->get()->count();

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

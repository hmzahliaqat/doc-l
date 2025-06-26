<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentService
{
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
}

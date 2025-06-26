<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'PDFId' => $this->pdf_id,
            'name' => $this->name,
            'updateDate' => $this->updated_at->timestamp * 1000,
            'PDFBase64' => 'data:application/pdf;base64,' . base64_encode(file_get_contents(public_path('storage/' . $this->file_path)),        ),
            'pages' => (int) $this->pages,
            'canvas' => $this->canvas ?? [],
            'trashDate' => 0,
            'isUpdate' => false,
        ];

    }
}

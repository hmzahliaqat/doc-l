<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait LogsDocumentActions
{
    public function logDocumentAction(int $user_id, int $documentId, $employeeId = null, string $action)
    {
        $logData = Log::create([
            'user_id'       => $user_id,
            'document_id'   => $documentId,
            'action'        => $action,
            'employee_id'   => $employeeId,
        ]);

        return $logData;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Partial;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignatureController extends Controller
{
    public DocumentService $documentService;

    public function __construct(DocumentService $documentService )
    {
        $this->documentService = $documentService;
    }

    public function index($employeeId, $type){

        $signatures = Partial::query()
            ->when(is_null($employeeId), function ($query) use ($employeeId, $type) {
                $query->where(['employee_id' => $employeeId, 'file_type' => $type]);
            }, function ($query) use ($type) {
                $query->whereNull('employee_id')
                    ->where(['user_id' => Auth::id() , 'file_type' => $type]);
            })
            ->get();

        return response()->json($signatures, 200);

    }

    public function store(Request $request){

        $data = $request->all();

      $data =  $this->documentService->storeSignatures($data);

        return response()->json($data, 200);

    }
}

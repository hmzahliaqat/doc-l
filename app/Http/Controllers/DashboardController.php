<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Employee;
use App\Models\Log;
use App\Models\SharedDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function index()
    {
        $total_documents = Document::where('user_id', Auth::id())->count();
        $total_employees = Employee::where('user_id', Auth::id())->count();
        $pending_signatures = SharedDocument::where(['user_id' => Auth::id(), 'status' => 0])->count();
        $completed_signatures = SharedDocument::where(['user_id' => Auth::id(), 'status' => 1])->count();
        $documents_shared = SharedDocument::where('user_id', Auth::id())->count();

//        $recent_documents = Document::with('user')
//            ->where('user_id', Auth::id())
//            ->orderBy('created_at', 'desc')
//            ->take(4)
//            ->get();

        $logs = Log::with('user', 'document', 'employee')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'total_documents' => $total_documents,
            'total_employees' => $total_employees,
            'pending_signatures' => $pending_signatures,
            'completed_signatures' => $completed_signatures,
            'documents_shared' => $documents_shared,
//            'recent_documents' => $recent_documents,
            'logs' => $logs,
        ], Response::HTTP_OK);
    }
}

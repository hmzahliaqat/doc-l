<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteEmployeeRequest;
use App\Http\Requests\EmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use League\Csv\Reader;
use Symfony\Component\HttpFoundation\Response;

class EmployeesController extends Controller
{
    public function index()
    {
        $employees = User::with('employees')->first()->employees;

        return response()->json($employees, Response::HTTP_OK);
    }

    public function save(StoreEmployeeRequest $request)
    {
        if ($request->has('id')) {
            $employee = Employee::find($request->id);

            if ($employee) {
                $employee->name = $request->name;
                $employee->email = $request->email;
                $employee->save();
            }

            return response()->json($employee, Response::HTTP_OK);
        } else {
            $employee = Employee::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_id' => Auth::id(),
            ]);

            return response()->json($employee, Response::HTTP_OK);
        }
    }

    public function delete(Request $request)
    {
        if ($request->has('ids')) {
            // Bulk delete
            Employee::whereIn('id', $request->ids)->delete();
            return response()->json('Employees deleted', Response::HTTP_OK);
        }

        if ($request->has('id')) {
            // Single delete
            $employee = Employee::findOrFail($request->id);
            $employee->delete();
            return response()->json('Employee deleted', Response::HTTP_OK);
        }

        return response()->json(['error' => 'No employee ID(s) provided'], Response::HTTP_BAD_REQUEST);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $csv = Reader::createFromPath($request->file('file')->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        $normalizedHeaders = array_map('strtolower', $csv->getHeader());
        $csv->setHeaderOffset(null);

        $records = $csv->getRecords();
        $employees = [];

        foreach ($records as $index => $record) {
            $normalizedRecord = [];
            foreach ($normalizedHeaders as $i => $header) {
                $normalizedRecord[$header] = $record[$i] ?? null;
            }

            Log::info("Processing Record at row $index", $normalizedRecord);

            $validator = Validator::make($normalizedRecord, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:employees,email',
            ]);

            if ($validator->fails()) {
                Log::warning("Validation failed at row $index", $validator->errors()->toArray());
                continue;
            }

            $employee = Employee::create([
                'name' => $normalizedRecord['name'],
                'email' => $normalizedRecord['email'],
                'user_id' => Auth::id(),
            ]);

            $employees[] = $employee;
        }

        if (!empty($employees)) {
            Log::info('Employees inserted successfully', $employees);
        } else {
            Log::warning('No valid employees were imported.');
        }

        return response()->json([
            'message' => 'CSV import completed',
            'imported_count' => count($employees),
            'data' => $employees,
        ], Response::HTTP_OK);
    }
}

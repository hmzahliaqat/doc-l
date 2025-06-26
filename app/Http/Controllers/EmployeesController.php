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

class EmployeesController extends Controller
{

    public function index()
    {
        $employees = User::with('employees')->first()->employees;

        return response()->json($employees, 200);
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
            return response()->json($employee, 200);
        } else {
            $employee = Employee::create([
                'name' => $request->name,
                'email' => $request->email,
                'user_id' => Auth::id(),
            ]);
            return response()->json($employee, 200);
        }
    }


    public function delete(Request $request)
    {
        if ($request->has('ids')) {
            // Bulk delete
            Employee::whereIn('id', $request->ids)->delete();
            return response()->json('Employees deleted', 200);
        }

        if ($request->has('id')) {
            // Single delete
            $employee = Employee::findOrFail($request->id);
            $employee->delete();
            return response()->json('Employee deleted', 200);
        }

        return response()->json(['error' => 'No employee ID(s) provided'], 400);
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048',
        ]);

        // Load the CSV
        $csv = Reader::createFromPath($request->file('file')->getPathname(), 'r');
        $csv->setHeaderOffset(0);

        // Normalize header names (lowercase)
        $normalizedHeaders = array_map('strtolower', $csv->getHeader());
        $csv->setHeaderOffset(null); // Clear original header offset

        $records = $csv->getRecords();

        $employees = [];

        foreach ($records as $index => $record) {
            // Normalize keys to lowercase
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
                continue; // Skip this record
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
        ], 200);
    }
}

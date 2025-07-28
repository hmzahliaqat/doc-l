<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\UserReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserReportController extends Controller
{
    protected $userReportService;

    public function __construct(UserReportService $userReportService)
    {
        $this->userReportService = $userReportService;
    }

    /**
     * Get user registration trends over time
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrationTrends(Request $request)
    {
        $period = $request->input('period', 'monthly'); // daily, weekly, monthly, yearly
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $trends = $this->userReportService->getRegistrationTrends($period, $startDate, $endDate);

        return response()->json([
            'data' => $trends,
        ], Response::HTTP_OK);
    }

    /**
     * Get most active users based on document uploads, shares, etc.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function activeUsers(Request $request)
    {
        $limit = $request->input('limit', 10);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $activityType = $request->input('activity_type'); // uploads, shares, all

        $activeUsers = $this->userReportService->getActiveUsers($limit, $startDate, $endDate, $activityType);

        return response()->json([
            'data' => $activeUsers,
        ], Response::HTTP_OK);
    }

    /**
     * Get storage usage per user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storageUsage(Request $request)
    {
        $limit = $request->input('limit', 10);
        $sortBy = $request->input('sort_by', 'desc'); // asc, desc

        $storageUsage = $this->userReportService->getStorageUsage($limit, $sortBy);

        return response()->json([
            'data' => $storageUsage,
        ], Response::HTTP_OK);
    }
}

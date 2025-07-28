<?php

namespace App\Services\Reports;

use App\Models\Document;
use App\Models\Log;
use App\Models\SharedDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserReportService
{
    /**
     * Get user registration trends over time
     *
     * @param string $period
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getRegistrationTrends(string $period = 'monthly', ?string $startDate = null, ?string $endDate = null): array
    {
        // Set default date range if not provided
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        // Default to last 12 months if no start date
        if (!$startDate) {
            switch ($period) {
                case 'daily':
                    $startDate = Carbon::now()->subDays(30);
                    break;
                case 'weekly':
                    $startDate = Carbon::now()->subWeeks(12);
                    break;
                case 'yearly':
                    $startDate = Carbon::now()->subYears(5);
                    break;
                case 'monthly':
                default:
                    $startDate = Carbon::now()->subMonths(12);
                    break;
            }
        } else {
            $startDate = Carbon::parse($startDate);
        }

        // Format for grouping based on period
        $dateFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u', // ISO week number
            'yearly' => '%Y',
            default => '%Y-%m', // monthly
        };

        // Query to get registration counts grouped by period
        $registrations = User::select(
            DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as date"),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Format the results
        $formattedResults = [];
        foreach ($registrations as $registration) {
            $label = match ($period) {
                'daily' => Carbon::parse($registration->date)->format('M d, Y'),
                'weekly' => 'Week ' . substr($registration->date, -2) . ', ' . substr($registration->date, 0, 4),
                'yearly' => $registration->date,
                default => Carbon::parse($registration->date . '-01')->format('M Y'),
            };

            $formattedResults[] = [
                'period' => $label,
                'count' => $registration->count,
                'raw_date' => $registration->date,
            ];
        }

        return [
            'period' => $period,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $formattedResults,
        ];
    }

    /**
     * Get most active users based on document uploads, shares, etc.
     *
     * @param int $limit
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string|null $activityType
     * @return array
     */
    public function getActiveUsers(int $limit = 10, ?string $startDate = null, ?string $endDate = null, ?string $activityType = null): array
    {
        // Set default date range if not provided
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subMonths(1);

        // Base query
        $query = Log::select('user_id', DB::raw('COUNT(*) as activity_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('user_id');

        // Filter by activity type if specified
        if ($activityType) {
            switch ($activityType) {
                case 'uploads':
                    $query->where('action', 'uploaded');
                    break;
                case 'shares':
                    $query->where('action', 'shared');
                    break;
                // Add more activity types as needed
            }
        }

        // Get the most active users
        $activeUsers = $query->orderBy('activity_count', 'desc')
            ->limit($limit)
            ->get();

        // Get user details
        $result = [];
        foreach ($activeUsers as $activeUser) {
            $user = User::find($activeUser->user_id);
            if ($user) {
                // Get activity breakdown
                $activityBreakdown = Log::select('action', DB::raw('COUNT(*) as count'))
                    ->where('user_id', $user->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('action')
                    ->get()
                    ->pluck('count', 'action')
                    ->toArray();

                $result[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'total_activity' => $activeUser->activity_count,
                    'activity_breakdown' => $activityBreakdown,
                    'created_at' => $user->created_at->toDateTimeString(),
                ];
            }
        }

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'activity_type' => $activityType ?? 'all',
            'users' => $result,
        ];
    }

    /**
     * Get storage usage per user
     *
     * @param int $limit
     * @param string $sortBy
     * @return array
     */
    public function getStorageUsage(int $limit = 10, string $sortBy = 'desc'): array
    {
        // Get all users
        $users = User::all();
        $usageData = [];

        foreach ($users as $user) {
            // Get all documents for the user
            $documents = Document::where('user_id', $user->id)->get();

            $totalSize = 0;
            $documentCount = $documents->count();

            // Calculate total storage used
            foreach ($documents as $document) {
                $filePath = $document->file_path;
                if (Storage::exists('public/' . $filePath)) {
                    $totalSize += Storage::size('public/' . $filePath);
                }
            }

            // Add to results if they have documents
            if ($documentCount > 0) {
                $usageData[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'document_count' => $documentCount,
                    'storage_used_bytes' => $totalSize,
                    'storage_used_formatted' => $this->formatBytes($totalSize),
                    'created_at' => $user->created_at->toDateTimeString(),
                ];
            }
        }

        // Sort by storage usage
        usort($usageData, function ($a, $b) use ($sortBy) {
            if ($sortBy === 'asc') {
                return $a['storage_used_bytes'] - $b['storage_used_bytes'];
            }
            return $b['storage_used_bytes'] - $a['storage_used_bytes'];
        });

        // Limit results
        $usageData = array_slice($usageData, 0, $limit);

        return [
            'sort_by' => $sortBy,
            'users' => $usageData,
        ];
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

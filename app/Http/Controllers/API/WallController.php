<?php

namespace App\Http\Controllers\API;

use App\Models\FundiAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WallController extends BaseAPIController
{
    public function arrivals(Request $request): JsonResponse
    {
        $accessKey = config('wall.access_key');

        if (empty($accessKey) || $request->query('key') !== $accessKey) {
            return $this->errorResponse('Invalid or missing access key', null, 403);
        }

        $eventKey = config('wall.scan_event_key');
        $sinceId = max(0, (int) $request->query('since_id', 0));

        $query = FundiAttendance::with('fundi.ward.district')
            ->where('event_key', $eventKey);

        if ($sinceId > 0) {
            $rows = $query->where('id', '>', $sinceId)
                ->orderBy('id')
                ->limit(200)
                ->get();
        } else {
            $rows = $query->orderByDesc('id')
                ->limit(200)
                ->get()
                ->sortBy('id')
                ->values();
        }

        $arrivals = $rows->map(fn (FundiAttendance $attendance) => [
            'id' => $attendance->id,
            'fname' => $attendance->fundi->fname,
            'lname' => $attendance->fundi->lname,
            'ranking' => $attendance->fundi->ranking,
            'ward' => $attendance->fundi->ward->name,
            'district' => $attendance->fundi->ward->district->name,
            'scanned_at' => $attendance->scanned_at,
        ]);

        return $this->successResponse([
            'arrivals' => $arrivals,
            'total' => FundiAttendance::where('event_key', $eventKey)->count(),
        ]);
    }
}

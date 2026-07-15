<?php

namespace Modules\Doctor\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Notification;
use App\Support\PaginationHelper;
use App\Support\TimeFormatter;
use Illuminate\Http\Request;

class DoctorNotificationController extends Controller
{
    public function index(Request $request)
    {
        [$page, $limit] = PaginationHelper::fromRequest($request);

        $query = Notification::where('user_id', $request->user()->id);

        if ($request->has('isRead')) {
            $query->where('is_read', filter_var($request->query('isRead'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(fn ($n) => [
            'id' => (string) $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'time' => TimeFormatter::humanDiff($n->created_at),
            'isRead' => (bool) $n->is_read,
            'priority' => $n->priority ?? 'Normal',
            'type' => $n->type,
        ]);

        return ApiResponse::paginated($paginator, $data);
    }

    public function markRead(Request $request, int $notificationId)
    {
        $notification = Notification::where('user_id', $request->user()->id)
            ->where('id', $notificationId)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        return ApiResponse::message('Notification marked as read');
    }
}

<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Conversation;
use App\Models\User;
use App\Support\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Services\ChatService;

class AdminSupportController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function threads(Request $request): JsonResponse
    {
        [$page, $limit] = PaginationHelper::fromRequest($request, 30, 100);

        $paginator = Conversation::query()
            ->where('type', Conversation::TYPE_SUPPORT)
            ->whereHas('messages')
            ->with(['patient', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->map(fn (Conversation $conversation) => $this->chat->formatSupportThread($conversation))
            ->values()
            ->all();

        return ApiResponse::paginated($paginator, $data);
    }

    public function messages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'uuid'],
        ]);

        $patient = User::query()
            ->where('id', $validated['user_id'])
            ->where('role', 'patient')
            ->first();

        if (!$patient) {
            return ApiResponse::error('NOT_FOUND', 'Patient not found', 404);
        }

        $conversation = $this->chat->getOrCreateSupportConversation($patient->id);

        return $this->chat->paginateMessages($request, $conversation);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'uuid'],
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $content = trim($validated['content']);
        if ($content === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'Invalid request body', 400, [
                'content' => ['The content field is required.'],
            ]);
        }

        $patient = User::query()
            ->where('id', $validated['user_id'])
            ->where('role', 'patient')
            ->first();

        if (!$patient) {
            return ApiResponse::error('NOT_FOUND', 'Patient not found', 404);
        }

        $agent = $request->user();
        $conversation = $this->chat->getOrCreateSupportConversation($patient->id);
        $message = $this->chat->sendMessage($conversation, $agent, $patient, $content);

        return ApiResponse::message(
            'Message sent successfully',
            $this->chat->formatMessage($message, $agent->id),
            201
        );
    }

    public function markRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'uuid'],
        ]);

        $patient = User::query()
            ->where('id', $validated['user_id'])
            ->where('role', 'patient')
            ->first();

        if (!$patient) {
            return ApiResponse::error('NOT_FOUND', 'Patient not found', 404);
        }

        $supportUser = $this->chat->supportUser();
        $conversation = $this->chat->getOrCreateSupportConversation($patient->id);
        $this->chat->markConversationRead($conversation, $supportUser->id);

        // Also mark as read for the authenticated agent if they received messages.
        if ($request->user()->id !== $supportUser->id) {
            $this->chat->markConversationRead($conversation, $request->user()->id);
        }

        return ApiResponse::message('Support chat marked as read', [
            'user_id' => $patient->id,
            'unread_count' => 0,
        ]);
    }
}

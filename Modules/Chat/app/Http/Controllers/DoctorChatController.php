<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Conversation;
use App\Support\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Services\ChatService;

class DoctorChatController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function conversations(Request $request): JsonResponse
    {
        $doctor = $request->user();
        [$page, $limit] = PaginationHelper::fromRequest($request, 30, 100);

        $paginator = Conversation::query()
            ->where('type', Conversation::TYPE_DOCTOR)
            ->where('doctor_id', $doctor->id)
            ->with(['patient.patientProfile', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->map(fn (Conversation $conversation) => $this->chat->formatDoctorConversationForDoctor($conversation, $doctor->id))
            ->values()
            ->all();

        return ApiResponse::paginated($paginator, $data);
    }

    public function messages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'uuid'],
        ]);

        $conversation = $this->chat->findDoctorConversationForDoctor(
            $request->user()->id,
            $validated['conversation_id']
        );

        if (!$conversation) {
            return ApiResponse::error('NOT_FOUND', 'Conversation not found', 404);
        }

        return $this->chat->paginateMessages($request, $conversation);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'uuid'],
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $content = trim($validated['content']);
        if ($content === '') {
            return ApiResponse::error('VALIDATION_ERROR', 'Invalid request body', 400, [
                'content' => ['The content field is required.'],
            ]);
        }

        $doctor = $request->user();
        $conversation = $this->chat->findDoctorConversationForDoctor($doctor->id, $validated['conversation_id']);

        if (!$conversation) {
            return ApiResponse::error('NOT_FOUND', 'Conversation not found', 404);
        }

        $patient = $conversation->patient;
        if (!$patient) {
            return ApiResponse::error('NOT_FOUND', 'Patient not found', 404);
        }

        $message = $this->chat->sendMessage($conversation, $doctor, $patient, $content);

        return ApiResponse::message(
            'Message sent successfully',
            $this->chat->formatMessage($message, $doctor->id),
            201
        );
    }

    public function markRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'uuid'],
        ]);

        $doctor = $request->user();
        $conversation = $this->chat->findDoctorConversationForDoctor($doctor->id, $validated['conversation_id']);

        if (!$conversation) {
            return ApiResponse::error('NOT_FOUND', 'Conversation not found', 404);
        }

        $this->chat->markConversationRead($conversation, $doctor->id);

        return ApiResponse::message('Conversation marked as read', [
            'conversation_id' => $conversation->id,
            'unread_count' => 0,
        ]);
    }
}

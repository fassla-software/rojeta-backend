<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Conversation;
use App\Support\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Services\ChatService;

class PatientChatController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function conversations(Request $request): JsonResponse
    {
        $patient = $request->user();
        [$page, $limit] = PaginationHelper::fromRequest($request, 30, 100);

        $paginator = Conversation::query()
            ->where('type', Conversation::TYPE_DOCTOR)
            ->where('patient_id', $patient->id)
            ->with(['doctor.doctorProfile', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->map(fn (Conversation $conversation) => $this->chat->formatDoctorConversationForPatient($conversation, $patient->id))
            ->values()
            ->all();

        return ApiResponse::paginated($paginator, $data);
    }

    public function storeConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => ['required', 'uuid'],
            'booking_id' => ['nullable', 'integer'],
        ]);

        $result = $this->chat->openDoctorConversation(
            $request->user(),
            $validated['doctor_id'],
            isset($validated['booking_id']) ? (string) $validated['booking_id'] : null
        );

        if (isset($result['error'])) {
            return $result['error'];
        }

        /** @var Conversation $conversation */
        $conversation = $result['conversation'];
        $payload = $this->chat->formatDoctorConversationForPatient($conversation, $request->user()->id);

        return ApiResponse::data($payload, $result['created'] ? 201 : 200);
    }

    public function messages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'uuid'],
        ]);

        $conversation = $this->chat->findDoctorConversationForPatient(
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

        $patient = $request->user();
        $conversation = $this->chat->findDoctorConversationForPatient($patient->id, $validated['conversation_id']);

        if (!$conversation) {
            return ApiResponse::error('NOT_FOUND', 'Conversation not found', 404);
        }

        $doctor = $conversation->doctor;
        if (!$doctor) {
            return ApiResponse::error('NOT_FOUND', 'Doctor not found', 404);
        }

        $message = $this->chat->sendMessage($conversation, $patient, $doctor, $content);

        return ApiResponse::message(
            'Message sent successfully',
            $this->chat->formatMessage($message, $patient->id),
            201
        );
    }

    public function markRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'uuid'],
        ]);

        $patient = $request->user();
        $conversation = $this->chat->findDoctorConversationForPatient($patient->id, $validated['conversation_id']);

        if (!$conversation) {
            return ApiResponse::error('NOT_FOUND', 'Conversation not found', 404);
        }

        $this->chat->markConversationRead($conversation, $patient->id);

        return ApiResponse::message('Conversation marked as read', [
            'conversation_id' => $conversation->id,
            'unread_count' => 0,
        ]);
    }
}

<?php

namespace Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Services\ChatService;

class PatientSupportController extends Controller
{
    public function __construct(private readonly ChatService $chat)
    {
    }

    public function messages(Request $request): JsonResponse
    {
        $conversation = $this->chat->getOrCreateSupportConversation($request->user()->id);

        return $this->chat->paginateMessages($request, $conversation);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $content = $this->chat->validatedContent($request);
        $patient = $request->user();
        $supportUser = $this->chat->supportUser();
        $conversation = $this->chat->getOrCreateSupportConversation($patient->id);

        $message = $this->chat->sendMessage($conversation, $patient, $supportUser, $content);

        return ApiResponse::message(
            'Message sent successfully',
            $this->chat->formatMessage($message, $patient->id),
            201
        );
    }

    public function markRead(Request $request): JsonResponse
    {
        $patient = $request->user();
        $conversation = $this->chat->getOrCreateSupportConversation($patient->id);
        $this->chat->markConversationRead($conversation, $patient->id);

        return ApiResponse::message('Support chat marked as read', [
            'unread_count' => 0,
        ]);
    }
}

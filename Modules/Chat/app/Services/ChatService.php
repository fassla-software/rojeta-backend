<?php

namespace Modules\Chat\Services;

use App\Http\Responses\ApiResponse;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\PaginationHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatService
{
    public function supportUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'support@rojeta.app'],
            [
                'name' => 'Rojeta Support',
                'password' => bcrypt(str()->random(32)),
                'role' => 'support',
                'phone_number' => '01000000000',
            ]
        );
    }

    public function findDoctorConversationForPatient(string $patientId, string $conversationId): ?Conversation
    {
        return Conversation::query()
            ->where('id', $conversationId)
            ->where('type', Conversation::TYPE_DOCTOR)
            ->where('patient_id', $patientId)
            ->first();
    }

    public function findDoctorConversationForDoctor(string $doctorId, string $conversationId): ?Conversation
    {
        return Conversation::query()
            ->where('id', $conversationId)
            ->where('type', Conversation::TYPE_DOCTOR)
            ->where('doctor_id', $doctorId)
            ->first();
    }

    public function getOrCreateSupportConversation(string $patientId): Conversation
    {
        return Conversation::firstOrCreate(
            [
                'patient_id' => $patientId,
                'type' => Conversation::TYPE_SUPPORT,
                'doctor_id' => null,
            ],
            [
                'booking_id' => null,
                'last_message_at' => null,
            ]
        );
    }

    public function patientCanStartChat(User $patient, User $doctor, ?string $bookingId = null): ?JsonResponse
    {
        $chatPrice = (float) ($doctor->doctorProfile?->chat_price ?? 0);

        if ($chatPrice <= 0) {
            return null;
        }

        if ($bookingId) {
            $booking = Booking::query()
                ->where('id', $bookingId)
                ->where('patient_id', $patient->id)
                ->where('provider_id', $doctor->id)
                ->where('payment_status', 'paid')
                ->first();

            if ($booking) {
                return null;
            }
        }

        $hasPaidBooking = Booking::query()
            ->where('patient_id', $patient->id)
            ->where('provider_id', $doctor->id)
            ->where('payment_status', 'paid')
            ->exists();

        if ($hasPaidBooking) {
            return null;
        }

        return ApiResponse::error(
            'CHAT_PAYMENT_REQUIRED',
            'Chat consultation payment is required before messaging this doctor',
            402
        );
    }

    public function openDoctorConversation(User $patient, string $doctorId, ?string $bookingId = null): array
    {
        $doctor = User::query()
            ->where('id', $doctorId)
            ->where('role', 'doctor')
            ->with('doctorProfile')
            ->first();

        if (!$doctor) {
            return [
                'error' => ApiResponse::error('NOT_FOUND', 'Doctor not found', 404),
            ];
        }

        if ($bookingId !== null) {
            $booking = Booking::query()
                ->where('id', $bookingId)
                ->where('patient_id', $patient->id)
                ->first();

            if (!$booking) {
                return [
                    'error' => ApiResponse::error('NOT_FOUND', 'Booking not found', 404),
                ];
            }

            if ($booking->provider_id !== $doctor->id) {
                return [
                    'error' => ApiResponse::error(
                        'VALIDATION_ERROR',
                        'Booking does not belong to the selected doctor',
                        400
                    ),
                ];
            }
        }

        $paymentError = $this->patientCanStartChat($patient, $doctor, $bookingId);
        if ($paymentError) {
            return ['error' => $paymentError];
        }

        $existing = Conversation::query()
            ->where('type', Conversation::TYPE_DOCTOR)
            ->where('patient_id', $patient->id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if ($existing) {
            if ($bookingId && !$existing->booking_id) {
                $existing->update(['booking_id' => $bookingId]);
            }

            return [
                'conversation' => $existing->fresh(['doctor.doctorProfile', 'latestMessage']),
                'created' => false,
            ];
        }

        $conversation = Conversation::create([
            'type' => Conversation::TYPE_DOCTOR,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'booking_id' => $bookingId,
            'last_message_at' => null,
        ]);

        return [
            'conversation' => $conversation->load(['doctor.doctorProfile', 'latestMessage']),
            'created' => true,
        ];
    }

    public function sendMessage(Conversation $conversation, User $sender, User $receiver, string $content): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $receiver, $content) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'content' => $content,
                'status' => Message::STATUS_SENT,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });
    }

    public function markConversationRead(Conversation $conversation, string $userId): int
    {
        $now = now();

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('receiver_id', $userId)
            ->where('status', '!=', Message::STATUS_READ)
            ->update([
                'status' => Message::STATUS_READ,
                'read_at' => $now,
            ]);
    }

    public function paginateMessages(Request $request, Conversation $conversation): JsonResponse
    {
        [$page, $limit] = PaginationHelper::fromRequest($request, 30, 100);

        $paginator = Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        $userId = $request->user()->id;
        $data = collect($paginator->items())->map(fn (Message $message) => $this->formatMessage($message, $userId));

        return ApiResponse::paginated($paginator, $data->values()->all());
    }

    public function formatMessage(Message $message, string $currentUserId): array
    {
        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'content' => $message->content,
            'timestamp' => $message->created_at?->utc()->toIso8601String(),
            'created_at' => $message->created_at?->utc()->toIso8601String(),
            'is_sent_by_me' => $message->sender_id === $currentUserId,
            'status' => $message->status,
        ];
    }

    public function formatDoctorConversationForPatient(Conversation $conversation, string $patientId): array
    {
        $doctor = $conversation->doctor;
        $profile = $doctor?->doctorProfile;
        $latest = $conversation->latestMessage;

        return [
            'id' => $conversation->id,
            'doctor_id' => $conversation->doctor_id,
            'booking_id' => $conversation->booking_id,
            'name' => $doctor?->name,
            'image_url' => $profile?->image_url,
            'specialty' => $profile?->specialty,
            'last_message' => $latest?->content,
            'last_message_at' => $conversation->last_message_at?->utc()->toIso8601String()
                ?? $latest?->created_at?->utc()->toIso8601String(),
            'last_message_time' => $conversation->last_message_at?->utc()->toIso8601String()
                ?? $latest?->created_at?->utc()->toIso8601String(),
            'unread_count' => $conversation->unreadMessagesFor($patientId)->count(),
            'is_online' => false,
            'type' => Conversation::TYPE_DOCTOR,
            'created_at' => $conversation->created_at?->utc()->toIso8601String(),
        ];
    }

    public function formatDoctorConversationForDoctor(Conversation $conversation, string $doctorId): array
    {
        $patient = $conversation->patient;
        $profile = $patient?->patientProfile;
        $latest = $conversation->latestMessage;
        $patientName = $patient?->name;
        if ($profile && ($profile->first_name || $profile->last_name)) {
            $patientName = trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: $patientName;
        }

        return [
            'id' => $conversation->id,
            'patient_id' => $conversation->patient_id,
            'booking_id' => $conversation->booking_id,
            'name' => $patientName,
            'image_url' => $profile?->image ?? null,
            'specialty' => null,
            'last_message' => $latest?->content,
            'last_message_at' => $conversation->last_message_at?->utc()->toIso8601String()
                ?? $latest?->created_at?->utc()->toIso8601String(),
            'last_message_time' => $conversation->last_message_at?->utc()->toIso8601String()
                ?? $latest?->created_at?->utc()->toIso8601String(),
            'unread_count' => $conversation->unreadMessagesFor($doctorId)->count(),
            'is_online' => false,
            'type' => Conversation::TYPE_DOCTOR,
            'created_at' => $conversation->created_at?->utc()->toIso8601String(),
        ];
    }

    public function formatSupportThread(Conversation $conversation): array
    {
        $patient = $conversation->patient;
        $latest = $conversation->latestMessage;
        $supportUser = $this->supportUser();

        return [
            'id' => $conversation->id,
            'user_id' => $conversation->patient_id,
            'name' => $patient?->name,
            'last_message' => $latest?->content,
            'last_message_at' => $conversation->last_message_at?->utc()->toIso8601String()
                ?? $latest?->created_at?->utc()->toIso8601String(),
            'unread_count' => $conversation->unreadMessagesFor($supportUser->id)->count(),
            'type' => Conversation::TYPE_SUPPORT,
            'created_at' => $conversation->created_at?->utc()->toIso8601String(),
        ];
    }

    public function validatedContent(Request $request): string
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $content = trim($validated['content']);

        if ($content === '') {
            throw ValidationException::withMessages([
                'content' => ['The content field is required.'],
            ]);
        }

        return $content;
    }
}

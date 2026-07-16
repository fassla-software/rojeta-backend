<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\DoctorProfile;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Chat\Services\ChatService;

class ChatTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $patient = User::firstOrCreate(
            ['email' => 'patient@test.com'],
            [
                'name' => 'Test Patient',
                'role' => 'patient',
                'phone_number' => '01000000001',
                'password' => Hash::make('password'),
            ]
        );

        $doctor = User::firstOrCreate(
            ['email' => 'doctor@test.com'],
            [
                'name' => 'Dr. Test Doctor',
                'role' => 'doctor',
                'phone_number' => '01000000002',
                'password' => Hash::make('password'),
            ]
        );

        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Test Admin',
                'role' => 'admin',
                'phone_number' => '01000000099',
                'password' => Hash::make('password'),
            ]
        );

        DoctorProfile::updateOrCreate(
            ['user_id' => $doctor->id],
            [
                'specialty' => 'Pediatrics',
                'title' => 'Consultant',
                'rating' => 4.8,
                'reviews_count' => 120,
                'experience_years' => 10,
                'home_visit_price' => 500.00,
                'chat_price' => 0,
                'languages' => 'Arabic,English',
                'image_url' => 'https://i.pravatar.cc/150?u=doctor-test',
                'is_ad' => false,
            ]
        );

        Booking::firstOrCreate(
            [
                'provider_id' => $doctor->id,
                'patient_id' => $patient->id,
                'status' => 'completed',
            ],
            [
                'provider_type' => 'doctor',
                'type' => 'home_visit',
                'patient_name' => $patient->name,
                'patient_phone' => $patient->phone_number,
                'service_name' => 'Home Visit Medical Examination',
                'date' => now()->subDays(5)->format('Y-m-d'),
                'time' => '14:00:00',
                'payment_status' => 'paid',
                'fee' => 800.00,
                'subtotal' => 800.00,
                'discount' => 0,
                'total_price' => 800.00,
            ]
        );

        $conversation = Conversation::firstOrCreate(
            [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'type' => Conversation::TYPE_DOCTOR,
            ],
            [
                'booking_id' => null,
                'last_message_at' => now()->subMinutes(10),
            ]
        );

        if ($conversation->messages()->count() === 0) {
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $doctor->id,
                'receiver_id' => $patient->id,
                'content' => 'مرحباً! كيف يمكنني مساعدتك اليوم؟',
                'status' => Message::STATUS_SENT,
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(10),
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $patient->id,
                'receiver_id' => $doctor->id,
                'content' => 'أهلاً دكتور، أريد استشارة بخصوص نتائج التحاليل',
                'status' => Message::STATUS_SENT,
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
            ]);

            $conversation->update(['last_message_at' => now()->subMinutes(5)]);
        }

        /** @var ChatService $chat */
        $chat = app(ChatService::class);
        $supportUser = $chat->supportUser();
        $support = $chat->getOrCreateSupportConversation($patient->id);

        if ($support->messages()->count() === 0) {
            $chat->sendMessage(
                $support,
                $supportUser,
                $patient,
                'مرحباً! كيف يمكنني مساعدتك اليوم؟'
            );
            $chat->sendMessage(
                $support,
                $patient,
                $supportUser,
                'مرحباً، أريد الاستفسار عن كود الخصم'
            );
        }

        $this->command?->info('Chat test accounts:');
        $this->command?->info("  Patient: {$patient->phone_number} / password  (id={$patient->id})");
        $this->command?->info("  Doctor:  {$doctor->phone_number} / password  (id={$doctor->id})");
        $this->command?->info("  Admin:   {$admin->phone_number} / password  (id={$admin->id})");
        $this->command?->info("  Conversation: {$conversation->id}");
    }
}

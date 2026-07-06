<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
	public function up(): void
	{
		// Kept intentionally empty because users registration fields now exist in the base users migration.
	}

	public function down(): void
	{
		// No-op.
	}
};

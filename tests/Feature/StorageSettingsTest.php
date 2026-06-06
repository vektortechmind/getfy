<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstalled;
use App\Models\Setting;
use App\Models\User;
use Tests\TestCase;

class StorageSettingsTest extends TestCase
{
    public function test_switching_to_local_preserves_saved_remote_storage_credentials(): void
    {
        $this->withoutMiddleware(EnsureInstalled::class);

        $user = User::factory()->create([
            'role' => User::ROLE_INFOPRODUTOR,
            'tenant_id' => 1,
        ]);

        Setting::set('storage_provider', 'r2', 1);
        Setting::set('storage_s3_key', 'saved-access-key', 1);
        Setting::set('storage_s3_bucket', 'saved-bucket', 1);
        Setting::set('storage_s3_region', 'auto', 1);
        Setting::set('storage_s3_endpoint', 'https://account.r2.cloudflarestorage.com', 1);
        Setting::set('storage_s3_url', 'https://cdn.example.com', 1);

        $this->actingAs($user)->put(route('settings.update'), [
            'email_provider' => 'smtp',
            'storage_provider' => 'local',
            'storage_s3_key' => '',
            'storage_s3_bucket' => '',
            'storage_s3_region' => '',
            'storage_s3_endpoint' => '',
            'storage_s3_url' => '',
        ])->assertRedirect();

        $this->assertSame('local', Setting::get('storage_provider', null, 1));
        $this->assertSame('saved-access-key', Setting::get('storage_s3_key', null, 1));
        $this->assertSame('saved-bucket', Setting::get('storage_s3_bucket', null, 1));
        $this->assertSame('auto', Setting::get('storage_s3_region', null, 1));
        $this->assertSame('https://account.r2.cloudflarestorage.com', Setting::get('storage_s3_endpoint', null, 1));
        $this->assertSame('https://cdn.example.com', Setting::get('storage_s3_url', null, 1));
    }

    public function test_switching_back_to_r2_can_update_remote_credentials(): void
    {
        $this->withoutMiddleware(EnsureInstalled::class);

        $user = User::factory()->create([
            'role' => User::ROLE_INFOPRODUTOR,
            'tenant_id' => 1,
        ]);

        Setting::set('storage_provider', 'local', 1);
        Setting::set('storage_s3_key', 'old-key', 1);
        Setting::set('storage_s3_bucket', 'old-bucket', 1);

        $this->actingAs($user)->put(route('settings.update'), [
            'email_provider' => 'smtp',
            'storage_provider' => 'r2',
            'storage_s3_key' => 'new-key',
            'storage_s3_bucket' => 'new-bucket',
            'storage_s3_region' => 'auto',
            'storage_s3_endpoint' => 'https://account.r2.cloudflarestorage.com',
            'storage_s3_url' => 'https://files.example.com',
        ])->assertRedirect();

        $this->assertSame('r2', Setting::get('storage_provider', null, 1));
        $this->assertSame('new-key', Setting::get('storage_s3_key', null, 1));
        $this->assertSame('new-bucket', Setting::get('storage_s3_bucket', null, 1));
    }
}

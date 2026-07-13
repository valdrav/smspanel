<?php

namespace App\Services\Sms;

use App\Models\SmsTemplate;
use App\Models\User;
use App\Support\UserScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SmsTemplateService
{
    public function list(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $query = SmsTemplate::query();

        if (! UserScope::isPlatformAdmin($user)) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, SmsTemplate>
     */
    public function activeForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return SmsTemplate::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): SmsTemplate
    {
        return SmsTemplate::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'body' => $data['body'],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SmsTemplate $template, array $data): SmsTemplate
    {
        $template->update([
            'name' => $data['name'],
            'body' => $data['body'],
            'is_active' => (bool) ($data['is_active'] ?? $template->is_active),
        ]);

        return $template->fresh();
    }

    public function delete(SmsTemplate $template): void
    {
        $template->delete();
    }
}

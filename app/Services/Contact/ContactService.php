<?php

namespace App\Services\Contact;

use App\Exceptions\BusinessException;
use App\Models\Contact;
use App\Models\User;
use App\Sms\Support\PhoneNormalizer;
use App\Support\UserScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactService
{
    public function __construct(private readonly PhoneNormalizer $phoneNormalizer) {}

    public function list(User $user, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Contact::query()->with('user');

        if (! UserScope::isPlatformAdmin($user)) {
            $query->where('user_id', $user->id);
        } elseif (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('name')->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data): Contact
    {
        $phone = $this->normalizePhone($data['phone']);

        return Contact::create([
            'user_id' => $owner->id,
            'name' => $data['name'] ?? null,
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Contact $contact, array $data): Contact
    {
        $contact->update([
            'name' => $data['name'] ?? $contact->name,
            'phone' => isset($data['phone']) ? $this->normalizePhone($data['phone']) : $contact->phone,
            'email' => $data['email'] ?? $contact->email,
            'notes' => $data['notes'] ?? $contact->notes,
            'is_active' => (bool) ($data['is_active'] ?? $contact->is_active),
        ]);

        return $contact->fresh();
    }

    public function delete(Contact $contact): void
    {
        $contact->delete();
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function importFromCsv(User $owner, UploadedFile $file): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new BusinessException('CSV dosyası okunamadı.');
        }

        $line = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $line++;

            if ($line === 1 && $this->isHeaderRow($row)) {
                continue;
            }

            if (count($row) < 1 || trim((string) ($row[0] ?? '')) === '' && trim((string) ($row[1] ?? '')) === '') {
                continue;
            }

            $name = trim((string) ($row[0] ?? ''));
            $phoneRaw = trim((string) ($row[1] ?? $row[0] ?? ''));

            if ($phoneRaw === '') {
                $skipped++;

                continue;
            }

            try {
                $phone = $this->normalizePhone($phoneRaw);
            } catch (BusinessException $e) {
                $errors[] = "Satır {$line}: {$e->getMessage()}";
                $skipped++;

                continue;
            }

            if (Contact::where('user_id', $owner->id)->where('phone', $phone)->exists()) {
                $skipped++;

                continue;
            }

            Contact::create([
                'user_id' => $owner->id,
                'name' => $name !== '' && $name !== $phoneRaw ? $name : null,
                'phone' => $phone,
                'email' => trim((string) ($row[2] ?? '')) ?: null,
                'notes' => trim((string) ($row[3] ?? '')) ?: null,
                'is_active' => true,
            ]);

            $imported++;
        }

        fclose($handle);

        return compact('imported', 'skipped', 'errors');
    }

    public function exportCsv(User $user): StreamedResponse
    {
        $query = Contact::query()->orderBy('name');

        if (! UserScope::isPlatformAdmin($user)) {
            $query->where('user_id', $user->id);
        }

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Ad', 'Telefon', 'E-posta', 'Notlar']);

            $query->chunk(500, function ($contacts) use ($handle): void {
                foreach ($contacts as $contact) {
                    fputcsv($handle, [
                        $contact->name,
                        $contact->phone,
                        $contact->email,
                        $contact->notes,
                    ]);
                }
            });

            fclose($handle);
        }, 'rehber-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Contact>
     */
    public function getActiveForUser(User $user, ?array $contactIds = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Contact::query()
            ->where('user_id', $user->id)
            ->where('is_active', true);

        if ($contactIds !== null && $contactIds !== []) {
            $query->whereIn('id', $contactIds);
        }

        return $query->get();
    }

    /**
     * @param  list<string>  $row
     */
    private function isHeaderRow(array $row): bool
    {
        $first = strtolower(trim((string) ($row[0] ?? '')));

        return in_array($first, ['ad', 'name', 'isim', 'telefon', 'phone'], true);
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = $this->phoneNormalizer->normalize($phone);

        if (! $this->phoneNormalizer->isValidTurkishMobile($normalized)) {
            throw new BusinessException("Geçersiz numara: {$phone}");
        }

        return $normalized;
    }
}

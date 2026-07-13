<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Http\Requests\Contact\UpdateContactRequest;
use App\Models\Contact;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contact\ContactService;
use App\Support\UserScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contactService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Contact::class);
        $user = auth()->user();
        $canManageAll = UserScope::isPlatformAdmin($user);

        return view('admin.contacts.index', [
            'pageTitle' => 'Rehber',
            'contacts' => $this->contactService->list($user, $request->only(['search', 'is_active', 'user_id'])),
            'filters' => $request->only(['search', 'is_active', 'user_id']),
            'canManageAll' => $canManageAll,
            'users' => $canManageAll ? $this->userRepository->all() : collect(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Contact::class);

        return view('admin.contacts.create', ['pageTitle' => 'Yeni Kişi']);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $this->contactService->create(auth()->user(), $request->validated());

        return redirect()->route('admin.contacts.index')->with('success', 'Kişi eklendi.');
    }

    public function edit(Contact $contact): View
    {
        $this->authorize('update', $contact);

        return view('admin.contacts.edit', [
            'pageTitle' => 'Kişi Düzenle',
            'contact' => $contact,
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->contactService->update($contact, $request->validated());

        return redirect()->route('admin.contacts.index')->with('success', 'Kişi güncellendi.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);
        $this->contactService->delete($contact);

        return redirect()->route('admin.contacts.index')->with('success', 'Kişi silindi.');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $result = $this->contactService->importFromCsv(auth()->user(), $request->file('csv_file'));

        $message = "{$result['imported']} kayıt içe aktarıldı, {$result['skipped']} atlandı.";

        if ($result['errors'] !== []) {
            $message .= ' Hatalar: '.implode(' | ', array_slice($result['errors'], 0, 3));
        }

        return redirect()->route('admin.contacts.index')->with('success', $message);
    }

    public function export(): StreamedResponse
    {
        $this->authorize('viewAny', Contact::class);

        return $this->contactService->exportCsv(auth()->user());
    }
}

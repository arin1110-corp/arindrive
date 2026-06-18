<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>ArinDrive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 text-slate-800">

    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">ArinDrive</h1>
                <p class="text-slate-500">Private Multi Google Drive Dashboard</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('drive.sync') }}"
                    class="px-5 py-3 rounded-xl bg-white border border-slate-200 hover:bg-slate-50">
                    Sync Storage
                </a>

                <a href="{{ route('google.connect') }}"
                    class="px-5 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                    + Tambah Akun Google
                </a>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="px-5 py-3 rounded-xl bg-red-600 text-white hover:bg-red-700">
                        Logout
                    </button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-5 rounded-xl bg-green-100 text-green-700 px-5 py-4">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-xl bg-red-100 text-red-700 px-5 py-4">
                {{ session('error') }}
            </div>
        @endif

        @php
            $totalLimit = $accounts->sum('storage_limit');
            $totalUsed = $accounts->sum('storage_used');
            $totalFree = max($totalLimit - $totalUsed, 0);
            $totalPercent = $totalLimit > 0 ? round(($totalUsed / $totalLimit) * 100, 2) : 0;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                <p class="text-sm text-slate-500">Total Grup</p>
                <h2 class="text-2xl font-bold">{{ $groups->count() }}</h2>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                <p class="text-sm text-slate-500">Total Akun</p>
                <h2 class="text-2xl font-bold">{{ $accounts->count() }}</h2>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                <p class="text-sm text-slate-500">Terpakai</p>
                <h2 class="text-2xl font-bold">{{ number_format($totalUsed / 1024 / 1024 / 1024, 2) }} GB</h2>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                <p class="text-sm text-slate-500">Sisa</p>
                <h2 class="text-2xl font-bold">{{ number_format($totalFree / 1024 / 1024 / 1024, 2) }} GB</h2>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-8">
            <div class="flex justify-between mb-2">
                <p class="font-semibold">Total Usage</p>
                <p>{{ $totalPercent }}%</p>
            </div>

            <div class="w-full bg-slate-200 rounded-full h-4">
                <div class="bg-blue-600 h-4 rounded-full" style="width: {{ min($totalPercent, 100) }}%"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-8">
            <h2 class="text-xl font-bold mb-4">Tambah Grup Drive</h2>

            <form action="{{ route('groups.store') }}" method="POST" class="flex flex-col md:flex-row gap-3">
                @csrf

                <input type="text" name="name" placeholder="Contoh: Kantor, Bisnis, Client"
                    class="border border-slate-300 rounded-xl p-3 w-full">

                <button class="bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700">
                    Tambah
                </button>
            </form>

            @error('name')
                <p class="text-red-600 mt-3">{{ $message }}</p>
            @enderror
        </div>

        <h2 class="text-xl font-bold mb-4">Grup Drive</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            @foreach ($groups as $group)
                @php
                    $groupLimit = $group->accounts->sum('storage_limit');
                    $groupUsed = $group->accounts->sum('storage_used');
                    $groupFree = max($groupLimit - $groupUsed, 0);
                    $groupPercent = $groupLimit > 0 ? round(($groupUsed / $groupLimit) * 100, 2) : 0;
                @endphp

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-bold text-lg">{{ $group->name }}</h3>
                            <p class="text-sm text-slate-500">{{ $group->accounts->count() }} akun</p>
                            <p class="text-xs text-slate-400 mt-1">Slug: {{ $group->slug }}</p>
                        </div>

                        @if ($group->is_active)
                            <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full">Aktif</span>
                        @else
                            <span class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded-full">Nonaktif</span>
                        @endif
                    </div>

                    <div class="mt-4 text-sm text-slate-600 space-y-1">
                        <p>Total: {{ number_format($groupLimit / 1024 / 1024 / 1024, 2) }} GB</p>
                        <p>Terpakai: {{ number_format($groupUsed / 1024 / 1024 / 1024, 2) }} GB</p>
                        <p>Sisa: {{ number_format($groupFree / 1024 / 1024 / 1024, 2) }} GB</p>
                    </div>

                    <div class="w-full bg-slate-200 rounded-full h-3 mt-4">
                        <div class="bg-blue-600 h-3 rounded-full" style="width: {{ min($groupPercent, 100) }}%"></div>
                    </div>

                    <p class="text-xs text-slate-500 mt-2">{{ $groupPercent }}%</p>

                    <div class="mt-4 border-t pt-4">
                        <form action="{{ route('groups.update', $group) }}" method="POST" class="space-y-2">
                            @csrf
                            @method('PUT')

                            <input type="text" name="name" value="{{ $group->name }}"
                                class="border border-slate-300 rounded-xl p-2 w-full text-sm">

                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="is_active" value="1" @checked($group->is_active)>
                                Aktif
                            </label>

                            <div class="flex gap-2">
                                <button class="px-3 py-2 bg-yellow-500 text-white rounded-lg text-sm">
                                    Update
                                </button>
                            </div>
                        </form>

                        <form action="{{ route('groups.destroy', $group) }}" method="POST" class="mt-2">
                            @csrf
                            @method('DELETE')

                            <button
                                onclick="return confirm('Hapus grup ini? Pastikan tidak ada akun Drive di dalam grup.')"
                                class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm w-full">
                                Hapus Grup
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach

            @if ($groups->count() == 0)
                <div class="md:col-span-4 bg-white rounded-2xl p-8 text-center border border-slate-200">
                    <p class="text-slate-500">Belum ada grup Drive.</p>
                </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-8">
            <h2 class="text-xl font-bold mb-4">Upload File</h2>

            <form action="{{ route('drive.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <select name="drive_group_slug" class="border border-slate-300 rounded-xl p-3">
                        <option value="">-- Pilih Grup Tujuan --</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->slug }}">
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="file" name="file" class="border border-slate-300 rounded-xl p-3 md:col-span-1">

                    <button class="bg-slate-900 text-white px-6 py-3 rounded-xl hover:bg-slate-800">
                        Upload Otomatis
                    </button>
                </div>

                @error('drive_group_slug')
                    <p class="text-red-600 mt-3">{{ $message }}</p>
                @enderror

                @error('file')
                    <p class="text-red-600 mt-3">{{ $message }}</p>
                @enderror
            </form>
        </div>

        <h2 class="text-xl font-bold mb-4">Akun Drive</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            @forelse($accounts as $account)
                @php
                    $limit = $account->storage_limit ?? 0;
                    $used = $account->storage_used ?? 0;
                    $free = max($limit - $used, 0);
                    $percent = $limit > 0 ? round(($used / $limit) * 100, 2) : 0;
                @endphp

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-bold text-slate-900 break-all">{{ $account->email }}</h2>
                            <p class="text-xs text-slate-400 mt-1">
                                Grup: {{ $account->group?->name ?? '-' }}
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Google ID: {{ $account->google_id ?? '-' }}
                            </p>
                        </div>

                        @if ($account->is_active)
                            <span class="text-xs bg-green-100 text-green-700 px-3 py-1 rounded-full">Aktif</span>
                        @else
                            <span class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded-full">Nonaktif</span>
                        @endif
                    </div>

                    <div class="mt-5 space-y-1 text-sm text-slate-600">
                        <p>Total: {{ number_format($limit / 1024 / 1024 / 1024, 2) }} GB</p>
                        <p>Terpakai: {{ number_format($used / 1024 / 1024 / 1024, 2) }} GB</p>
                        <p>Sisa: {{ number_format($free / 1024 / 1024 / 1024, 2) }} GB</p>
                    </div>

                    <div class="w-full bg-slate-200 rounded-full h-3 mt-4">
                        <div class="bg-blue-600 h-3 rounded-full" style="width: {{ min($percent, 100) }}%"></div>
                    </div>

                    <p class="text-xs text-slate-500 mt-2">{{ $percent }}% digunakan</p>

                    <div class="flex gap-2 mt-4">
                        <form method="POST" action="{{ route('accounts.toggle', $account->id) }}">
                            @csrf
                            <button class="px-3 py-2 bg-yellow-500 text-white rounded-lg text-sm">
                                {{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('accounts.delete', $account->id) }}">
                            @csrf
                            @method('DELETE')
                            <button
                                onclick="return confirm('Hapus akun dari ArinDrive? File di Google Drive tidak dihapus.')"
                                class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm">
                                Hapus
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="md:col-span-3 bg-white rounded-2xl p-8 text-center border border-slate-200">
                    <p class="text-slate-500">Belum ada akun Google Drive.</p>
                </div>
            @endforelse
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-8">
            <h2 class="text-xl font-bold mb-4">Daftar Aplikasi API</h2>

            <form action="{{ route('api-clients.store') }}" method="POST"
                class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                @csrf

                <input type="text" name="name" placeholder="Contoh: SAPLARIN, SADARIN, SIMONTORIN"
                    class="border border-slate-300 rounded-xl p-3">

                <select name="group_slug" class="border border-slate-300 rounded-xl p-3">
                    <option value="">Semua Grup</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->slug }}">{{ $group->name }}</option>
                    @endforeach
                </select>

                <button class="bg-slate-900 text-white rounded-xl px-5 py-3 hover:bg-slate-800">
                    Generate API Key
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm border border-slate-200">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="border p-3 text-left">Aplikasi</th>
                            <th class="border p-3 text-left">Akses Grup</th>
                            <th class="border p-3 text-left">Token</th>
                            <th class="border p-3 text-left">Status</th>
                            <th class="border p-3 text-left">Terakhir Dipakai</th>
                            <th class="border p-3 text-left">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($apiClients as $client)
                            <tr class="hover:bg-slate-50">
                                <td class="border p-3 font-semibold">{{ $client->name }}</td>
                                <td class="border p-3">{{ $client->group_slug ?? 'Semua Grup' }}</td>
                                <td class="border p-3 text-xs break-all">{{ $client->token }}</td>
                                <td class="border p-3">
                                    @if ($client->is_active)
                                        <span
                                            class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">Aktif</span>
                                    @else
                                        <span
                                            class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="border p-3">
                                    {{ $client->last_used_at ? \Carbon\Carbon::parse($client->last_used_at)->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="border p-3">
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('api-clients.toggle', $client) }}">
                                            @csrf
                                            <button class="px-3 py-2 bg-yellow-500 text-white rounded-lg text-sm">
                                                Toggle
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('api-clients.destroy', $client) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button onclick="return confirm('Hapus API Key?')"
                                                class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-5 text-slate-500">
                                    Belum ada aplikasi API.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
    <h2 class="text-xl font-bold mb-4">Daftar File</h2>

    <div class="overflow-x-auto">
        <table class="w-full border border-slate-200 text-sm">
            <thead class="bg-slate-100">
                <tr>
                    <th class="p-3 text-left border">Nama File</th>
                    <th class="p-3 text-left border">Aplikasi</th>
                    <th class="p-3 text-left border">Folder</th>
                    <th class="p-3 text-left border">Reference</th>
                    <th class="p-3 text-left border">Ukuran</th>
                    <th class="p-3 text-left border">Grup</th>
                    <th class="p-3 text-left border">Akun Drive</th>
                    <th class="p-3 text-left border">Link</th>
                </tr>
            </thead>
            <tbody>
                @forelse($files as $file)
                    <tr class="hover:bg-slate-50">
                        <td class="p-3 border font-medium">
                            {{ $file->name }}
                            <div class="text-xs text-slate-400">
                                {{ $file->original_name ?? '-' }}
                            </div>
                        </td>

                        <td class="p-3 border">
                            {{ strtoupper($file->source_app ?? '-') }}
                        </td>

                        <td class="p-3 border">
                            {{ $file->folder ?? '-' }}
                        </td>

                        <td class="p-3 border text-xs">
                            {{ $file->reference_id ?? '-' }}
                        </td>

                        <td class="p-3 border">
                            {{ number_format($file->size / 1024 / 1024, 2) }} MB
                        </td>

                        <td class="p-3 border">
                            {{ $file->driveAccount?->group?->name ?? '-' }}
                        </td>

                        <td class="p-3 border">
                            {{ $file->driveAccount?->email ?? '-' }}
                        </td>

                        <td class="p-3 border">
                            @if($file->file_uid)
                                <a href="{{ route('files.show', $file->file_uid) }}"
                                    target="_blank"
                                    class="text-blue-600 hover:underline">
                                    Buka
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-5 text-center text-slate-500">
                            Belum ada file diupload.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $files->links() }}
    </div>
</div>

    </div>

</body>

</html>

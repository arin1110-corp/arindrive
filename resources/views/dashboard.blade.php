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

        <div class="flex gap-3">
            <a href="{{ route('drive.sync') }}"
               class="px-5 py-3 rounded-xl bg-white border border-slate-200 hover:bg-slate-50">
                Sync Storage
            </a>

            <a href="{{ route('google.connect') }}"
               class="px-5 py-3 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                + Tambah Akun Google
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-xl bg-green-100 text-green-700 px-5 py-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
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
            <div class="bg-blue-600 h-4 rounded-full"
                 style="width: {{ min($totalPercent, 100) }}%">
            </div>
        </div>
    </div>

    <h2 class="text-xl font-bold mb-4">Grup Drive</h2>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        @foreach($groups as $group)
            @php
                $groupLimit = $group->accounts->sum('storage_limit');
                $groupUsed = $group->accounts->sum('storage_used');
                $groupFree = max($groupLimit - $groupUsed, 0);
                $groupPercent = $groupLimit > 0 ? round(($groupUsed / $groupLimit) * 100, 2) : 0;
            @endphp

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                <h3 class="font-bold text-lg">{{ $group->name }}</h3>
                <p class="text-sm text-slate-500">{{ $group->accounts->count() }} akun</p>

                <div class="mt-4 text-sm text-slate-600 space-y-1">
                    <p>Total: {{ number_format($groupLimit / 1024 / 1024 / 1024, 2) }} GB</p>
                    <p>Terpakai: {{ number_format($groupUsed / 1024 / 1024 / 1024, 2) }} GB</p>
                    <p>Sisa: {{ number_format($groupFree / 1024 / 1024 / 1024, 2) }} GB</p>
                </div>

                <div class="w-full bg-slate-200 rounded-full h-3 mt-4">
                    <div class="bg-blue-600 h-3 rounded-full"
                         style="width: {{ min($groupPercent, 100) }}%">
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-2">{{ $groupPercent }}%</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 mb-8">
        <h2 class="text-xl font-bold mb-4">Upload File</h2>

        <form action="{{ route('drive.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <select name="drive_group_slug"
                        class="border border-slate-300 rounded-xl p-3">
                    <option value="">-- Pilih Grup Tujuan --</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->slug }}">
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>

                <input type="file"
                       name="file"
                       class="border border-slate-300 rounded-xl p-3 md:col-span-1">

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
                    </div>

                    @if($account->is_active)
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
                    <div class="bg-blue-600 h-3 rounded-full"
                         style="width: {{ min($percent, 100) }}%">
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-2">{{ $percent }}% digunakan</p>
            </div>
        @empty
            <div class="md:col-span-3 bg-white rounded-2xl p-8 text-center border border-slate-200">
                <p class="text-slate-500">Belum ada akun Google Drive.</p>
            </div>
        @endforelse
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h2 class="text-xl font-bold mb-4">Daftar File</h2>

        <div class="overflow-x-auto">
            <table class="w-full border border-slate-200 text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3 text-left border">Nama File</th>
                        <th class="p-3 text-left border">Ukuran</th>
                        <th class="p-3 text-left border">MIME</th>
                        <th class="p-3 text-left border">Grup</th>
                        <th class="p-3 text-left border">Akun Drive</th>
                        <th class="p-3 text-left border">Google File ID</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($files as $file)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 border font-medium">{{ $file->name }}</td>
                            <td class="p-3 border">
                                {{ number_format($file->size / 1024 / 1024, 2) }} MB
                            </td>
                            <td class="p-3 border">{{ $file->mime_type }}</td>
                            <td class="p-3 border">{{ $file->driveAccount?->group?->name }}</td>
                            <td class="p-3 border">{{ $file->driveAccount?->email }}</td>
                            <td class="p-3 border text-xs break-all">{{ $file->google_file_id }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-5 text-center text-slate-500">
                                Belum ada file diupload.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
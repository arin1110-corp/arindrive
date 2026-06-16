<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Akun Google - ArinDrive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">

<div class="min-h-screen flex items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 w-full max-w-lg p-6">

        <h1 class="text-2xl font-bold text-slate-900">Tambah Akun Google Drive</h1>
        <p class="text-slate-500 mt-1">Pilih grup akun Drive sebelum login Google.</p>

        @if(session('error'))
            <div class="mt-5 rounded-xl bg-red-100 text-red-700 px-5 py-4">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('google.redirect') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-semibold mb-2">Grup Drive</label>
                <select name="drive_group_id"
                        class="w-full border border-slate-300 rounded-xl p-3">
                    <option value="">-- Pilih Grup --</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>

                @error('drive_group_id')
                    <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button class="w-full bg-blue-600 text-white rounded-xl px-5 py-3 hover:bg-blue-700">
                Lanjut Login Google
            </button>
        </form>

        <a href="{{ route('dashboard') }}"
           class="block text-center mt-4 text-sm text-slate-500 hover:text-slate-700">
            Kembali ke Dashboard
        </a>

    </div>
</div>

</body>
</html>
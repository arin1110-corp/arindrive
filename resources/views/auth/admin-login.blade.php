<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login ArinDrive</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center">

<div class="bg-white w-full max-w-md rounded-2xl shadow p-8">
    <h1 class="text-2xl font-bold">ArinDrive</h1>
    <p class="text-slate-500 mb-6">Admin Login</p>

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded-xl mb-4">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.process') }}">
        @csrf

        <label class="text-sm font-semibold">Email</label>
        <input type="email" name="email" class="w-full border rounded-xl p-3 mb-4" required>

        <label class="text-sm font-semibold">Password</label>
        <input type="password" name="password" class="w-full border rounded-xl p-3 mb-5" required>

        <button class="w-full bg-blue-600 text-white rounded-xl py-3">
            Login
        </button>
    </form>
</div>

</body>
</html>
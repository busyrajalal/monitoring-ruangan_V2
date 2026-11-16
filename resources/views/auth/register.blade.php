<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/sentono.png') }}" type="image/x-icon" />
</head>
<body class="bg-light">
    <div class="container min-vh-100 d-flex flex-column justify-content-center align-items-center py-5">

        {{-- Logo Laravel --}}
        <a href="{{ url('/') }}" class="mb-4" aria-label="Home">
            <img src="{{ asset('assets/img/sentono.png') }}" alt="Logo" width="56" height="58">
        </a>

        <div class="card shadow-sm w-100" style="max-width: 420px;">
            <div class="card-body p-4">
                <h4 class="card-title mb-4 text-center">Register</h4>

                <form action="{{ route('register.submit') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Nama:</label>
                        <input type="text" name="name" id="" placeholder="Nama" class="form-control @error('name') is-invalid @enderror">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email:</label>
                        <input type="email" name="email" id="" placeholder="Email" class="form-control @error('email') is-invalid @enderror">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password:</label>
                        <input type="password" name="password" id="" placeholder="Password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                    <p class="mt-3 text-center">Sudah punya akun? <a href="{{ route('login.lihat') }}">Login</a></p>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
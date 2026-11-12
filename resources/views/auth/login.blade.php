<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - FinTrack AI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-slate-100 py-5">
<div class="container" style="max-width: 440px;">
    <!-- Logo -->
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-3 mb-2 shadow-sm">
            <i class="bi bi-wallet2 fs-2"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">FinTrack<span class="text-primary">.AI</span></h3>
        <p class="text-muted small">Intelligent Financial Management for Small Businesses & Individuals</p>
    </div>

    <!-- Login Card -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-3">Sign In to Your Account</h5>

            @if(session('success'))
                <div class="alert alert-success small py-2">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger small py-2">
                    @foreach($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" required value="{{ old('email', 'alex@example.com') }}" placeholder="name@business.com">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label small fw-semibold text-secondary">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" required value="password123" placeholder="••••••••">
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
                        <label class="form-check-label small text-muted" for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                    Sign In <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>
            </form>

            <!-- Quick Demo Credentials Box -->
            <div class="mt-4 pt-3 border-top text-center">
                <span class="badge bg-light text-muted border mb-2">Demo Profiles (1-Click Fill)</span>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="fillDemo('alex@example.com', 'password123')">
                        <i class="bi bi-building me-1"></i> Alex (Small Biz)
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="fillDemo('sarah@example.com', 'password123')">
                        <i class="bi bi-person me-1"></i> Sarah (Individual)
                    </button>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light border-0 py-3 text-center small text-muted rounded-bottom-3">
            Don't have an account? <a href="{{ route('register') }}" class="text-primary fw-semibold text-decoration-none">Create Account</a>
        </div>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>
</body>
</html>

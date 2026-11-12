<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - FinTrack AI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-slate-100 py-5">
<div class="container" style="max-width: 520px;">
    <!-- Logo -->
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-3 mb-2 shadow-sm">
            <i class="bi bi-wallet2 fs-2"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">FinTrack<span class="text-primary">.AI</span></h3>
        <p class="text-muted small">Start tracking your business or personal finances with AI</p>
    </div>

    <!-- Register Card -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-3">Create Your Account</h5>

            @if($errors->any())
                <div class="alert alert-danger small py-2">
                    @foreach($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST">
                @csrf

                <!-- Role Selector -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Account Purpose *</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="role_biz" value="small_business" checked onchange="toggleBizField(true)">
                            <label class="btn btn-outline-primary w-100 text-start p-2" for="role_biz">
                                <div class="fw-bold small"><i class="bi bi-building me-1"></i> Small Business</div>
                                <div class="text-muted" style="font-size: 0.7rem;">Invoicing, contractors, expenses</div>
                            </label>
                        </div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="role" id="role_ind" value="individual" onchange="toggleBizField(false)">
                            <label class="btn btn-outline-secondary w-100 text-start p-2" for="role_ind">
                                <div class="fw-bold small"><i class="bi bi-person me-1"></i> Individual</div>
                                <div class="text-muted" style="font-size: 0.7rem;">Personal budget, savings goals</div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Full Name *</label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}" placeholder="e.g. Alex Morgan">
                    </div>
                    <div class="col-md-6" id="bizNameWrapper">
                        <label class="form-label small fw-semibold text-secondary">Business / Brand Name</label>
                        <input type="text" name="business_name" class="form-control" value="{{ old('business_name') }}" placeholder="e.g. Apex Tech LLC">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-secondary">Email Address *</label>
                        <input type="email" name="email" class="form-control" required value="{{ old('email') }}" placeholder="name@business.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Currency *</label>
                        <select name="currency" class="form-select">
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                            <option value="INR">INR (₹)</option>
                            <option value="CAD">CAD ($)</option>
                            <option value="AUD">AUD ($)</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Password *</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Confirm Password *</label>
                        <input type="password" name="password_confirmation" class="form-control" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                    Create Account <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </form>
        </div>
        <div class="card-footer bg-light border-0 py-3 text-center small text-muted rounded-bottom-3">
            Already have an account? <a href="{{ route('login') }}" class="text-primary fw-semibold text-decoration-none">Sign In</a>
        </div>
    </div>
</div>

<script>
function toggleBizField(isBiz) {
    const wrap = document.getElementById('bizNameWrapper');
    wrap.style.display = isBiz ? 'block' : 'none';
}
</script>
</body>
</html>

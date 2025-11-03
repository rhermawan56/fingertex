<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Fingertex Attendance | Login</title>

  <!-- Google Font: Outfit -->
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Metronic CSS -->
  <link href="{{ asset('metronic/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />

  <style>
    /* Global Font */
    * {
      font-family: 'Outfit', sans-serif !important;
    }

    body {
      background: linear-gradient(135deg, #f3f6f9 0%, #e9eff6 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: #1e1e2d;
    }

    /* Header */
    .login-header {
      background-color: #ffffff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
      padding: 1rem 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .login-header img {
      height: 45px;
    }

    .login-header h2 {
      font-weight: 700;
      color: #0d6efd;
      margin: 0;
      letter-spacing: 0.5px;
    }

    /* Layout utama */
    .login-section {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    .login-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: #fff;
      border-radius: 1.5rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      max-width: 960px;
      width: 100%;
    }

    /* Kiri */
    .login-info {
      background: linear-gradient(135deg, #0d6efd 0%, #004bba 100%);
      color: #fff;
      padding: 3rem 2rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-info h1 {
      font-weight: 700;
      font-size: 28px;
    }

    .login-info p {
      opacity: 0.9;
      margin-top: 1rem;
      font-size: 15px;
      line-height: 1.6;
    }

    .login-info img {
      width: 220px;
      margin: 2rem auto 0;
      opacity: 0.95;
    }

    /* Kanan (Form) */
    .login-card {
      padding: 3rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .login-title {
      font-weight: 700;
      color: #1e1e2d;
    }

    .form-label {
      font-weight: 500;
      color: #4b5675;
    }

    .form-control {
      border-radius: 0.65rem;
    }

    .btn-primary {
      background-color: #0d6efd;
      border: none;
      font-weight: 600;
      border-radius: 0.65rem;
      transition: all 0.25s ease;
    }

    .btn-primary:hover {
      background-color: #004bba;
      transform: translateY(-1px);
    }

    .link-primary {
      color: #0d6efd;
      text-decoration: none;
    }

    .link-primary:hover {
      text-decoration: underline;
    }

    /* Footer */
    footer {
      background-color: #fff;
      padding: 1rem 0;
      text-align: center;
      font-size: 14px;
      color: #6c757d;
      box-shadow: 0 -1px 6px rgba(0, 0, 0, 0.05);
    }

    footer a {
      color: #0d6efd;
      text-decoration: none;
      font-weight: 500;
    }

    footer a:hover {
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .login-container {
        grid-template-columns: 1fr;
      }

      .login-info {
        display: none;
      }
    }
  </style>
</head>

<body id="kt_body">
  <!-- Header -->
  <div class="login-header">
    <img src="https://103.76.15.27/attendance/public/assets/images/kahap.png" alt="Logo Fingertex">
    <h2>Fingertex Attendance</h2>
  </div>

  <!-- Content -->
  <section class="login-section">
    <div class="login-container">
      <!-- Kiri -->
      <div class="login-info">
        <h1>Selamat Datang di Fingertex Attendance</h1>
        <p>
          Sistem manajemen kehadiran & karyawan modern yang terintegrasi dengan mesin fingerprint.
          Pantau absensi, jadwal kerja, dan performa karyawan dengan mudah dan akurat.
        </p>
        <img src="{{ asset('metronic/assets/media/illustrations/sketchy-1/10.png') }}" alt="Attendance Illustration">
      </div>

      <!-- Kanan -->
      <div class="login-card">
        <div class="text-center mb-8">
          <h1 class="login-title mb-2">Sign In</h1>
          <div class="text-muted fw-semibold fs-6">Masuk untuk mengakses dashboard Fingertex</div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
          @csrf

          <div class="fv-row mb-7">
            <label class="form-label">Email</label>
            <input class="form-control form-control-lg form-control-solid" type="email" name="email"
              value="{{ old('email') }}" placeholder="Masukkan email anda" required autofocus />
          </div>

          <div class="fv-row mb-5">
            <label class="form-label">Password</label>
            <input class="form-control form-control-lg form-control-solid" type="password" name="password"
              placeholder="Masukkan password" required />
          </div>

          <div class="d-flex flex-stack flex-wrap gap-3 py-2 mb-6">
            <div>
              <label class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="remember" />
                <span class="form-check-label text-gray-700">Remember me</span>
              </label>
            </div>
            <div class="text-end">
              <a href="#" class="link-primary">Forgot Password?</a>
            </div>
          </div>

          <div class="d-grid">
            <button type="submit" id="kt_sign_in_submit" class="btn btn-primary btn-lg">
              <span class="indicator-label">Sign In</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    &copy; {{ date('Y') }} <strong>Fingertex Attendance System</strong>. All rights reserved.
    <br>
    <a href="https://pradanagasnusantara.com" target="_blank">PT. Kahaptex </a>
  </footer>

  <!-- JS -->
  <script src="{{ asset('metronic/assets/plugins/global/plugins.bundle.js') }}"></script>
  <script src="{{ asset('metronic/assets/js/scripts.bundle.js') }}"></script>
</body>

</html>

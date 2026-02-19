<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Login &mdash; Kab Tapin</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="{{ asset('library/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/bootstrap-social/bootstrap-social.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('stisla/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('stisla/css/components.css') }}">
</head>

<body>
    <div id="app">
        <section class="section">
            <div class="d-flex align-items-stretch flex-wrap">
                <div class="col-lg-4 col-md-6 col-12 order-lg-1 min-vh-100 order-2 bg-white">
                    <div class="m-3 p-4">
                        <img src="{{ asset('img/stisla-fill.svg') }}" alt="logo" width="80" class="shadow-light rounded-circle mb-5 mt-2">
                        <h4 class="text-dark font-weight-normal">Welcome to <span class="font-weight-bold">Kabupaten
                                Tapin</span>
                        </h4>
                        <p class="text-muted">ini adalah Aplikasi Cuti untuk pegawai Kabupaten Tapin </p>
                        <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate="">
                            @csrf
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" class="form-control" @error('email') is-invalid
                                @enderror
                                    value="{{ old('email') }}" tabindex="1" required autofocus>
                                @error('email')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">
                                    Please fill in your email
                                </div>
                            </div>

                            {{-- <div class="form-group">
                                <div class="d-block">
                                    <label for="password" class="control-label">Password</label>
                                </div>
                                <input id="password" type="password" class="form-control" name="password"
                                    tabindex="2" required>
                                <div class="invalid-feedback">
                                    please fill in your password
                                </div>
                            </div> --}}
                            <div class="form-group">
                                <div class="d-block">
                                    <label for="password" class="control-label">Password</label>
                                </div>

                                <div class="input-group">
                                    <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" tabindex="2" required
                                        autocomplete="current-password">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Tampilkan password" tabindex="-1">
                                            {{-- icon eye (default: sembunyikan password) --}}
                                            <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5" />
                                            </svg>

                                            {{-- icon eye-off (disembunyikan; akan dipakai saat mode text) --}}
                                            <svg id="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M3 3l18 18M10.58 10.58A3 3 0 0112 9c1.657 0 3 1.343 3 3 0 .42-.085.82-.238 1.183M9.88 9.88C9.337 10.392 9 11.154 9 12a3 3 0 003 3c.846 0 1.608-.337 2.12-.88M6.11 6.11C4.245 7.31 2.86 9.027 2.458 12c1.274 4.057 5.065 7 9.542 7 1.41 0 2.753-.29 3.969-.81M17.89 17.89C19.755 16.69 21.14 14.973 21.542 12c-.52-1.655-1.451-3.086-2.64-4.19" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                @error('password')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="invalid-feedback">please fill in your password</div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                                    <label class="custom-control-label" for="remember-me">Remember Me</label>
                                </div>
                            </div>

                            <div class="form-group text-right">
                                <a href="auth-forgot-password.html" class="float-left mt-3">
                                    Forgot Password?
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg btn-icon icon-right" tabindex="4">
                                    Login
                                </button>
                            </div>

                            <div class="mt-5 text-center">
                                Don't have an account? <a href="auth-register.html">Create new one</a>
                            </div>
                        </form>

                        <div class="text-small mt-5 text-center">
                            Copyright 2025&copy; Coding by Didik Sugito
                            <div class="mt-2">
                                <a href="#">Privacy Policy</a>
                                <div class="bullet"></div>
                                <a href="#">Terms of Service</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-12 order-lg-2 min-vh-100 background-walk-y position-relative overlay-gradient-bottom order-1"
                    data-background="{{ asset('stisla/img/unsplash/login-bg.jpg') }}">
                    <div class="absolute-bottom-left index-2">
                        <div class="text-light p-5 pb-2">
                            <div class="mb-5 pb-3">
                                <h1 class="display-4 font-weight-bold mb-2">Good Morning</h1>
                                <h5 class="font-weight-normal text-muted-transparent">Kabupaten Tapin, Provinsi
                                    Kalimantan Selatan, Indonesia</h5>
                            </div>
                            Photo by <a class="text-light bb" target="_blank" href="https://unsplash.com/photos/a8lTjWJJgLA">Buapati dan Wakil Bupati</a> Tapin <a
                                class="text-light bb" target="_blank" href="https://web.tapinkab.go.id/">Kabupaten
                                Tapin</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- General JS Scripts -->
    <script src="{{ asset('library/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('library/popper.js/dist/umd/popper.js') }}"></script>
    <script src="{{ asset('library/tooltip.js/dist/umd/tooltip.js') }}"></script>
    <script src="{{ asset('library/bootstrap/dist/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('library/jquery.nicescroll/dist/jquery.nicescroll.min.js') }}"></script>
    <script src="{{ asset('library/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('stisla/js/stisla.js') }}"></script>

    <!-- JS Libraies -->

    <!-- Page Specific JS File -->

    <!-- Template JS File -->
    <script src="{{ asset('stisla/js/scripts.js') }}"></script>
    <script src="{{ asset('stisla/js/custom.js') }}"></script>

    <script>
        (function() {
            const input = document.getElementById('password');
            const btn = document.getElementById('togglePassword');
            const eye = document.getElementById('icon-eye');
            const eyeOff = document.getElementById('icon-eye-off');

            if (!input || !btn) return;

            btn.addEventListener('click', function() {
                const isHidden = input.getAttribute('type') === 'password';
                input.setAttribute('type', isHidden ? 'text' : 'password');
                btn.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Tampilkan password');
                eye.style.display = isHidden ? 'none' : 'inline';
                eyeOff.style.display = isHidden ? 'inline' : 'none';
            });
        })();
    </script>

</body>

</html>

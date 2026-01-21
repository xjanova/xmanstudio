<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>เข้าสู่ระบบ - {{ config('app.name', 'XMan Studio') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .animate-gradient {
            background: linear-gradient(-45deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 75%, #667eea 100%);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .input-group {
            position: relative;
            margin-bottom: 2rem;
        }

        .input-field {
            width: 100%;
            padding: 16px 16px 16px 50px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .input-field:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            transition: color 0.3s ease;
        }

        .input-field:focus ~ .input-icon {
            color: #667eea;
        }

        .gradient-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .gradient-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .gradient-button:active {
            transform: translateY(0);
        }

        .gradient-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .gradient-button:hover::before {
            left: 100%;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            pointer-events: none;
        }

        @keyframes particleFloat {
            0% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translate(100px, -100px) rotate(360deg);
                opacity: 0;
            }
        }

        .logo-container {
            width: 120px;
            height: 120px;
            margin: 0 auto 2rem;
            position: relative;
        }

        .logo-glow {
            position: absolute;
            inset: -10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            filter: blur(20px);
            opacity: 0.5;
            animation: pulse 3s ease-in-out infinite;
        }

        .logo-image {
            position: relative;
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 20px;
            background: white;
            padding: 10px;
        }
    </style>
</head>
<body class="antialiased">
    <!-- Animated Gradient Background -->
    <div class="fixed inset-0 animate-gradient"></div>

    <!-- Particles Container -->
    <div id="particles-container" class="fixed inset-0 overflow-hidden pointer-events-none"></div>

    <!-- Main Content -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Login Card -->
            <div class="glass-card rounded-3xl p-8 fade-in-up">
                <!-- Logo -->
                <div class="logo-container floating">
                    <div class="logo-glow"></div>
                    @php
                        $siteLogo = \App\Models\Setting::getValue('site_logo');
                    @endphp
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}"
                             alt="Logo"
                             class="logo-image">
                    @else
                        <div class="logo-image flex items-center justify-center">
                            <svg class="w-16 h-16 text-purple-600" fill="currentColor"
                                 viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9a1 1 0 112 0v4a1 1 0 11-2 0V9z
                                         m1-5a1 1 0 100 2 1 1 0 000-2z"
                                      clip-rule="evenodd" />
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Title -->
                <h2 class="text-3xl font-bold text-center text-gray-800 mb-2">
                    ยินดีต้อนรับกลับ
                </h2>
                <p class="text-center text-gray-600 mb-8">
                    เข้าสู่ระบบเพื่อดำเนินการต่อ
                </p>

                <!-- Session Status -->
                @if (session('status'))
                    <div class="mb-4 p-4 bg-green-50 border border-green-200
                                rounded-lg text-green-600 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <!-- Email Input -->
                    <div class="input-group">
                        <input type="email"
                               name="email"
                               id="email"
                               class="input-field @error('email') border-red-500 @enderror"
                               placeholder="อีเมล"
                               value="{{ old('email') }}"
                               required
                               autofocus
                               autocomplete="username">
                        <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5
                                     a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div class="input-group">
                        <input type="password"
                               name="password"
                               id="password"
                               class="input-field @error('password') border-red-500 @enderror"
                               placeholder="รหัสผ่าน"
                               required
                               autocomplete="current-password">
                        <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z
                                     m10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="remember"
                                   class="w-4 h-4 text-purple-600 border-gray-300
                                          rounded focus:ring-purple-500">
                            <span class="ml-2 text-sm text-gray-600">จดจำฉันไว้</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-sm text-purple-600 hover:text-purple-700
                                      hover:underline transition">
                                ลืมรหัสผ่าน?
                            </a>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full gradient-button">
                        เข้าสู่ระบบ
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-6 text-center">
                    <a href="/" class="text-sm text-gray-600 hover:text-purple-600
                                       transition">
                        ← กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles-container');
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';

                // Random size between 2-8px
                const size = Math.random() * 6 + 2;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';

                // Random position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';

                // Random animation duration
                const duration = Math.random() * 10 + 5;
                particle.style.animation = `particleFloat ${duration}s linear infinite`;
                particle.style.animationDelay = Math.random() * 5 + 's';

                container.appendChild(particle);
            }
        }

        // Add ripple effect to button
        document.querySelector('.gradient-button').addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.5)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s ease-out';

            this.appendChild(ripple);

            setTimeout(() => ripple.remove(), 600);
        });

        // Add ripple animation style
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Initialize particles on load
        window.addEventListener('load', createParticles);
    </script>
</body>
</html>

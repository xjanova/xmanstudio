<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>สมัครสมาชิก - {{ config('app.name', 'XMan Studio') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Space Warp Drive Effect */
        .warp-container {
            position: fixed;
            inset: 0;
            background: radial-gradient(ellipse at center, #0a0a20 0%, #000008 50%, #000000 100%);
            overflow: hidden;
            perspective: 1000px;
        }

        /* Nebula/Galaxy Clouds */
        .nebula {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 30%, rgba(138, 43, 226, 0.15) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(0, 191, 255, 0.12) 0%, transparent 45%),
                radial-gradient(ellipse at 40% 80%, rgba(255, 20, 147, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 70% 70%, rgba(75, 0, 130, 0.15) 0%, transparent 40%),
                radial-gradient(ellipse at 10% 60%, rgba(0, 255, 255, 0.08) 0%, transparent 35%);
            animation: nebulaShift 30s ease-in-out infinite;
        }

        @keyframes nebulaShift {
            0%, 100% { opacity: 0.8; transform: scale(1) rotate(0deg); }
            50% { opacity: 1; transform: scale(1.1) rotate(5deg); }
        }

        /* Static Stars Background */
        .stars-static {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(1px 1px at 10% 20%, rgba(255,255,255,0.8), transparent),
                radial-gradient(1px 1px at 20% 50%, rgba(255,255,255,0.6), transparent),
                radial-gradient(2px 2px at 30% 10%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1px 1px at 40% 70%, rgba(255,255,255,0.5), transparent),
                radial-gradient(1px 1px at 50% 30%, rgba(255,255,255,0.7), transparent),
                radial-gradient(2px 2px at 60% 80%, rgba(255,255,255,0.8), transparent),
                radial-gradient(1px 1px at 70% 15%, rgba(255,255,255,0.6), transparent),
                radial-gradient(1px 1px at 80% 60%, rgba(255,255,255,0.7), transparent),
                radial-gradient(2px 2px at 90% 40%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1px 1px at 15% 85%, rgba(255,255,255,0.5), transparent),
                radial-gradient(1px 1px at 25% 35%, rgba(255,255,255,0.6), transparent),
                radial-gradient(1px 1px at 35% 95%, rgba(255,255,255,0.7), transparent),
                radial-gradient(2px 2px at 45% 5%, rgba(255,255,255,0.8), transparent),
                radial-gradient(1px 1px at 55% 55%, rgba(255,255,255,0.5), transparent),
                radial-gradient(1px 1px at 65% 25%, rgba(255,255,255,0.6), transparent),
                radial-gradient(1px 1px at 75% 75%, rgba(255,255,255,0.7), transparent),
                radial-gradient(2px 2px at 85% 45%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1px 1px at 95% 90%, rgba(255,255,255,0.6), transparent);
            animation: twinkle 8s ease-in-out infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }

        /* Warp Light Streaks */
        .warp-streaks {
            position: absolute;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
        }

        .warp-streaks-1 {
            background-image:
                radial-gradient(1px 80px at 10% 50%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1px 60px at 25% 30%, rgba(200,220,255,0.8), transparent),
                radial-gradient(1px 100px at 40% 70%, rgba(255,255,255,0.7), transparent),
                radial-gradient(1px 70px at 55% 20%, rgba(220,200,255,0.8), transparent),
                radial-gradient(1px 90px at 70% 60%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1px 50px at 85% 40%, rgba(200,255,255,0.7), transparent),
                radial-gradient(1px 80px at 95% 80%, rgba(255,255,255,0.8), transparent);
            background-size: 100% 100%;
            animation: warpStreak 3s linear infinite;
        }

        .warp-streaks-2 {
            background-image:
                radial-gradient(1px 120px at 5% 35%, rgba(180,200,255,0.6), transparent),
                radial-gradient(1px 90px at 20% 65%, rgba(255,180,220,0.5), transparent),
                radial-gradient(1px 70px at 35% 25%, rgba(200,255,220,0.6), transparent),
                radial-gradient(1px 110px at 50% 85%, rgba(220,180,255,0.5), transparent),
                radial-gradient(1px 80px at 65% 15%, rgba(255,220,180,0.6), transparent),
                radial-gradient(1px 100px at 80% 55%, rgba(180,255,255,0.5), transparent),
                radial-gradient(1px 60px at 92% 75%, rgba(255,200,200,0.6), transparent);
            background-size: 100% 100%;
            animation: warpStreak 4s linear infinite;
            animation-delay: -1.5s;
        }

        .warp-streaks-3 {
            background-image:
                radial-gradient(2px 150px at 8% 45%, rgba(138,43,226,0.4), transparent),
                radial-gradient(2px 100px at 22% 75%, rgba(0,191,255,0.3), transparent),
                radial-gradient(2px 130px at 38% 15%, rgba(255,20,147,0.4), transparent),
                radial-gradient(2px 80px at 52% 55%, rgba(75,0,130,0.3), transparent),
                radial-gradient(2px 120px at 68% 85%, rgba(0,255,255,0.4), transparent),
                radial-gradient(2px 90px at 82% 25%, rgba(255,105,180,0.3), transparent);
            background-size: 100% 100%;
            animation: warpStreak 5s linear infinite;
            animation-delay: -2s;
        }

        @keyframes warpStreak {
            0% {
                transform: translateZ(-200px) scale(0.5);
                opacity: 0;
            }
            10% {
                opacity: 0.8;
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translateZ(800px) scale(2);
                opacity: 0;
            }
        }

        /* Central Glow */
        .center-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(100,150,255,0.1) 0%, transparent 70%);
            animation: centerPulse 6s ease-in-out infinite;
        }

        @keyframes centerPulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; }
            50% { transform: translate(-50%, -50%) scale(1.3); opacity: 0.8; }
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

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-field {
            width: 100%;
            padding: 14px 14px 14px 46px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
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
            left: 14px;
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
            padding: 14px 28px;
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

        .logo-container {
            width: 100%;
            padding: 0 10px;
            margin: 0 auto 1.5rem;
            position: relative;
        }

        .logo-image {
            width: 100%;
            height: auto;
            max-height: 150px;
            object-fit: contain;
        }

        .input-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
    </style>
</head>
<body class="antialiased">
    <!-- Space Warp Drive Background -->
    <div class="warp-container">
        <div class="nebula"></div>
        <div class="stars-static"></div>
        <div class="warp-streaks warp-streaks-1"></div>
        <div class="warp-streaks warp-streaks-2"></div>
        <div class="warp-streaks warp-streaks-3"></div>
        <div class="center-glow"></div>
    </div>

    <!-- Main Content -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Register Card -->
            <div class="glass-card rounded-3xl p-8 fade-in-up">
                <!-- Logo -->
                <div class="logo-container">
                    @php
                        $siteLogo = \App\Models\Setting::getValue('site_logo');
                    @endphp
                    @if ($siteLogo)
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
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">
                    สมัครสมาชิก
                </h2>
                <p class="text-center text-gray-600 mb-6">
                    สร้างบัญชีใหม่เพื่อเข้าใช้งาน
                </p>

                <!-- Register Form -->
                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <!-- Name Input -->
                    <div class="input-group">
                        <label for="name" class="input-label">ชื่อ</label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="input-field @error('name') border-red-500 @enderror"
                               placeholder="ชื่อของคุณ"
                               value="{{ old('name') }}"
                               required
                               autofocus
                               autocomplete="name">
                        <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" style="top: calc(50% + 12px);">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Input -->
                    <div class="input-group">
                        <label for="email" class="input-label">อีเมล</label>
                        <input type="email"
                               name="email"
                               id="email"
                               class="input-field @error('email') border-red-500 @enderror"
                               placeholder="example@email.com"
                               value="{{ old('email') }}"
                               required
                               autocomplete="username">
                        <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" style="top: calc(50% + 12px);">
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
                        <label for="password" class="input-label">รหัสผ่าน</label>
                        <input type="password"
                               name="password"
                               id="password"
                               class="input-field @error('password') border-red-500 @enderror"
                               placeholder="รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)"
                               required
                               autocomplete="new-password">
                        <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" style="top: calc(50% + 12px);">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z
                                     m10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password Input -->
                    <div class="input-group">
                        <label for="password_confirmation" class="input-label">ยืนยันรหัสผ่าน</label>
                        <input type="password"
                               name="password_confirmation"
                               id="password_confirmation"
                               class="input-field"
                               placeholder="ใส่รหัสผ่านอีกครั้ง"
                               required
                               autocomplete="new-password">
                        <svg class="input-icon w-5 h-5" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24" style="top: calc(50% + 12px);">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full gradient-button">
                        สมัครสมาชิก
                    </button>
                </form>

                <!-- Login Link -->
                <div class="mt-6 text-center">
                    <span class="text-sm text-gray-600">มีบัญชีอยู่แล้ว?</span>
                    <a href="{{ route('login') }}" class="text-sm text-purple-600 hover:text-purple-700 hover:underline transition font-medium ml-1">
                        เข้าสู่ระบบ
                    </a>
                </div>

                <!-- Footer -->
                <div class="mt-4 text-center">
                    <a href="/" class="text-sm text-gray-600 hover:text-purple-600
                                       transition">
                        ← กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>

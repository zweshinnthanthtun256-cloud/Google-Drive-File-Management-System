<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Sign In" />

    <div
        class="relative min-h-screen overflow-hidden bg-slate-950 bg-cover bg-center bg-no-repeat"
        style="">
    >
        <!-- Background Overlay -->
        <div class="absolute inset-0 bg-slate-950/65"></div>

        <!-- Background Glow -->
        <div
            class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-blue-500/20 blur-3xl"
        ></div>

        <div
            class="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-indigo-500/20 blur-3xl"
        ></div>

        <!-- Main Content -->
        <div
            class="relative z-10 min-h-screen flex items-center justify-center px-4 py-8 sm:px-6 lg:justify-end lg:px-16 xl:px-24"
        >

            <!-- Left Branding -->
            <div
                class="hidden lg:flex absolute left-10 xl:left-20 top-1/2 -translate-y-1/2 max-w-xl flex-col text-white"
            >
                <!-- Badge -->
                <div class="mb-7">
                    <span
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium backdrop-blur-xl"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-emerald-400"
                        ></span>

                        Cloud File Management
                    </span>
                </div>

                <!-- Heading -->
                <h1
                    class="text-5xl font-bold leading-[1.08] tracking-tight xl:text-6xl"
                >
                    Manage your files
                    <br />

                    <span class="text-blue-300">
                        anywhere, anytime.
                    </span>
                </h1>

                <!-- Divider -->
                <div
                    class="my-7 h-1 w-16 rounded-full bg-blue-400"
                ></div>

                <p
                    class="max-w-lg text-lg leading-relaxed text-white/75 xl:text-xl"
                >
                    Access, organize and manage your Google Drive files
                    through one simple and secure workspace.
                </p>

                <!-- Features -->
                <div class="mt-9 space-y-5">

                    <!-- Feature -->
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/10 backdrop-blur-xl"
                        >
                            📁
                        </div>

                        <div>
                            <p class="font-semibold">
                                Easy File Management
                            </p>

                            <p class="text-sm text-white/55">
                                Find and organize your files easily
                            </p>
                        </div>
                    </div>

                    <!-- Feature -->
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/10 backdrop-blur-xl"
                        >
                            🔒
                        </div>

                        <div>
                            <p class="font-semibold">
                                Secure & Private
                            </p>

                            <p class="text-sm text-white/55">
                                Protected with Google authentication
                            </p>
                        </div>
                    </div>

                    <!-- Feature -->
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/10 backdrop-blur-xl"
                        >
                            ☁️
                        </div>

                        <div>
                            <p class="font-semibold">
                                Google Drive Powered
                            </p>

                            <p class="text-sm text-white/55">
                                Your files stay in your Google Drive
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Login Card -->
            <div class="w-full max-w-md lg:max-w-[440px]">

                <div
                    class="rounded-3xl border border-white/20 bg-white/95 p-7 shadow-2xl backdrop-blur-2xl sm:p-9"
                >

                    <!-- Logo -->
                    <div class="flex justify-center">
                        <div
                            class="flex h-20 w-20 items-center justify-center rounded-2xl border border-gray-100 bg-white shadow-lg"
                        >
                            <svg
                                viewBox="0 0 100 100"
                                class="h-12 w-12"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M32 12L50 12L82 67H63L32 12Z"
                                    fill="#0F9D58"
                                />

                                <path
                                    d="M32 12L14 43L29 69L47 38L32 12Z"
                                    fill="#F4B400"
                                />

                                <path
                                    d="M29 69L63 69L82 67L67 93H44L29 69Z"
                                    fill="#4285F4"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Heading -->
                    <div class="mt-4 text-center">
                        <h2
                            class="text-3xl font-bold tracking-tight text-gray-900"
                        >
                            Welcome back
                        </h2>

                        <p
                            class="mt-2 text-sm leading-6 text-gray-500"
                        >
                            Sign in with your Google account to
                            continue to your workspace.
                        </p>
                    </div>

                    <!-- Google Login -->
                    <div class="mt-8">

                        <a
                            href="/auth/google"
                            class="group flex w-full items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white px-5 py-3.5 font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-gray-300 hover:bg-gray-50 hover:shadow-md"
                        >
                            <!-- Google Icon -->
                            <span
                                class="flex h-7 w-7 items-center justify-center"
                            >
                                <svg
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        fill="#4285F4"
                                        d="M21.35 12.27c0-.72-.06-1.41-.18-2.07H12v3.92h5.24a4.48 4.48 0 01-1.94 2.94v2.45h3.14c1.84-1.7 2.91-4.2 2.91-7.24z"
                                    />

                                    <path
                                        fill="#34A853"
                                        d="M12 21.5c2.63 0 4.84-.87 6.45-2.36l-3.14-2.45c-.87.58-1.98.92-3.31.92-2.54 0-4.69-1.72-5.46-4.03H3.29v2.53A9.74 9.74 0 0012 21.5z"
                                    />

                                    <path
                                        fill="#FBBC05"
                                        d="M6.54 13.58A5.85 5.85 0 016.23 12c0-.55.1-1.09.31-1.58V7.89H3.29A9.75 9.75 0 002.25 12c0 1.57.38 3.05 1.04 4.11l3.25-2.53z"
                                    />

                                    <path
                                        fill="#EA4335"
                                        d="M12 6.39c1.43 0 2.71.49 3.72 1.45l2.79-2.79C16.83 3.5 14.63 2.5 12 2.5a9.74 9.74 0 00-8.71 5.39l3.25 2.53C7.31 8.11 9.46 6.39 12 6.39z"
                                    />
                                </svg>
                            </span>

                            <span>
                                Continue with Google
                            </span>

                            <svg
                                class="ml-auto h-5 w-5 text-gray-400 transition-transform group-hover:translate-x-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </a>

                    </div>

                    <!-- Security -->
                    <div
                        class="mt-6 rounded-xl border border-blue-100 bg-blue-50/70 p-4"
                    >
                        <div class="flex gap-3">

                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100"
                            >
                                🔒
                            </div>

                            <div>
                                <p
                                    class="text-sm font-semibold text-gray-700"
                                >
                                    Secure authentication
                                </p>

                                <p
                                    class="mt-1 text-xs leading-5 text-gray-500"
                                >
                                    We use Google OAuth to securely
                                    authenticate your account.
                                </p>
                            </div>

                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-7 text-center">

                        <div
                            class="mx-auto mb-4 h-px w-full bg-gray-100"
                        ></div>

                        <p class="text-xs text-gray-400">
                            Google Drive Manager
                        </p>

                        <p class="mt-1 text-[11px] text-gray-300">
                            Secure Cloud File Management
                        </p>

                    </div>

                </div>

                <p
                    class="mt-5 text-center text-xs text-white/50"
                >
                    Secure cloud file management powered by Google Drive.
                </p>

            </div>

        </div>
    </div>
</template>

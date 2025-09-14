<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Localization</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js for interactions -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom styles -->
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <div class="min-h-full" x-data="{ sidebarOpen: false }">
        <!-- Off-canvas menu for mobile -->
        <div x-show="sidebarOpen" class="relative z-50 lg:hidden" x-cloak>
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/80"></div>

            <div class="fixed inset-0 flex">
                <div x-show="sidebarOpen"
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-16 flex w-full max-w-xs flex-1">
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button @click="sidebarOpen = false" class="-m-2.5 p-2.5">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Mobile sidebar -->
                    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-2">
                        <div class="flex h-16 shrink-0 items-center">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg flex items-center justify-center">
                                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1h-1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7H3a1 1 0 01-1-1V5a1 1 0 011-1h4zM9 3v1h6V3H9zM6 7v12h12V7H6zm2 3h8v2H8v-2zm0 4h8v2H8v-2z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h1 class="text-lg font-semibold text-gray-900">Localization</h1>
                                    <p class="text-xs text-gray-500">Translation Manager</p>
                                </div>
                            </div>
                        </div>
                        <nav class="flex flex-1 flex-col">
                            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                <li>
                                    <ul role="list" class="-mx-2 space-y-1">
                                        <li>
                                            <a href="{{route('localization.jawab.translation.index')}}"
                                               class="{{ request()->routeIs('localization.jawab.translation.index') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-gray-700 hover:text-primary-700 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors duration-150">
                                                <svg class="{{ request()->routeIs('localization.jawab.translation.index') ? 'text-primary-500' : 'text-gray-400 group-hover:text-primary-500' }} h-6 w-6 shrink-0 transition-colors duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                Translations
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('localization.jawab.translation.generate') }}"
                                               class="{{ request()->routeIs('localization.jawab.translation.generate') ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500' : 'text-gray-700 hover:text-primary-700 hover:bg-gray-50' }} group flex gap-x-3 rounded-md p-3 text-sm leading-6 font-medium transition-colors duration-150">
                                                <svg class="{{ request()->routeIs('localization.jawab.translation.generate') ? 'text-primary-500' : 'text-gray-400 group-hover:text-primary-500' }} h-6 w-6 shrink-0 transition-colors duration-150" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Generate
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6">
                <div class="flex h-16 shrink-0 items-center border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-xl font-bold text-gray-900">Localization</h1>
                            <p class="text-sm text-gray-500">Translation Manager</p>
                        </div>
                    </div>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <a href="{{route('localization.jawab.translation.index')}}"
                                       class="{{ request()->routeIs('localization.jawab.translation.index') ? 'bg-primary-50 text-primary-700 border-r-4 border-primary-500' : 'text-gray-700 hover:text-primary-700 hover:bg-gray-50' }} group flex gap-x-3 rounded-lg p-3 text-sm leading-6 font-medium transition-all duration-200">
                                        <svg class="{{ request()->routeIs('localization.jawab.translation.index') ? 'text-primary-500' : 'text-gray-400 group-hover:text-primary-500' }} h-6 w-6 shrink-0 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Translations
                                        <span class="{{ request()->routeIs('localization.jawab.translation.index') ? 'bg-primary-100 text-primary-600' : 'bg-gray-100 text-gray-600' }} ml-auto w-9 min-w-max whitespace-nowrap rounded-full px-2.5 py-0.5 text-center text-xs font-medium leading-5 transition-colors duration-200">{{ (is_object($data) && method_exists($data, 'total')) ? $data->total() : (is_array($data) ? count($data) : '0') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('localization.jawab.translation.generate') }}"
                                       class="{{ request()->routeIs('localization.jawab.translation.generate') ? 'bg-primary-50 text-primary-700 border-r-4 border-primary-500' : 'text-gray-700 hover:text-primary-700 hover:bg-gray-50' }} group flex gap-x-3 rounded-lg p-3 text-sm leading-6 font-medium transition-all duration-200">
                                        <svg class="{{ request()->routeIs('localization.jawab.translation.generate') ? 'text-primary-500' : 'text-gray-400 group-hover:text-primary-500' }} h-6 w-6 shrink-0 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Generate
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="mt-auto">
                            <div class="bg-gradient-to-r from-primary-50 to-blue-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="h-6 w-6 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">Need Help?</p>
                                        <p class="text-xs text-gray-500">Check our documentation</p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-72">
            <!-- Top bar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-700 lg:hidden">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200 lg:hidden"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="relative flex flex-1 items-center">
                        <h2 class="text-lg font-semibold text-gray-900">
                            @yield('page-title', 'Dashboard')
                        </h2>
                    </div>
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Notifications -->
                        <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">View notifications</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </button>

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200"></div>

                        <!-- Profile -->
                        <div class="flex items-center gap-x-4 lg:gap-x-6">
                            <div class="h-8 w-8 rounded-full bg-gradient-to-r from-primary-500 to-primary-600 flex items-center justify-center">
                                <span class="text-sm font-medium text-white">{{ substr(auth()->user()->name ?? 'Admin', 0, 1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main content area -->
            <main class="py-6 lg:py-8">
                <div class="px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed bottom-4 right-4 z-50">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 shadow-lg max-w-md">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-green-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                    <button @click="show = false" class="ml-auto pl-3">
                        <svg class="h-4 w-4 text-green-400 hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                if (document.querySelector('[x-data]').__x) {
                    document.querySelector('[x-data]').__x.$data.show = false;
                }
            }, 5000);
        </script>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed bottom-4 right-4 z-50">
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg max-w-md">
                <div class="flex items-start">
                    <svg class="h-5 w-5 text-red-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                    <button @click="show = false" class="ml-auto pl-3">
                        <svg class="h-4 w-4 text-red-400 hover:text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                if (document.querySelector('[x-data]').__x) {
                    document.querySelector('[x-data]').__x.$data.show = false;
                }
            }, 5000);
        </script>
    @endif
</body>
</html>
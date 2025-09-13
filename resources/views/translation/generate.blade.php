@extends('localization::layouts.app')

@section('page-title', 'Generate Translations')

@section('content')
<div x-data="{
    step: 1,
    selectedGroup: 'general',
    selectedLocales: ['en'],
    translations: {},
    isGenerating: false,
    progress: 0
}" class="space-y-6">

    <!-- Breadcrumb -->
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('localization.jawab.translation.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    Translations
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Generate Translations</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl">
                Generate Translation Files
            </h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
                Export your database translations to PHP and JSON language files for optimal performance.
            </p>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success') || request()->has('generated'))
    <div class="rounded-md bg-green-50 border border-green-200 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">
                    Translation files generated successfully!
                </h3>
                <div class="mt-2 text-sm text-green-700">
                    <p>Language files have been exported to: <code class="bg-green-100 px-2 py-1 rounded font-mono text-xs">{{ App::langPath() }}</code></p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Main Content -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-4 py-5 sm:p-6">
            <!-- Progress Steps -->
            <div class="mb-8">
                <nav aria-label="Progress">
                    <ol class="flex items-center">
                        <li class="relative">
                            <div class="flex items-center">
                                <div :class="step >= 1 ? 'bg-primary-600' : 'bg-gray-200'" class="flex h-8 w-8 items-center justify-center rounded-full">
                                    <svg x-show="step > 1" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-show="step === 1" class="text-sm font-medium text-white">1</span>
                                    <span x-show="step < 1" class="text-sm font-medium text-gray-500">1</span>
                                </div>
                                <p :class="step >= 1 ? 'text-primary-600' : 'text-gray-500'" class="ml-3 text-sm font-medium">Select Options</p>
                            </div>
                            <div class="absolute top-4 left-4 -ml-px mt-0.5 h-full w-0.5 bg-gray-300" aria-hidden="true"></div>
                        </li>

                        <li class="relative">
                            <div class="flex items-center">
                                <div :class="step >= 2 ? 'bg-primary-600' : 'bg-gray-200'" class="flex h-8 w-8 items-center justify-center rounded-full">
                                    <svg x-show="step > 2" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span x-show="step === 2" class="text-sm font-medium text-white">2</span>
                                    <span x-show="step < 2" class="text-sm font-medium text-gray-500">2</span>
                                </div>
                                <p :class="step >= 2 ? 'text-primary-600' : 'text-gray-500'" class="ml-3 text-sm font-medium">Generate Files</p>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>

            <!-- Step 1: Configuration -->
            <div x-show="step === 1">
                <form action="{{ route('localization.jawab.translation.generate') }}" method="POST">
                    @csrf

                    <div class="space-y-6">
                        <!-- Export Format -->
                        <div>
                            <label class="text-base font-medium text-gray-900">Export Format</label>
                            <p class="text-sm leading-5 text-gray-500">Choose which file formats to generate.</p>
                            <fieldset class="mt-4">
                                <div class="space-y-4 sm:flex sm:items-center sm:space-y-0 sm:space-x-10">
                                    <div class="flex items-center">
                                        <input id="format-php" name="format[]" type="checkbox" value="php" checked
                                               class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                        <label for="format-php" class="ml-3 block text-sm font-medium text-gray-700">
                                            PHP Files
                                            <span class="text-xs text-gray-500">(lang/en/auth.php)</span>
                                        </label>
                                    </div>

                                    <div class="flex items-center">
                                        <input id="format-json" name="format[]" type="checkbox" value="json" checked
                                               class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                        <label for="format-json" class="ml-3 block text-sm font-medium text-gray-700">
                                            JSON Files
                                            <span class="text-xs text-gray-500">(lang/en.json)</span>
                                        </label>
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <!-- Language Selection -->
                        <div>
                            <label class="text-base font-medium text-gray-900">Languages to Export</label>
                            <p class="text-sm leading-5 text-gray-500">Select which languages to generate files for.</p>
                            <fieldset class="mt-4">
                                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach(config('localization.locale_names', []) as $code => $name)
                                    <div class="flex items-center">
                                        <input id="locale-{{ $code }}" name="locales[]" type="checkbox" value="{{ $code }}"
                                               {{ $code === 'en' ? 'checked' : '' }}
                                               class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                        <label for="locale-{{ $code }}" class="ml-3 block text-sm font-medium text-gray-700">
                                            <span class="inline-flex items-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-2">
                                                    {{ strtoupper($code) }}
                                                </span>
                                                {{ $name }}
                                            </span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>

                        <!-- Translation Groups -->
                        <div>
                            <label class="text-base font-medium text-gray-900">Translation Groups</label>
                            <p class="text-sm leading-5 text-gray-500">Select which translation groups to export.</p>
                            <fieldset class="mt-4">
                                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                    @foreach(config('localization.translation_groups', []) as $group)
                                    <div class="flex items-center">
                                        <input id="group-{{ $group }}" name="groups[]" type="checkbox" value="{{ $group }}" checked
                                               class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                        <label for="group-{{ $group }}" class="ml-3 block text-sm font-medium text-gray-700">
                                            {{ ucfirst($group) }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </fieldset>
                        </div>

                        <!-- Advanced Options -->
                        <div>
                            <h3 class="text-base font-medium text-gray-900">Advanced Options</h3>
                            <div class="mt-4 space-y-4">
                                <div class="flex items-center">
                                    <input id="overwrite-existing" name="overwrite" type="checkbox" value="1"
                                           class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                    <label for="overwrite-existing" class="ml-3 block text-sm font-medium text-gray-700">
                                        Overwrite existing files
                                        <span class="text-xs text-gray-500 block">Replace existing language files with new ones</span>
                                    </label>
                                </div>

                                <div class="flex items-center">
                                    <input id="clear-cache" name="clear_cache" type="checkbox" value="1" checked
                                           class="focus:ring-primary-500 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                    <label for="clear-cache" class="ml-3 block text-sm font-medium text-gray-700">
                                        Clear translation cache
                                        <span class="text-xs text-gray-500 block">Clear cached translations after generation</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex justify-between">
                        <a href="{{ route('localization.jawab.translation.index') }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back
                        </a>

                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            Generate Files
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-blue-900">Import Translations</h4>
                            <p class="text-xs text-blue-700">Import from existing language files</p>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-green-900">Translation Stats</h4>
                            <p class="text-xs text-green-700">View translation completion status</p>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-6 w-6 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-yellow-900">Clear Cache</h4>
                            <p class="text-xs text-yellow-700">Clear translation cache files</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    About Translation Generation
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p class="mb-2">This tool exports your database translations to static files for better performance:</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>PHP Files:</strong> Traditional Laravel translation files (lang/en/auth.php)</li>
                        <li><strong>JSON Files:</strong> Simple key-value translations (lang/en.json)</li>
                        <li><strong>Performance:</strong> Static files load faster than database queries</li>
                        <li><strong>Caching:</strong> Laravel automatically caches translation files</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
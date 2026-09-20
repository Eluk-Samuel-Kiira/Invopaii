<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <base href="{{ asset('') }}" />
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', config('app.name') . ' - Payment Gateway & Invoicing System')" />
    
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="{{ asset('pay.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" />
    <script>if (window.top != window.self) { window.top.location.replace(window.self.location.href); }</script>
</head>
<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">

    <script>
        var defaultThemeMode = "light"; var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                themeMode = localStorage.getItem("data-bs-theme") ?? defaultThemeMode;
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>

    <style>
        body { background-image: url('{{ asset('assets/media/auth/stardena.png') }}'); }
        [data-bs-theme="dark"] body { background-image: url('{{ asset('assets/media/auth/stardena.png') }}'); }
    </style>

    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-column-fluid flex-lg-row">

            {{-- Left panel --}}
            <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
                <div class="d-flex flex-center flex-lg-start flex-column">
                    <a
                        href="{{ url('/') }}"
                        class="mb-7"
                        style="display:flex; flex-direction:row; align-items:center; gap:6px; text-decoration:none;"
                    >
                        <img alt="Logo" src="{{ asset('pay.png') }}" style="height: 100px; width: auto;" />
                        <span
                            style="
                                font-family:'Archivo','Inter',system-ui,sans-serif;
                                font-weight:700;
                                font-size:96px;
                                letter-spacing:-.01em;
                                line-height:1;
                                color:#6E3FE7; /* fallback if background-clip: text isn't supported */
                                background:linear-gradient(120deg, #5A2FD8 0%, #6E3FE7 45%, #9F7BFF 100%);
                                -webkit-background-clip:text;
                                background-clip:text;
                                -webkit-text-fill-color:transparent;
                            "
                        >Pay</span>
                    </a>
                    <h2 class="text-white fw-normal m-0">
                        @yield('tagline', 'Stardena Payment Gateway & Invoicing System')
                    </h2>
                </div>
            </div>

            {{-- Right panel --}}
            <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
                <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20 p-lg-20">

                    <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">
                        @yield('content')
                    </div>

                    {{-- Footer --}}
                    {{-- 
                    <div class="d-flex flex-stack px-lg-10">
                        <div class="me-0">
                            <button class="btn btn-flex btn-link btn-color-gray-700 btn-active-color-primary rotate fs-base"
                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-start">
                                <img class="w-20px h-20px rounded me-3"
                                    src="{{ asset('assets/media/flags/united-states.svg') }}" alt="" />
                                <span class="me-1">English</span>
                                <i class="ki-duotone ki-down fs-5 text-muted rotate-180 m-0"></i>
                            </button>
                        </div>
                        <div class="d-flex fw-semibold text-primary fs-base gap-5">
                            <a href="#">Terms</a>
                            <a href="#">Plans</a>
                            <a href="#">Contact Us</a>
                        </div>
                    </div>
                    --}}

                </div>
            </div>

        </div>
    </div>

    <script>var hostUrl = "{{ asset('assets/') }}";</script>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    @yield('scripts')

</body>
</html>
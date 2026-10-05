<!DOCTYPE html>
<html lang="id">
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Monitoring Ruangan')</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="{{ asset('assets/img/kaiadmin/favicon.ico') }}" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="{{ asset('assets/js/plugin/webfont/webfont.min.js') }}"></script>
    <script>
      WebFont.load({
        google: { families: ["Public Sans:300,400,500,600,700"] },
        custom: {
          families: ["Font Awesome 5 Solid","Font Awesome 5 Regular","Font Awesome 5 Brands","simple-line-icons"],
          urls: ["{{ asset('assets/css/fonts.min.css') }}"],
        },
        active: function () { sessionStorage.fonts = true; },
      });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/plugins.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/kaiadmin.min.css') }}" />
    @stack('styles')
  </head>
  <body>
    <div class="wrapper">
      @include('partials.sidebar')

      <div class="main-panel">
        @include('partials.navbar')

        <div class="container">
          <div class="page-inner">
            @hasSection('custom-header')
              @yield('custom-header')
            @else
              <div class="page-header">
                <h4 class="page-title">@yield('page-title')</h4>
                <ul class="breadcrumbs">
                  <li class="nav-home"><a href="{{ route('dashboard') }}"><i class="icon-home"></i></a></li>
                  <li class="separator"><i class="icon-arrow-right"></i></li>
                  <li class="nav-item"><a href="#">@yield('breadcrumb')</a></li>
                </ul>
              </div>
            @endif

            @yield('content')
          </div>
        </div>

        @include('partials.footer')
      </div>
    </div>

    <!-- Core JS -->
    <script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js') }}"></script>
    <!-- Chart.js 2.7.2 (bawaan template) -->
    <script src="{{ asset('assets/js/plugin/chart.js/chart.min.js') }}"></script>
    <script src="{{ asset('assets/js/kaiadmin.min.js') }}"></script>
    <!-- Sumber data sensor (dummy) -->
    <script src="{{ asset('assets/js/monitoring-data.js') }}"></script>
    @stack('scripts')
  </body>
</html>

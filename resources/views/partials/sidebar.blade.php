      <!-- Sidebar -->
      <div class="sidebar" data-background-color="dark">
        <div class="sidebar-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="{{ route('dashboard') }}" class="logo">
              <span class="fw-bold fs-4 text-white"><i class="fas fa-building"></i> Ruangan</span>
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <div class="sidebar-wrapper scrollbar scrollbar-inner">
          <div class="sidebar-content">
            <ul class="nav nav-secondary">
              <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                  <i class="fas fa-home"></i>
                  <p>Dashboard</p>
                </a>
              </li>
              <li class="nav-section">
                <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
                <h4 class="text-section">Sensor</h4>
              </li>
              <li class="nav-item {{ request()->routeIs('suhu') ? 'active' : '' }}">
                <a href="{{ route('suhu') }}">
                  <i class="fas fa-thermometer-half"></i>
                  <p>Suhu &amp; Kelembaban</p>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('udara') ? 'active' : '' }}">
                <a href="{{ route('gas') }}">
                  <i class="fas fa-cloud"></i>
                  <p>Gas</p>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
      <!-- End Sidebar -->

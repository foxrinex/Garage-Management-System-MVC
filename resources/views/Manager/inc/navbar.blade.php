<style>
    /* Light up effect for links */
    .navbar-nav .nav-link {
        transition: all 0.3s ease;
        border-radius: 5px;
        padding: 8px 12px;
    }
    .navbar-nav .nav-link:hover {
        color: #fff !important;
        background-color: rgba(255, 255, 255, 0.15);
        box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
    }

    /* DROPDOWN FIXES */
    @media (min-width: 992px) {
        .nav-item.dropdown:hover > .dropdown-menu {
            display: block;
            margin-top: -2px !important; 
            z-index: 10000;
        }
        
        .dropdown-menu {
            border-top: 10px solid transparent !important;
            background-clip: padding-box;
        }
    }

    .ms-auto-custom {
        margin-right: 50px !important; 
    }

    .dropdown-item {
        transition: all 0.2s ease;
    }
    .dropdown-item:hover {
        background-color: #f8f9fa;
        color: #0d6efd !important;
        padding-left: 20px; 
    }

    .nav-link:hover i {
        text-shadow: 0 0 8px rgba(255,255,255,0.8);
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="{{ route('manager.dashboard') }}">
      <img src="{{ asset('logo.png') }}" width="40" alt="Logo">
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#managerNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="managerNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        
        <li class="nav-item">
          <a class="nav-link" href="{{ route('manager.dashboard') }}">
            <i class="fa fa-th-large me-1"></i> Dashboard
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="garageDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-wrench me-1"></i> Garage
          </a>
          <ul class="dropdown-menu shadow border-0" aria-labelledby="garageDrop">
            <li><h6 class="dropdown-header text-uppercase small">Inventory</h6></li>
            <li><a class="dropdown-item" href="{{ route('manager.inventory.view') }}"><i class="fa fa-list-ul me-2 text-muted"></i> Parts List</a></li>
            <li><a class="dropdown-item" href="{{ route('manager.inventory') }}"><i class="fa fa-plus-square me-2 text-muted"></i> Add/Restock Parts</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-uppercase small">Showroom</h6></li>
            <li><a class="dropdown-item" href="{{ route('manager.showroom') }}"><i class="fa fa-car me-2 text-muted"></i> Manage Vehicles</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-uppercase small">Services</h6></li>
            <li><a class="dropdown-item" href="{{ route('manager.repair-house') }}"><i class="fa fa-cogs me-2 text-muted"></i> Repair House</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="auctionDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-gavel me-1"></i> Auctions
          </a>
          <ul class="dropdown-menu shadow border-0" aria-labelledby="auctionDrop">
            <li><h6 class="dropdown-header text-uppercase small">Bidding</h6></li>
            <li><a class="dropdown-item" href="{{ route('manager.auction.dashboard') }}"><i class="fa fa-clock-o me-2 text-muted"></i> Live Auctions</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header text-uppercase small">Reports</h6></li>
            <li><a class="dropdown-item" href="{{ route('manager.auction.sales') }}"><i class="fa fa-money me-2 text-muted"></i> Auction Sales</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="{{ route('manager.sales-history') }}">
            <i class="fa fa-usd me-1"></i> Sales
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="{{ route('manager.warranties') }}">
            <i class="fa fa-shield me-1"></i> Warranties
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="{{ route('manager.employees.index') }}">
            <i class="fa fa-users me-1"></i> Employees
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="{{ route('manager.messages') }}">
            <i class="fa fa-envelope-o me-1"></i> Inquiries
            @if (isset($unread_count) && $unread_count > 0)
                <span class="badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                    {{ $unread_count }}
                </span>
            @endif
          </a>
        </li>

      </ul>

      <ul class="navbar-nav ms-auto ms-auto-custom mb-2 mb-lg-0 align-items-center">
        <li class="nav-item me-3">
          <a class="nav-link p-0" href="{{ route('manager.settings') }}" title="Garage Settings">
            <i class="fa fa-cog fa-lg"></i>
          </a>
        </li>
        
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white fw-bold" href="#" id="userDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-user-circle-o fa-lg me-1 text-primary"></i> {{ $nav_display_name ?? 'Manager' }}
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDrop">
            <li><a class="dropdown-item" href="{{ route('manager.profile') }}"><i class="fa fa-user me-2"></i> My Profile</a></li>
            <li><a class="dropdown-item" href="{{ route('manager.settings') }}"><i class="fa fa-university me-2"></i> Garage Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item text-danger" href="#" onclick="event.preventDefault(); document.getElementById('navbar-logout-form').submit();">
                    <i class="fa fa-sign-out me-2"></i> Logout
                </a>
                <form id="navbar-logout-form" action="{{ url('/logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
<?php
use Illuminate\Support\Facades\DB;

// Fetch customer name for the navbar if not already set
$nav_display_name = "Customer";
if (!isset($customer_name) && session()->has('user_id')) {
    $nav_user_id = session('user_id');
    $nav_cust_data = DB::select("SELECT fname FROM customer WHERE user_id = ?", [$nav_user_id]);
    if (!empty($nav_cust_data)) {
        $nav_display_name = $nav_cust_data[0]->fname;
    }
}

// Logic to calculate cart count
$nav_cart_count = 0;
if (session()->has('cart')) {
    $cart_session = session('cart');
    for ($i = 0; $i < count($cart_session); $i = $i + 1) {
        $nav_cart_count = $nav_cart_count + $cart_session[$i]['quantity'];
    }
}

// Logic to calculate wishlist count (temporarily set to 0 until wishlist table exists)
$nav_wish_count = 0;
?>

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

    /* DROPDOWN HOVER LOGIC */
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
    <a class="navbar-brand" href="{{ url('/Customer/index') }}">
      <img src="{{ asset('logo.png') }}" width="40">
    </a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="customerNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        
        <li class="nav-item">
          <a class="nav-link" href="{{ url('/Customer/index') }}">
            <i class="fa fa-home me-1"></i> Home
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="shopDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-shopping-bag me-1"></i> Shop
          </a>
          <ul class="dropdown-menu shadow border-0" aria-labelledby="shopDrop">
            <li><a class="dropdown-item" href="#"><i class="fa fa-gears me-2 text-muted"></i> Parts Store</a></li>
            <li><a class="dropdown-item" href="#"><i class="fa fa-car me-2 text-muted"></i> Car Showroom</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-warning" href="#"><i class="fa fa-wrench me-2"></i> Custom Build</a></li>
            <li><a class="dropdown-item text-info" href="#"><i class="fa fa-cogs me-2"></i> Repair or Service</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="auctionDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-gavel me-1"></i> Auctions
          </a>
          <ul class="dropdown-menu shadow border-0" aria-labelledby="auctionDrop">
            <li><a class="dropdown-item" href="#"><i class="fa fa-clock-o me-2 text-muted"></i> Live Auctions</a></li>
            <li><a class="dropdown-item" href="#"><i class="fa fa-history me-2 text-muted"></i> My Bidding History</a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fa fa-list-alt me-1"></i> Orders
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fa fa-comments-o me-1"></i> Reviews
          </a>
        </li>

      </ul>

      <ul class="navbar-nav ms-auto ms-auto-custom mb-2 mb-lg-0 align-items-center">
        <li class="nav-item me-2">
          <a class="nav-link position-relative" href="#" title="Wishlist">
            <i class="fa fa-heart text-danger fa-lg"></i>
            @if ($nav_wish_count > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                    {{ $nav_wish_count }}
                </span>
            @endif
          </a>
        </li>

        <li class="nav-item me-3">
          <a class="nav-link position-relative" href="#" title="Cart">
            <i class="fa fa-shopping-cart fa-lg"></i>
            @if ($nav_cart_count > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info text-dark" style="font-size: 0.6rem;">
                    {{ $nav_cart_count }}
                </span>
            @endif
          </a>
        </li>
        
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white fw-bold" href="#" id="userDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-user-circle-o fa-lg me-1 text-primary"></i> {{ $nav_display_name }}
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="userDrop">
            <li><a class="dropdown-item" href="{{ url('/Customer/profile') }}"><i class="fa fa-user me-2"></i> My Profile</a></li>
            <li><a class="dropdown-item" href="#"><i class="fa fa-shield me-2"></i> Warranty Claims</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="{{ url('/logout') }}"><i class="fa fa-sign-out me-2"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
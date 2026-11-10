<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Saved Car Builds</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .build-card {
            transition: transform 0.2s;
            border-radius: 12px;
            overflow: hidden;
        }
        .build-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            border-radius: 12px;
            border-left: 5px solid #0d6efd;
        }
    </style>
</head>

<body class="bg-light">

    @include('customer.inc.navbar')

    <div class="container mt-5">

        <div class="row mb-4 align-items-center">
            <div class="col-md-8">
                <h2 class="display-6 fw-bold">
                    <i class="fa fa-car text-primary"></i> Garage Inventory
                </h2>
                <p class="text-muted">Manage your custom configurations and compatibility checks.</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="{{ route('customer.car.builder') }}" class="btn btn-primary btn-lg shadow-sm">
                    <i class="fa fa-plus-circle"></i> Create New Build
                </a>
            </div>
        </div>

        <hr class="mb-5">

        @if ($totalBuildCount == 0)

            <div class="text-center py-5 bg-white rounded shadow-sm">
                <i class="fa fa-folder-open-o fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">Your garage is empty</h4>
                <p>You haven't saved any car configurations yet.</p>
                <a href="{{ route('customer.car.builder') }}" class="btn btn-outline-primary mt-2">Start Building Now</a>
            </div>

        @else
            
            <div class="row mb-5">
                <div class="col-md-4">
                    <div class="card stat-card shadow-sm p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-primary rounded-circle p-3 text-white">
                                <i class="fa fa-list-ul fa-lg"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0 text-muted">Saved Projects</h6>
                                <h3 class="mb-0 fw-bold">{{ $totalBuildCount }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">

                @for ($i = 0; $i < count($processedBuilds); $i = $i + 1)
                    @php
                        $b = $processedBuilds[$i];
                    @endphp

                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card build-card shadow-sm border-0 h-100">
                            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                                <div class="small text-uppercase fw-bold text-primary">PROJECT</div>
                                <span class="badge rounded-pill bg-light text-dark border">
                                    <i class="fa fa-calendar-o"></i> {{ date("M d, Y", strtotime($b['created_at'])) }}
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-3">{{ $b['name'] }}</h5>
                                
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted small">Total Investment:</span>
                                        <span class="fw-bold text-success h5 mb-0">
                                            ${{ number_format($b['total_cost'], 2) }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-muted small">Performance:</span>
                                        <span class="fw-bold text-dark">
                                            <i class="fa fa-bolt text-warning"></i> {{ number_format($b['total_hp']) }} HP
                                        </span>
                                    </div>
                                    
                                    <div class="p-2 rounded bg-light border text-center">
                                        @if (!$b['is_complete'])
                                            <small class="text-danger fw-bold">
                                                <i class="fa fa-times-circle"></i> Invalid (Missing Parts)
                                            </small>
                                        @elseif (!$b['is_compatible'])
                                            <small class="text-danger fw-bold">
                                                <i class="fa fa-warning"></i> Conflict Detected
                                            </small>
                                        @else
                                            <small class="text-success fw-bold">
                                                <i class="fa fa-check-circle"></i> Status: Optimized
                                            </small>
                                        @endif
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-9">
                                        <a href="{{ route('customer.build.details', $b['id']) }}" 
                                           class="btn btn-outline-primary w-100">
                                            <i class="fa fa-search"></i> Inspect Build
                                        </a>
                                    </div>
                                    <div class="col-3">
                                        <a href="{{ route('customer.build.delete', $b['id']) }}" 
                                           class="btn btn-outline-danger w-100"
                                           onclick="return confirm('Archive this project?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                @endfor

            </div>

        @endif

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
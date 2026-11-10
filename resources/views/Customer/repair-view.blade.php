<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Repair Details #{{ $repair->repair_id }} - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        body { background-color: #f4f7f6; color: #212529; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .detail-card { border: none; border-radius: 15px; background: #fff; }
        .status-header { background: #212529; color: #fff; border-radius: 15px 15px 0 0; padding: 20px; }
        .info-label { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 700; letter-spacing: 0.5px; }
        .info-value { font-size: 1rem; font-weight: 600; color: #212529; }
        .item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
        .item-row:last-child { border-bottom: none; }
        .part-img-sm { width: 40px; height: 40px; object-fit: cover; border-radius: 5px; }

        /* Status Bar Styling */
        .step-wrapper { padding: 20px 0; }
        .stepper { display: flex; justify-content: space-between; position: relative; margin-bottom: 20px; }
        .stepper::before { content: ""; position: absolute; top: 18px; left: 0; right: 0; height: 2px; background: #ddd; z-index: 0; }
        .step { position: relative; z-index: 1; text-align: center; flex: 1; }
        .step-circle { width: 36px; height: 36px; background: #fff; border: 2px solid #ddd; border-radius: 50%; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #ddd; transition: 0.3s; font-size: 14px; }
        
        .step.completed .step-circle { background: #198754; border-color: #198754; color: white; }
        .step.active .step-circle { background: #212529; border-color: #212529; color: white; }
        .step.rejected .step-circle { background: #dc3545; border-color: #dc3545; color: white; }
        
        .step-label { font-size: 10px; font-weight: 700; color: #bbb; text-transform: uppercase; }
        .step.active .step-label { color: #212529; }
        .step.completed .step-label { color: #198754; }
        .step.rejected .step-label { color: #dc3545; }
    </style>
</head>
<body>

    @include('customer.inc.navbar')

    <div class="container mt-4 mb-5">
        
        @if (session('success'))
            <div class="alert alert-success shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="fa fa-check-circle me-2 fs-4"></i>
                <div>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" role="alert">
                <i class="fa fa-times-circle me-2 fs-4"></i>
                <div>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <div class="mb-3">
            <a href="{{ route('customer.repair.dashboard') }}" class="text-decoration-none text-dark small fw-bold">
                <i class="fa fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

        <div class="card detail-card shadow-sm mb-4">
            <div class="card-body">
                <div class="step-wrapper">
                    <div class="stepper">
                        @php
                            $currentStatus = $repair->status;
                            $labels = ["Booked", "Review", "Payment", "Repair", "Ready", "Picked Up"];
                            $icons = ["fa-calendar-check-o", "fa-search", "fa-dollar", "fa-wrench", "fa-car", "fa-handshake-o"];
                            
                            $statusMap = [
                                "Pending"          => 0,
                                "Inspecting"       => 1,
                                "Awaiting Payment" => 2,
                                "Repairing"        => 3,
                                "Ready"            => 4,
                                "Completed"        => 5
                            ];
                        @endphp

                        @if ($currentStatus == "Rejected")
                            <div class="step rejected" style="flex: 0 0 100%;">
                                <div class="step-circle"><i class="fa fa-times"></i></div>
                                <div class="step-label">Request Rejected</div>
                            </div>
                        @else
                            @php
                                $activeIdx = $statusMap[$currentStatus] ?? 0;
                            @endphp

                            @for ($i = 0; $i < 6; $i = $i + 1)
                                @php
                                    $stepClass = "";
                                    if ($i < $activeIdx) {
                                        $stepClass = "completed";
                                    } else if ($i == $activeIdx) {
                                        $stepClass = "active";
                                    }
                                @endphp
                                <div class="step {{ $stepClass }}">
                                    <div class="step-circle">
                                        <i class="fa {{ $icons[$i] }}"></i>
                                    </div>
                                    <div class="step-label">{{ $labels[$i] }}</div>
                                </div>
                            @endfor
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card detail-card shadow-sm mb-4">
                    <div class="status-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Service Request #{{ $repair->repair_id }}</h5>
                            <small class="opacity-75">Booked on: {{ $repair->created_at }}</small>
                        </div>
                        <span class="badge bg-light text-dark px-3 py-2">{{ strtoupper($repair->status) }}</span>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <img src="{{ asset('uploads/' . $repair->car_image) }}" 
                                     class="img-fluid rounded shadow-sm"
                                     onerror="this.src='https://via.placeholder.com/300?text=No+Image'">
                            </div>
                            <div class="col-md-8">
                                <h4 class="fw-bold">{{ $repair->brand . " " . $repair->model }}</h4>
                                <p class="text-muted small mb-3">Production Year: {{ $repair->year }} | Mileage: {{ number_format($repair->mileage) }} KM</p>
                                
                                <div class="p-3 bg-light rounded">
                                    <div class="info-label">Customer Reported Issue:</div>
                                    <div class="info-value mt-1" style="font-weight: 400; font-style: italic;">
                                        "{{ $repair->issue_description }}"
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3"><i class="fa fa-gears me-2"></i>Parts Required/Selected</h6>
                        <div class="mb-4">
                            @if (count($partsList) > 0)
                                <ul class="list-group list-group-flush">
                                    @for ($i = 0; $i < count($partsList); $i = $i + 1)
                                        @php $part = $partsList[$i]; @endphp
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset('uploads/' . $part->image) }}" class="part-img-sm me-3" onerror="this.src='https://via.placeholder.com/40'">
                                                <div>
                                                    <div class="fw-bold">{{ $part->product_name }}</div>
                                                    <small class="text-muted">Unit Price: ${{ number_format($part->price_at_time, 2) }}</small>
                                                </div>
                                            </div>
                                            <span class="fw-bold text-dark">${{ number_format($part->price_at_time, 2) }}</span>
                                        </li>
                                    @endfor
                                </ul>
                            @else
                                <p class="text-muted small italic">No specific parts listed yet.</p>
                            @endif
                        </div>

                        <hr>

                        <h6 class="fw-bold mb-3"><i class="fa fa-file-text-o me-2"></i>Technician Workshop Log</h6>
                        @if (!empty($repair->repair_notes))
                            <div class="p-3 border-start border-4 border-dark bg-light">
                                {!! nl2br(e($repair->repair_notes)) !!}
                            </div>
                        @else
                            <p class="text-muted small italic">No technician notes available yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card detail-card shadow-sm">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-4 text-uppercase" style="letter-spacing: 1px;">Cost Breakdown</h6>
                        
                        <div class="item-row d-flex justify-content-between">
                            <span class="text-muted">Labor / Service Fee</span>
                            <span class="fw-bold">${{ number_format($displayLabor, 2) }}</span>
                        </div>

                        <div class="item-row d-flex justify-content-between">
                            <span class="text-muted">Parts & Materials</span>
                            <span class="fw-bold">${{ number_format($displayParts, 2) }}</span>
                        </div>

                        <div class="mt-4 p-3 bg-dark text-white rounded d-flex justify-content-between align-items-center">
                            <span class="small opacity-75">TOTAL AMOUNT</span>
                            <h4 class="mb-0 fw-bold">${{ number_format($totalBill, 2) }}</h4>
                        </div>

                        @if ($repair->status == 'Awaiting Payment')
                            <div class="mt-4">
                                <p class="small text-muted mb-3">Please choose your payment option below:</p>
                                
                                <div class="d-grid gap-2">
                                    <a href="{{ route('customer.repair.transaction', ['id' => $repair->repair_id]) }}" 
                                       class="btn btn-success fw-bold py-2">
                                        Pay Full Invoice (${{ number_format($totalBill, 2) }})
                                    </a>

                                    @if (!empty($repair->repair_type) && $repair->repair_type == 'Repair')
                                        <hr>
                                        <p class="text-center x-small text-danger fw-bold mb-1" style="font-size: 10px;">DON'T WANT THE PARTS?</p>
                                        <a href="#" class="btn btn-outline-secondary btn-sm fw-bold">
                                            Pay Diagnostics Fee Only
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($repair->status == 'Pending')
                            <div class="d-grid mt-4">
                                <a href="{{ route('customer.repair.cancel', ['id' => $repair->repair_id]) }}" 
                                class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('Are you sure you want to cancel this request?');">
                                    Cancel This Request
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <p class="text-muted" style="font-size: 11px;">
                        <i class="fa fa-shield me-1"></i> Quality service guaranteed by Rev Nation Workshop.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
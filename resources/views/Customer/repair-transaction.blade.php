<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete Payment - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background-color: #f4f7f6; color: #212529; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .payment-card { border: none; border-radius: 15px; background: #fff; overflow: hidden; }
        .bill-header { background: #212529; color: #fff; padding: 20px; }
        .item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
    </style>
</head>
<body>

    @include('customer.inc.navbar')

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                
                <div class="card payment-card shadow-lg">
                    <div class="bill-header text-center">
                        <h4 class="mb-0 fw-bold">Payment Summary</h4>
                        <small class="opacity-75">Repair ID: #{{ $repair->repair_id }}</small>
                    </div>
                    
                    <div class="p-4">
                        <div class="text-center mb-4">
                            <h6 class="text-muted mb-1">{{ $repair->brand . " " . $repair->model }}</h6>
                            <p class="small text-muted">Service Year: {{ $repair->year }}</p>
                        </div>

                        <div class="item-row d-flex justify-content-between">
                            <span>Labor Charges</span>
                            <span class="fw-bold">${{ number_format($labor, 2) }}</span>
                        </div>

                        <div class="item-row d-flex justify-content-between">
                            <span>Parts & Materials</span>
                            <span class="fw-bold">${{ number_format($parts, 2) }}</span>
                        </div>

                        <div class="d-flex justify-content-between mt-4 p-3 bg-light rounded">
                            <span class="fw-bold">Total Amount Due</span>
                            <h4 class="fw-extrabold text-success mb-0">${{ number_format($totalBill, 2) }}</h4>
                        </div>

                        <div class="mt-4">
                            <div class="alert alert-info py-2" style="font-size: 12px;">
                                <i class="fa fa-info-circle me-2"></i> By clicking pay, you agree to the service terms.
                            </div>
                            
                            <div class="d-grid gap-2">
                                {{-- Calls your existing repairTransaction function via this route --}}
                                <a href="{{ route('customer.repair.transaction.process', $repair->repair_id) }}" 
                                   class="btn btn-dark btn-lg fw-bold">
                                   <i class="fa fa-credit-card me-2"></i> Confirm and Pay
                                </a>
                                
                                <a href="{{ route('customer.repair.dashboard') }}" class="btn btn-link text-muted small text-decoration-none">Cancel Transaction</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
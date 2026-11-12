<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Order - Rev Nation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('customer.inc.navbar')

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0" style="border-radius: 15px;">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0 text-center">Which purchase is this for?</h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-4 text-center">
                            Target Item: <br>
                            <span class="h4 fw-bold">
                                {{ $item->name . " " . $item->subname }}
                            </span>
                        </p>
                        
                        <div class="list-group">
                            @for ($i = 0; $i < count($userOrders); $i = $i + 1)
                                @php
                                    $order = $userOrders[$i];
                                    $alreadyClaimed = !empty($order->claim_id);
                                    
                                    // Construct the route parameters based on item type
                                    $claimParams = ['order_id' => $order->id];
                                    if ($isCar) {
                                        $claimParams['car_id'] = $itemId;
                                    } else {
                                        $claimParams['product_id'] = $itemId;
                                    }
                                @endphp
                                
                                @if ($alreadyClaimed)
                                    <div class="list-group-item list-group-item-light d-flex justify-content-between align-items-center opacity-75">
                                        <div>
                                            <span class="text-muted small">Order #{{ $order->id }}</span><br>
                                            <small class="text-muted">Purchased: {{ $order->date_ordered }}</small>
                                        </div>
                                        <span class="badge bg-secondary">Claim Already Filed</span>
                                    </div>
                                @else
                                    <a href="{{ route('customer.warranty.claim.form', $claimParams) }}" 
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <span class="fw-bold">Order #{{ $order->id }}</span><br>
                                            <small class="text-muted">Purchased: {{ $order->date_ordered }}</small>
                                        </div>
                                        <i class="fa fa-chevron-right text-warning"></i>
                                    </a>
                                @endif

                            @endfor
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 p-3">
                        <a href="{{ $backLink }}" class="btn btn-outline-secondary w-100">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
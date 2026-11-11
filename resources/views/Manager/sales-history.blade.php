<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ Auth::user()->role ?? 'Manager' }} Sales History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Sales & Revenue Report</h3>
            
            <form action="{{ route('manager.sales-history') }}" method="GET" class="d-flex w-50">
                <input type="text" 
                       name="search" 
                       class="form-control me-2" 
                       placeholder="Search customer, car or part..." 
                       value="{{ $search }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow border-0">
                    <div class="card-body text-center">
                        <h5 class="text-uppercase small">Total Revenue</h5>
                        <h2 class="display-6 fw-bold">${{ number_format($total_revenue, 2) }}</h2>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow border-0">
                    <div class="card-body text-center">
                        <h5 class="text-uppercase small">Orders Processed</h5>
                        <h2 class="display-6 fw-bold">{{ $order_count }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive shadow-sm bg-white p-3 rounded">
            <table class="table table-hover text-center align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Item (Car/Part)</th>
                        <th>Qty</th>
                        <th>Total Paid</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $orderCount = count($all_orders);
                    @endphp

                    @for ($j = 0; $j < $orderCount; $j = $j + 1) 
                        @php
                            $order = $all_orders[$j];
                            
                            // Handle Customer Name
                            $customerDisplay = '<span class="text-danger"><i>Deleted Account</i></span>';
                            if (!empty($order->username)) {
                                $customerDisplay = $order->username;
                            }

                            // Handle Product Display
                            $productDisplay = "Deleted Item";

                            if (!empty($order->car_id) && !empty($order->car_model)) {
                                $productDisplay = $order->car_brand . " " . $order->car_model . " (Car)";
                            } else if (!empty($order->product_id) && !empty($order->p_name)) {
                                $productDisplay = $order->p_name;
                            } else if (!empty($order->product_name)) {
                                $productDisplay = $order->product_name;
                            }
                        @endphp
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{!! $customerDisplay !!}</td>
                            <td><strong>{{ $productDisplay }}</strong></td>
                            <td>{{ $order->quantity }}</td>
                            <td class="text-success fw-bold">${{ number_format($order->total_price, 2) }}</td>
                            <td>{{ $order->date_ordered }}</td>
                        </tr>
                    @endfor

                    @if ($order_count == 0)
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No sales records found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
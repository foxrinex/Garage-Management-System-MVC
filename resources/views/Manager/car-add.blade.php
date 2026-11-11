<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Vehicle Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .page-header-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 10px 0; }
        .header-left { display: flex; align-items: center; }
        .page-header-icon { font-size: 1.8rem; margin-right: 10px; color: #6c757d; }
        .page-header-title { font-weight: 600; font-size: 1.6rem; color: #333; margin: 0; }
        .cat-list { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5">
        <div class="page-header-container">
            <div class="header-left">
                <div class="page-header-icon"><i class="fa fa-car"></i></div>
                <h2 class="page-header-title">Vehicle Management</h2>
            </div>
            <a href="{{ route('manager.showroom') }}" class="btn btn-dark">
                <i class="fa fa-chevron-left"></i> Back to Showroom
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm border-0 mb-5">
                    <div class="card-header bg-white fw-bold">Add New Vehicle</div>
                    <div class="card-body p-4">
                        <form action="{{ route('manager.car.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Brand</label>
                                    <input type="text" name="brand" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Model</label>
                                    <input type="text" name="model" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Category</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Select Category</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Transmission</label>
                                    <select name="transmission" class="form-select" required>
                                        @foreach ($transmissions as $tx)
                                            <option value="{{ $tx }}">{{ $tx }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Year</label>
                                    <input type="number" name="year" class="form-control" min="1900" max="{{ $currYear }}" value="{{ $currYear }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Mileage (km)</label>
                                    <input type="number" name="mileage" class="form-control" min="1" max="999999999" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Price ($)</label>
                                    <input type="number" name="price" class="form-control" min="1" max="999999999" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Stock Quantity</label>
                                    <input type="number" name="quantity" id="quantityInput" class="form-control" value="1" oninput="validateQty()" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Initial Status</label>
                                    <select name="status" id="statusSelect" class="form-select" onchange="toggleQuantity()">
                                        <option value="In Stock">In Stock</option>
                                        <option value="Upcoming">Upcoming</option>
                                        <option value="Sold Out">Sold Out</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Warranty Types</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="regular_warranty" value="1" id="regW" checked>
                                    <label class="form-check-label" for="regW">Regular (Repair)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="replacement_warranty" value="1" id="repW">
                                    <label class="form-check-label" for="repW">Replacement</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Vehicle Image</label>
                                <input type="file" name="car_image" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Save Vehicle</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-bold">Manage Categories</div>
                    <div class="card-body">
                        <form action="{{ route('manager.category.store') }}" method="POST" id="catForm">
                            @csrf
                            <input type="hidden" name="category_id_edit" id="cat_id_edit">
                            <input type="hidden" name="type" value="Car">

                            <div class="mb-3">
                                <label class="form-label" id="catLabel">New Category Name</label>
                                <div class="input-group">
                                    <input type="text" name="category_name" id="cat_name_input" class="form-control" required>
                                    <button type="submit" class="btn btn-success" id="catBtn">Add</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-link d-none" id="cancelEdit" onclick="resetCatForm()">Cancel Edit</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <div class="table-responsive cat-list">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $cat)
                                        <tr>
                                            <td>{{ $cat->category_name }}</td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-warning" onclick="editCategory('{{ $cat->id }}', '{{ $cat->category_name }}')">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <a href="{{ route('manager.category.delete', $cat->id) }}" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleQuantity() {
            var status = document.getElementById('statusSelect').value;
            var qtyInput = document.getElementById('quantityInput');
            if (status === 'Upcoming' || status === 'Sold Out') {
                qtyInput.value = 0;
                qtyInput.readOnly = true;
                qtyInput.style.backgroundColor = "#e9ecef";
            } else {
                if (qtyInput.value <= 0) { qtyInput.value = 1; }
                qtyInput.readOnly = false;
                qtyInput.style.backgroundColor = "#ffffff";
            }
        }

        function validateQty() {
            var status = document.getElementById('statusSelect').value;
            var qtyInput = document.getElementById('quantityInput');
            if (status === 'In Stock' && qtyInput.value < 1) { qtyInput.value = 1; }
        }

        function editCategory(id, name) {
            document.getElementById('catForm').action = "{{ route('manager.category.update') }}";
            document.getElementById('cat_id_edit').value = id;
            document.getElementById('cat_name_input').value = name;
            document.getElementById('catLabel').innerText = "Edit Category Name";
            document.getElementById('catBtn').innerText = "Update";
            document.getElementById('catBtn').className = "btn btn-warning";
            document.getElementById('cancelEdit').classList.remove('d-none');
        }

        function resetCatForm() {
            document.getElementById('catForm').action = "{{ route('manager.category.store') }}";
            document.getElementById('cat_id_edit').value = "";
            document.getElementById('cat_name_input').value = "";
            document.getElementById('catLabel').innerText = "New Category Name";
            document.getElementById('catBtn').innerText = "Add";
            document.getElementById('catBtn').className = "btn btn-success";
            document.getElementById('cancelEdit').classList.add('d-none');
        }

        window.onload = function() { toggleQuantity(); };
    </script>
</body>
</html>
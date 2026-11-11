<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager - Parts Inventory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        .management-card { border: none; border-radius: 15px; transition: transform 0.2s; }
        .card-header { border-radius: 15px 15px 0 0 !important; font-weight: 600; }
        .tag-scroll { border: 1px solid #dee2e6; background-color: #f8f9fa; }
        .btn-action { border-radius: 8px; font-weight: 500; }
        .category-item { border-left: 4px solid #343a40; transition: all 0.2s; }
        .category-item:hover { background-color: #f1f1f1; }
    </style>
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5 mb-5">
        <div class="d-flex align-items-center justify-content-center mb-5">
            <div class="text-center">
                <h2 class="fw-bold mb-1"><i class="fa fa-sliders text-primary me-2"></i>Parts Management Hub</h2>
                <p class="text-muted">Configure stock levels, new parts, and build compatibility</p>
            </div>
        </div>
        
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0">
                <i class="fa fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <!-- 1. Quick Stock Update -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 management-card border-top border-success border-4">
                    <div class="card-header bg-white text-success py-3">
                        <h5 class="mb-0"><i class="fa fa-cubes me-2"></i>Quick Stock Update</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('manager.inventory.restock') }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Select Part</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">-- Choose Part --</option>
                                    @foreach ($products as $prod)
                                        <option value="{{ $prod['id'] }}">{{ $prod['product_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Adjustment Type</label>
                                <select name="type" class="form-select">
                                    <option value="add">Add to Stock (+)</option>
                                    <option value="subtract">Remove from Stock (-)</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold">Quantity</label>
                                <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                            </div>

                            <button type="submit" class="btn btn-success w-100 btn-action py-2 shadow-sm">
                                <i class="fa fa-refresh me-2"></i>Apply Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2. Register New Part -->
            <div class="col-md-4">
                <div class="card shadow-sm h-100 management-card border-top border-primary border-4">
                    <div class="card-header bg-white text-primary py-3">
                        <h5 class="mb-0"><i class="fa fa-plus-circle me-2"></i>Register New Part</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('manager.inventory.store.part') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Part Name</label>
                                <input type="text" name="p_name" class="form-control" placeholder="e.g. Turbocharger" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="0">-- Select Category --</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat['id'] }}">{{ $cat['category_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Technical details..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Compatibility Tags</label>
                                <div class="p-2 rounded tag-scroll" style="max-height: 100px; overflow-y: auto;">
                                    @foreach ($all_tags as $i => $tag)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="{{ $tag['tag_name'] }}" id="tag_{{ $i }}">
                                            <label class="form-check-label small" for="tag_{{ $i }}">{{ $tag['tag_name'] }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="form-text small">None = Compatible with all builds.</div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <label class="form-label small fw-bold">Price ($)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" min="0" required placeholder="0.00">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">Initial Stock</label>
                                    <input type="number" name="quantity" class="form-control" value="1" min="0">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">HP</label>
                                    <input type="number" name="hp" class="form-control" placeholder="0">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block small fw-bold">Warranty Options</label>
                                <div class="bg-light p-2 rounded border">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="regular_warranty" value="1" id="regW" checked>
                                        <label class="form-check-label small" for="regW">Regular</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="replacement_warranty" value="1" id="repW">
                                        <label class="form-check-label small" for="repW">Replacement</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold">Part Image</label>
                                <input type="file" name="product_image" class="form-control form-control-sm">
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-action py-2">
                                <i class="fa fa-save me-2"></i>Save Part to Database
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3. Build Categories & System Tags -->
            <div class="col-md-4">
                <!-- Build Categories Card -->
                <div class="card shadow-sm management-card border-top border-dark border-4 mb-4">
                    <div class="card-header bg-white text-dark py-3">
                        <h5 class="mb-0"><i class="fa fa-folder-open me-2"></i>Build Categories</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('manager.builder.settings') }}" method="POST" class="mb-4" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="type" value="add_category">
                            <div class="mb-2">
                                <input type="text" name="category_name" class="form-control form-control-sm" placeholder="New Category Name..." required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-bold">Category Image (Required)</label>
                                <input type="file" name="category_img" class="form-control form-control-sm" required>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="newMand">
                                    <label class="form-check-label small" for="newMand">Mandatory</label>
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm px-3">Create</button>
                            </div>
                        </form>

                        <div class="list-group overflow-auto" style="max-height: 350px;">
                            @foreach ($categories as $cat)
                                @php
                                    $icon_path = !empty($cat['category_img']) ? asset('uploads/' . $cat['category_img']) : asset('uploads/default-cat.png');
                                @endphp
                                <div class="list-group-item category-item mb-2 rounded border shadow-sm p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="d-flex align-items-center">
                                            <img src="{{ $icon_path }}" class="rounded border me-2 shadow-sm" style="width: 35px; height: 35px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/100'">
                                            <div>
                                                <div class="fw-bold mb-0">{{ $cat['category_name'] }}</div>
                                                @if ($cat['is_mandatory'])
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.6rem;">REQUIRED FOR BUILD</span>
                                                @endif
                                            </div>
                                        </span>
                                        <div class="btn-group">
                                            <a href="{{ route('manager.builder.category.toggle', ['id' => $cat['id'], 'current' => $cat['is_mandatory']]) }}" 
                                               class="btn btn-sm {{ $cat['is_mandatory'] ? 'btn-warning' : 'btn-outline-secondary' }}" title="Toggle Mandatory">
                                                <i class="fa fa-anchor"></i>
                                            </a>
                                            <a href="{{ route('manager.builder.category.delete', $cat['id']) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <form action="{{ route('manager.builder.settings') }}" method="POST" class="input-group input-group-sm mb-2">
                                        @csrf
                                        <input type="hidden" name="type" value="edit_category">
                                        <input type="hidden" name="category_id" value="{{ $cat['id'] }}">
                                        <input type="text" name="new_name" class="form-control" placeholder="Rename..." required>
                                        <button type="submit" class="btn btn-warning">Rename</button>
                                    </form>

                                    <form action="{{ route('manager.builder.settings') }}" method="POST" enctype="multipart/form-data" class="d-flex">
                                        @csrf
                                        <input type="hidden" name="type" value="edit_category_img">
                                        <input type="hidden" name="category_id" value="{{ $cat['id'] }}">
                                        <input type="file" name="category_img" class="form-control form-control-sm me-1" required>
                                        <button type="submit" class="btn btn-sm btn-outline-info"><i class="fa fa-image"></i></button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- System Tags Card -->
                <div class="card shadow-sm management-card border-top border-info border-4">
                    <div class="card-header bg-white text-info py-3">
                        <h5 class="mb-0"><i class="fa fa-tags me-2"></i>System Tags</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('manager.builder.settings') }}" method="POST" class="mb-3">
                            @csrf
                            <input type="hidden" name="type" value="add_tag">
                            <div class="input-group">
                                <input type="text" name="tag_name" class="form-control" placeholder="New Compatibility Tag..." required>
                                <button class="btn btn-info text-white" type="submit">Add</button>
                            </div>
                        </form>
                        
                        <div class="list-group list-group-flush overflow-auto rounded border" style="max-height: 180px;">
                            @foreach ($all_tags as $tag)
                                <div class="list-group-item p-2">
                                    <div class="d-flex align-items-center m-0">
                                        <form action="{{ route('manager.builder.settings') }}" method="POST" class="d-flex align-items-center m-0 flex-grow-1">
                                            @csrf
                                            <input type="hidden" name="type" value="edit_tag">
                                            <input type="hidden" name="tag_id" value="{{ $tag['id'] }}">
                                            <input type="text" name="new_tag_name" class="form-control form-control-sm border-0 bg-transparent me-1" value="{{ $tag['tag_name'] }}">
                                            <button type="submit" class="btn btn-sm text-warning me-2" title="Update Tag">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('manager.builder.tag.delete', $tag['id']) }}" class="btn btn-sm text-danger" onclick="return confirm('Delete tag?')">
                                            <i class="fa fa-times"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
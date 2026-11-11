<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager - Messages</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body class="bg-light">
    @include('manager.inc.navbar')

    <div class="container mt-5" style="width: 90%; max-width: 800px;">
        
        <div class="bg-white p-4 rounded shadow-sm">
            <h4 class="text-center mb-4">Customer Inquiries</h4>

            <form action="{{ route('manager.messages') }}" method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by name or email..."
                           value="{{ $search ?? '' }}">
                    <button class="btn btn-primary" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                    @if (!empty($search))
                        <a href="{{ route('manager.messages') }}" class="btn btn-secondary">Clear</a>
                    @endif
                </div>
            </form>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($messages->count() > 0)
                <div class="accordion accordion-flush" id="accordionMessages">
                    @foreach ($messages as $msg)
                        @php
                            $msgId = $msg->message_id; 
                            $isUnread = ($msg->status == 'Unread');
                        @endphp

                        <div class="accordion-item shadow-sm mb-3 border rounded" id="accordion-item-{{ $msgId }}">
                            <h2 class="accordion-header" id="flush-heading_{{ $msgId }}">
                              <button class="accordion-button collapsed" 
                                      type="button" 
                                      data-bs-toggle="collapse" 
                                      data-bs-target="#flush-collapse_{{ $msgId }}"
                                      data-message-id="{{ $msgId }}">
                            
                                <i id="icon-{{ $msgId }}" class="fa fa-envelope-o me-3 {{ $isUnread ? 'text-danger' : 'text-primary' }}"></i>
                                
                                <div class="d-flex justify-content-between w-100 me-3">
                                    <span id="sender-{{ $msgId }}" class="{{ $isUnread ? 'fw-bold' : '' }}">
                                        {{ $msg->sender_full_name }}
                                    </span>
                                    <small class="text-muted">{{ date("M d, Y", strtotime($msg->date_time)) }}</small>
                                </div>

                              </button>
                            </h2>

                            <div id="flush-collapse_{{ $msgId }}" 
                                 class="accordion-collapse collapse" 
                                 data-bs-parent="#accordionMessages">
                              <div class="accordion-body">
                                
                                <div class="p-3 bg-light rounded mb-3">
                                    <p class="mb-0">{!! nl2br(e($msg->message)) !!}</p>
                                </div>

                                <div class="row text-muted small">
                                    <div class="col-sm-6">
                                        <i class="fa fa-envelope"></i> <b>Email:</b> {{ $msg->sender_email }}
                                    </div>
                                    <div class="col-sm-6 text-sm-end">
                                        <i class="fa fa-clock-o"></i> <b>Received:</b> {{ $msg->date_time }}
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end border-top mt-3 pt-2">
                                    <form action="{{ route('manager.messages.delete') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="message_id" value="{{ $msgId }}">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                onclick="return confirm('Delete this inquiry?')">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>

                              </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info m-5 text-center" role="alert">
                    No inquiries found.
                </div>
            @endif
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>  

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const collapseElements = document.querySelectorAll('.accordion-collapse');

            collapseElements.forEach(function (collapseEl) {
                collapseEl.addEventListener('show.bs.collapse', function () {
                    // Extract message ID from the collapse element's ID
                    const msgId = this.id.replace('flush-collapse_', '');
                    const iconEl = document.getElementById('icon-' + msgId);
                    const senderEl = document.getElementById('sender-' + msgId);

                    // Check if it's currently unread visually
                    if (iconEl && iconEl.classList.contains('text-danger')) {
                        // Send fetch request to mark as read in the backend
                        fetch("{{ url('/manager/messages/mark-read') }}/" + msgId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update UI smoothly without reloading
                                iconEl.classList.remove('text-danger');
                                iconEl.classList.add('text-primary');
                                if (senderEl) {
                                    senderEl.classList.remove('fw-bold');
                                }

                                // Update the navbar badge count dynamically
                                const badgeEl = document.querySelector('#navbar-unread-badge .badge');
                                if (badgeEl) {
                                    let currentCount = parseInt(badgeEl.innerText);
                                    if (currentCount > 1) {
                                        badgeEl.innerText = currentCount - 1;
                                    } else {
                                        badgeEl.remove(); // Remove badge if count drops to 0
                                    }
                                }
                            }
                        })
                        .catch(error => console.error('Error marking message as read:', error));
                    }
                });
            });
        });
    </script>
</body>
</html>
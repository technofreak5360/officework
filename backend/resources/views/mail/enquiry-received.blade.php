<!doctype html>
<html>
<body>
    <h1>New product enquiry</h1>

    <p><strong>Product:</strong> {{ $enquiry->product_title }} (ID {{ $enquiry->product_id }})</p>
    <p><strong>Name:</strong> {{ $enquiry->name }}</p>
    <p><strong>Email:</strong> {{ $enquiry->email }}</p>

    @if ($enquiry->phone)
        <p><strong>Phone:</strong> {{ $enquiry->phone }}</p>
    @endif

    @if ($enquiry->message)
        <p><strong>Message:</strong></p>
        <p>{!! nl2br(e($enquiry->message)) !!}</p>
    @endif
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - Super Steel</title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet"> <!-- Include your CSS -->
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Delete Account</h1>
        <p class="text-center text-danger">
            Are you sure you want to delete your account? This action cannot be undone.
        </p>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @elseif (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form action="{{ route('delete.account') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="mobile">Enter Your Mobile Number</label>
                <input type="text" name="mobile" id="mobile" class="form-control" placeholder="Enter your mobile number" required>
            </div>
            <div class="form-group text-center mt-3">
                <button type="submit" class="btn btn-danger">Delete My Account</button>
            </div>
        </form>
    </div>
</body>
</html>

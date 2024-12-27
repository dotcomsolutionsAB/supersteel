<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - Super Steel</title>
    <style>
        /* General Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 30px;
            text-align: center;
        }

        .logo {
            width: 150px;
            margin: 0 auto 20px;
        }

        h1 {
            font-size: 24px;
            color: #333;
        }

        p {
            font-size: 16px;
            color: #777;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .header-btn {
            margin-top: 20px;
            display: inline-block;
            text-align: center;
        }

        .header-btn-1 {
            display: inline-block;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .header-btn-1:hover {
            background-color: #0056b3;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }

        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://www.supersteel.in/wp-content/uploads/2024/06/Logo.svg" alt="Super Steel Logo" class="logo">
        <h1>Delete Account</h1>
        <p class="text-danger">Are you sure you want to delete your account? This action cannot be undone.</p>

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
            <div class="header-btn">
                <button type="submit" class="header-btn-1 button-primary">Delete My Account</button>
            </div>
        </form>
    </div>
</body>
</html>

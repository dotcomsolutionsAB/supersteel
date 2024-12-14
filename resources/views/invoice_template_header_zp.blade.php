<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Invoice</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            padding-top: 15px;
        }
        .header img {
            width: 100%;
            display: block;
            height: auto;
        }
        .customer-info, .order-details, .order-summary {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .customer-info td, .order-details td, .order-summary th, .order-summary td {
            padding: 8px;
        }
        .order-summary th {
            background-color: grey;
            color: white;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            background-color: lightgrey; /* Light grey background */
            color: black; /* Black text */
            padding: 5px; /* Thinner padding for a less bulky look */
        }
        table, th, td {
            border: 1px solid #ddd;
            border-collapse: collapse;
        }
        .center-align {
            text-align: center;
        }
        .right-align {
            text-align: right;
        }
        .label {
            width: 15%; /* Slimmer labels */
        }
        .value {
            width: 35%; /* Wider values */
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <img src="{{ asset('storage/uploads/s1.jpg') }}" alt="Logo">
    </div>

    <!-- Customer and Order Information -->
    <table class="customer-info">
        <tr>
            <td class="label">Client:</td>
            <td class="value">{{ $user->name }}</td>
            <td class="label">Order ID:</td>
            <td class="value">{{ $order->order_id }}</td>
        </tr>
        <tr>
            <td class="label">Address:</td>
            <td class="value">{{ $user->address_line_1 }}{{ !empty($user->address_line_1) && !empty($user->address_line_2) ? ', ' : '' }}{{ $user->address_line_2 }}</td>
            <td class="label">Order Date:</td>
            <td class="value">{{ \Carbon\Carbon::parse($order->order_date)->format('d-m-Y') }}</td>
        </tr>
        <tr>
            <td colspan="4" class="value"><strong>Remarks:</strong> {{ $order->remarks }}</td>
        </tr>
    </table>

    <!-- Order Details -->
    <table class="order-summary">
        <thead>
            <tr>
                <th class="center-align">SN</th>
                <th>Photo</th>
                <th>Product Name</th>
                <th class="center-align">Qty</th>
            </tr>
        </thead>
        <tbody>
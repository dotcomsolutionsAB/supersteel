<!DOCTYPE html>
<html>
<head>
    <title>VCL Items</title>
    <style>
        /* Styling for the brown box */
        .title-box {
            background-color: brown;
            color: white;
            text-align: center;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 8px 8px 0 0; /* Rounded corners on top only */
            margin-bottom: 0; /* Ensure no gap between title and table */
        }
        /* Styling for the table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }

        /* Styling for table headers */
        th {
            background-color: blue;
            color: white;
            padding: 10px;
            text-align: left;
        }

        /* Specific styling for the "PRICE" column */
        .price-column {
            background-color: lightblue;
        }

        /* Image styling */
        img {
            width: 80px;
            height: auto;
        }
    </style>
</head>
<body>
    <!-- Title Box -->
    <div class="title-box">
        {{ $get_product_details->product_name }} - {{$get_product_details->product_code}}
    </div>

    <!-- Table for the Items -->
    <table>
        <thead>
            <tr>
                <th>S.NO</th>
                <th>ITEM NO</th>
                <th>ITEM</th>
                <th>MODEL</th>
                <th>PRICE</th>
                <th>Image</th>
            </tr>
        </thead>
        <tbody>
            @foreach($get_record as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td> <!-- S.NO -->
                    <td>{{ $item->product_code }}</td>
                    <td class="print-column">{{ $item->print_name }}</td>
                    <td>{{ $item->brand }}</td>
                    <td>{{ $item->price }}</td>
                    <td><img src="{{ public_path($item->product_image)}}" alt="{{ $item->print_name }}"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

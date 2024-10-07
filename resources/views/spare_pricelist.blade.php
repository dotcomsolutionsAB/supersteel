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
        }
        table {
            width: 90%; /* Make the table larger */
            margin: 0 auto; /* Center the table */
            border-collapse: collapse;
            font-size: 16px; /* Increase font size for better visibility */
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
        .print-column {
            background-color: lightblue;
        }

        /* Image styling */
        td img {
            width: 100%; /* Image fills the table cell */
            height: 100px; /* Fixed height for the image */
            object-fit: cover; /* Ensure the image fills the box without distortion */
        }
    </style>
</head>
<body>
    <!-- Title Box -->
    <div class="title-box">
        VCL Items List
    </div>

    <table>
        <thead>
            <tr>
                <th>S.NO</th>
                <th>ITEM NO</th>
                <th>ITEM</th>
                <th>MODEL</th>
                <th>PRICE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($get_record as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td> <!-- S.NO -->
                    <td>{{ $item->product_code }}</td>
                    <td class="print-column">{{ $item->print_name }}</td>
                    <td>{{ $item->brand }}</td>
                    <td><img src="{{ public_path($item->product_image)}}" alt="{{ $item->print_name }}"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

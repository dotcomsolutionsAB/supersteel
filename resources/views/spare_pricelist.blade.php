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
        /* Make the table full width */
        .table-container {
            width: 95%;
            margin: 0 auto; /* Center the entire table block */
            border: 1px solid black;
            border-radius: 0 0 8px 8px; /* Rounded corners on bottom only */
            overflow: hidden; /* Ensures the image doesn't overflow */
        }

        table {
            width: 100%; /* Full width table */
            border-collapse: collapse;
            font-size: 16px;
        }

        /* Table borders */
        table, th, td {
            border: 1px solid black;
        }

        /* Styling for table headers */
        th {
            background-color: blue;
            color: white;
            padding: 12px;
            text-align: left;
        }

        /* Specific styling for the "print-column" */
        .print-column {
            background-color: lightblue;
        }

        /* Image styling to fill the table cell */
        td img {
            width: 100%; /* Image fills the entire cell */
            height: 100%; /* Ensure the image takes the height of the cell */
            object-fit: cover; /* Ensures the image fills without distorting */
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

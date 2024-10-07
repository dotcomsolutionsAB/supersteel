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
            padding: 10px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 8px;
            margin-bottom: 20px;
        }
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

        /* Styling for table rows */
        td {
            background-color: lightblue;
            padding: 10px;
            text-align: left;
        }

        /* Image styling */
        img {
            width: 50px;
            height: auto;
        }
    </style>
</head>
<body>
    <h1>VCL Items List</h1>
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
                    <td>{{ $item->print_name }}</td>
                    <td>{{ $item->brand }}</td>
                    <td>{{ $item->product_image }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
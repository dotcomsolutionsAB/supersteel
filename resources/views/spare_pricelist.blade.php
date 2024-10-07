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
            width: 80%; /* Increase the width of the title box */
            margin: 0 auto 20px; /* Center the box and add margin */
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
        img {
            width: 70px
            
            /* Ensure that the table and content are responsive */
        @media print, screen {
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }

            table {
                width: 100%; /* Make sure the table uses full width on smaller screens or prints */
            }
        }
;
            height: auto;
        }

        /* Ensure that the table and content are responsive */
        @media print, screen {
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }

            table {
                width: 100%; /* Make sure the table uses full width on smaller screens or prints */
            }
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
                    <td class="print-column">{{ $item->print_name }}</td>
                    <td>{{ $item->brand }}</td>
                    <td><img src="{{ public_path($item->product_image)}}" alt="{{ $item->print_name }}"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

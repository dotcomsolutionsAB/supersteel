<!DOCTYPE html>
<html>
<head>
    <title>VCL Items</title>
    <style>
        /* Container for the header (image on the left, text on the right) */
        .header-box {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            background-color: brown;
            color: white;
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            border-radius: 8px 8px 0 0;
        }

        /* Styling for the image */
        .header-box img {
            width: 100px;
            height: auto;
            object-fit: contain;
            margin-right: 20px; /* Space between the image and text */
        }

        /* Right align for the text */
        .header-box .product-details {
            text-align: left;
            flex-grow: 1;
        }

        /* Styling for the table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
            text-align: center; /* Center-align all table content */
            padding: 10px;
        }

        /* Styling for table headers */
        th {
            background-color: grey;
            color: white;
            padding: 10px;
            text-align: left;
        }

        /* Specific styling for the "PRICE" column */
        .price-column {
            background-color: lightblue;
        }

        /* Image styling inside the table */
        td img {
            width: 80px;
            height: auto;
        }
    </style>
</head>
<body>
    <!-- Title Box with image and product details -->
    <div class="header-box">
        <img src="{{ public_path($get_product_details->product_image) }}" alt="Product Image"> <!-- Product Image -->
        <div class="product-details">
            {{ $get_product_details->product_name }} - {{ $get_product_details->product_code }} <!-- Product Title -->
        </div>
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

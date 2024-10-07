<head>
    <title>VCL Items</title>
    <style>
       /* Container for the header (image on the left, text on the right) */
       .header-box {
        /* Container for the header (image on the left, text on the right) */
        .header-box {
            display: flex;
            justify-content: space-between;
            justify-content: flex-start;
            align-items: center;
            background-color: brown;
            color: white;
@@ -26,8 +26,8 @@
        /* Right align for the text */
        .header-box .product-details {
            text-align: right;
            flex-grow: 1; /* Ensure the text takes up the remaining space */
            text-align: left;
            flex-grow: 1;
        }
        /* Styling for the table */
@@ -54,19 +54,19 @@
            background-color: lightblue;
        }
        /* Image styling */
        img {
        /* Image styling inside the table */
        td img {
            width: 80px;
            height: auto;
        }
    </style>
</head>
<body>
    <!-- Title Box -->
    <!-- Title Box with image and product details -->
    <div class="header-box">
        <img src="{{ public_path($get_product_details->product_image) }}" alt="Product Image">
        <img src="{{ public_path($get_product_details->product_image) }}" alt="Product Image"> <!-- Product Image -->
        <div class="product-details">
            {{ $get_product_details->product_name }} - {{$get_product_details->product_code}}
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
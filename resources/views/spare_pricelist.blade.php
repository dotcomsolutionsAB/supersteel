<!DOCTYPE html>
<html>
<head>
    <title>VCL Items</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
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
            @foreach($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td> <!-- S.NO -->
                    <td>{{ $item->ITEM_NO }}</td>
                    <td>{{ $item->ITEM }}</td>
                    <td>{{ $item->MODEL }}</td>
                    <td>{{ $item->price }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

<tr>
    <td class="center-align">{{ $index + 1 }}</td>
    <td><img src="{{ $item->product_image }}" alt="" style="height: 60px; width: 60px;"></td>
    <td>{{ $item->product_name }}<br>Part No: {{ $item->product->product_code }}<br>Remarks: {{ $item->remarks }}</td>
    <td class="center-align">{{ $item->quantity }}</td>
</tr>
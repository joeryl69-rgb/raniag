@if(!empty($shelters))
    <div class="section-title">People in shelters</div>
    <p style="font-size:10pt; margin:0 0 8px;">
        {{ $shelters['inside'] }} from Pamplona,
        {{ $shelters['outside'] }} from outside the municipality,
        {{ $shelters['still_in'] }} still inside.
    </p>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Shelter</th>
                <th>From</th>
                <th>Origin</th>
                <th>Checked in</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($shelters['rows'] as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['shelter'] }}</td>
                    <td>{{ $row['from'] }}</td>
                    <td>{{ $row['origin'] }}</td>
                    <td>{{ $row['checked_in'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No one checked into a shelter during these dates.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif

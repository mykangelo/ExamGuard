@php
    $formatAttemptTime = function ($attempt) {
        if (!$attempt->started_at) {
            return '—';
        }
        $end = $attempt->submitted_at ?? now();
        $totalSeconds = (int) $attempt->started_at->diffInSeconds($end);
        if ($totalSeconds < 60) {
            return $totalSeconds . 's';
        }
        $minutes = intdiv($totalSeconds, 60);
        $seconds = $totalSeconds % 60;

        return $seconds > 0 ? ($minutes . 'm ' . $seconds . 's') : ($minutes . 'm');
    };
@endphp

<table class="pg-table">
    <thead>
        <tr>
            <th>Student</th>
            <th>Score</th>
            <th>Total time</th>
            <th>Violations</th>
        </tr>
    </thead>
    <tbody>
    @if($attempts->isEmpty())
        <tr>
            <td colspan="4" class="pg-table-empty">{{ $emptyMessage ?? 'No records yet.' }}</td>
        </tr>
    @else
        @foreach($attempts as $attempt)
            @php
                $studentName = $attempt->student->name ?? 'Unknown';
                $warnings = $attempt->warning_count ?? 0;
            @endphp
            <tr>
                <td class="exam-col">
                    <div class="exam-cell">
                        <span class="pg-exam-cell-title">{{ $studentName }}</span>
                    </div>
                </td>
                <td>
                    @if($attempt->submitted_at && $attempt->total > 0)
                        {{ $attempt->score }}/{{ $attempt->total }} ({{ round(($attempt->score / $attempt->total) * 100) }}%)
                    @else
                        —
                    @endif
                </td>
                <td>{{ $formatAttemptTime($attempt) }}</td>
                <td class="{{ $warnings > 0 ? 'warn' : '' }}">{{ $warnings }}</td>
            </tr>
        @endforeach
    @endif
    </tbody>
</table>

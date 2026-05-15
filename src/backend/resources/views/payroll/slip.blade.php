<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>給与明細 {{ $periodLabel }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .meta { color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .amount { text-align: right; }
        .net { font-size: 16px; font-weight: bold; margin-top: 12px; }
        .note { font-size: 10px; color: #666; margin-top: 24px; }
    </style>
</head>
<body>
    <h1>給与明細</h1>
    <p class="meta">{{ $periodLabel }} · {{ $userName }}</p>

    <p class="net">手取り額: ¥{{ number_format($net, 0) }}</p>
    <p>支給額 ¥{{ number_format($gross, 0) }} − 控除合計 ¥{{ number_format($deductionTotal, 0) }}</p>

    <h2>支給</h2>
    <table>
        <tr><th>項目</th><th class="amount">金額</th></tr>
        <tr><td>基本給</td><td class="amount">¥{{ number_format($baseSalary, 0) }}</td></tr>
        @foreach ($allowances as $row)
            <tr><td>{{ $row['name'] }}</td><td class="amount">¥{{ number_format($row['amount'], 0) }}</td></tr>
        @endforeach
    </table>

    <h2>控除</h2>
    <table>
        <tr><th>項目</th><th class="amount">金額</th></tr>
        @foreach ($deductions as $row)
            <tr><td>{{ $row['name'] }}</td><td class="amount">¥{{ number_format($row['amount'], 0) }}</td></tr>
        @endforeach
    </table>

    @if (!empty($attendance))
        <h2>勤怠サマリ</h2>
        <table>
            <tr><td>出勤日数</td><td>{{ $attendance['work_days'] ?? 0 }} 日</td></tr>
            <tr><td>欠勤日数</td><td>{{ $attendance['absence_days'] ?? 0 }} 日</td></tr>
        </table>
    @endif

    <p class="note">本明細はシステムによる簡易計算結果です。実務の給与支払いは人事・労務の確認を前提とします。</p>
</body>
</html>

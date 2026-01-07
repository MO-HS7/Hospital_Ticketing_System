param(
    [string]$BaseUrl = "http://localhost:8000/api",
    [int]$Iterations = 10,
    [switch]$Detailed = $false
)

$endpoints = @(
    @{ Path = "health"; Method = "GET" },
    @{ Path = "departments"; Method = "GET" }
    # Login requires payload, simplistic harness for now focuses on GETs
)

function Get-Percentile($sortedValues, $percentile) {
    if ($sortedValues.Count -eq 0) { return 0 }
    $index = [Math]::Ceiling(($percentile / 100.0) * $sortedValues.Count) - 1
    return $sortedValues[[Math]::Max(0, [Math]::Min($sortedValues.Count - 1, $index))]
}

Write-Host "Running Performance Harness on $BaseUrl ($Iterations iterations)..." -ForegroundColor Cyan

foreach ($ep in $endpoints) {
    $times = @()
    $url = "$BaseUrl/$($ep.Path)"
    
    Write-Host "`nMeasuring $url..." -NoNewline
    
    for ($i = 1; $i -le $Iterations; $i++) {
        $start = Get-Date
        try {
            $resp = Invoke-WebRequest -Uri $url -Method $ep.Method -UseBasicParsing -ErrorAction Stop
        } catch {
            Write-Host "X" -NoNewline -ForegroundColor Red
            continue
        }
        $end = Get-Date
        $duration = ($end - $start).TotalMilliseconds
        $times += $duration
        Write-Host "." -NoNewline -ForegroundColor Green
    }
    
    if ($times.Count -gt 0) {
        $stats = $times | Sort-Object
        $min = $stats[0]
        $max = $stats[-1]
        $avg = ($stats | Measure-Object -Average).Average
        $p50 = Get-Percentile $stats 50
        $p95 = Get-Percentile $stats 95
        
        Write-Host "`n  Min: $([Math]::Round($min))ms"
        Write-Host "  Max: $([Math]::Round($max))ms"
        Write-Host "  Avg: $([Math]::Round($avg))ms"
        Write-Host "  p50: $([Math]::Round($p50))ms"
        Write-Host "  p95: $([Math]::Round($p95))ms" -ForegroundColor Yellow
    } else {
        Write-Host "`n  No successful requests." -ForegroundColor Red
    }
}

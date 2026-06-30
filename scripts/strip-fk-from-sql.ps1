# Remove FOREIGN KEY / REFERENCES lines from a MySQL dump for InfinityFree import.
param(
    [Parameter(Mandatory = $true)]
    [string]$InputFile,
    [Parameter(Mandatory = $true)]
    [string]$OutputFile
)

if (-not (Test-Path $InputFile)) {
    throw "Input file not found: $InputFile"
}

$lines = Get-Content $InputFile -Encoding UTF8
$out = New-Object System.Collections.Generic.List[string]
$skip = $false

foreach ($line in $lines) {
    if ($line -match '^\s*CONSTRAINT\s+`') {
        if ($line -match ',\s*$') { continue }
        if ($line -match '\)\s*,?\s*$') { continue }
        $skip = $true
        continue
    }
    if ($skip) {
        if ($line -match '\)\s*,?\s*$') { $skip = $false }
        continue
    }
    if ($line -match 'FOREIGN KEY|REFERENCES `') { continue }
    if ($line -match 'ADD CONSTRAINT') { continue }
    $out.Add($line)
}

$parent = Split-Path -Parent $OutputFile
if ($parent -and -not (Test-Path $parent)) {
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
}

$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllLines($OutputFile, $out.ToArray(), $utf8NoBom)
Write-Host "Wrote $($out.Count) lines to $OutputFile (UTF-8 without BOM)"

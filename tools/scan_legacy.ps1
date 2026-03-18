$ErrorActionPreference = "Stop"

$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$webbaru = Join-Path $root "WebBaru"
$frm = Join-Path $root "src\\simrskhanza\\frmUtama.java"
$dbxml = Join-Path $root "setting\\database.xml"

if (!(Test-Path $frm)) {
    throw "File frmUtama.java tidak ditemukan: $frm"
}

$lines = Get-Content $frm

$btnText = @{}
foreach ($l in $lines) {
    if ($l -match '^(\s*)(btn\w+)\.setText\("([^"]*)"\);') {
        $btnText[$matches[2]] = $matches[3]
    }
}

$map = @()
for ($i = 0; $i -lt $lines.Count - 2; $i++) {
    if ($lines[$i] -match 'if\(akses\.get(\w+)\(\)==true\)\{') {
        $perm = $matches[1]
        for ($j = $i + 1; $j -le [Math]::Min($i + 6, $lines.Count - 1); $j++) {
            if ($lines[$j] -match 'Panelmenu\.add\((btn\w+)\);') {
                $btn = $matches[1]
                $label = if ($btnText.ContainsKey($btn)) { $btnText[$btn] } else { "" }
                $map += [PSCustomObject]@{
                    permission = $perm
                    button = $btn
                    label = $label
                }
                break
            }
        }
    }
}

$menuMap = $map | Sort-Object permission, button -Unique
$menuMap | ConvertTo-Json -Depth 4 | Set-Content -Encoding UTF8 (Join-Path $webbaru "docs_menu_access.json")

if (Test-Path $dbxml) {
    $xml = [xml](Get-Content $dbxml)
    $keys = @($xml.properties.entry | ForEach-Object { $_.key }) | Sort-Object
    $keys | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $webbaru "docs_config_keys.json")
}

$pkgs = Get-ChildItem (Join-Path $root "src") -Recurse -File -Filter *.java |
    ForEach-Object { $_.Directory.Name } |
    Group-Object |
    Sort-Object Count -Descending |
    Select-Object @{Name = "package"; Expression = { $_.Name } }, @{Name = "count"; Expression = { $_.Count }}
$pkgs | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $webbaru "docs_packages.json")

Write-Output "scan selesai"
Write-Output ("menu_map=" + $menuMap.Count)
Write-Output ("button_text=" + $btnText.Count)
if (Test-Path (Join-Path $webbaru "docs_config_keys.json")) {
    $ck = Get-Content (Join-Path $webbaru "docs_config_keys.json") | ConvertFrom-Json
    Write-Output ("config_keys=" + $ck.Count)
}

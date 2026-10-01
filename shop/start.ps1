$ErrorActionPreference='Stop'
$projectRoot=Split-Path $PSScriptRoot -Parent
$runtime=Join-Path $projectRoot 'work/shop-runtime'
$php=Join-Path $runtime 'php/php.exe'
$wp=Join-Path $runtime 'wordpress/wordpress'
if (!(Test-Path -LiteralPath $php) -or !(Test-Path -LiteralPath "$wp/wp-config.php")) { throw 'Run shop/setup.ps1 first.' }
Copy-Item -Path "$PSScriptRoot/theme/*" -Destination "$wp/wp-content/themes/haya2" -Recurse -Force
Copy-Item -LiteralPath "$PSScriptRoot/plugins/haya2-demo.php" -Destination "$wp/wp-content/plugins/haya2-demo/haya2-demo.php" -Force
Write-Host 'Haya2: http://127.0.0.1:8088 — Ctrl+C to stop'
& $php -S 127.0.0.1:8088 -t $wp "$PSScriptRoot/router.php"

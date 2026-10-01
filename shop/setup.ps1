$ErrorActionPreference='Stop'
$projectRoot=Split-Path $PSScriptRoot -Parent
$runtime=Join-Path $projectRoot 'work/shop-runtime'
New-Item -ItemType Directory -Force $runtime | Out-Null
$packages=@(
 @('php','https://downloads.php.net/~windows/releases/php-8.3.35-nts-Win32-vs16-x64.zip','php/php.exe'),
 @('wordpress','https://wordpress.org/latest.zip','wordpress/wordpress/wp-load.php'),
 @('woocommerce','https://downloads.wordpress.org/plugin/woocommerce.latest-stable.zip','woocommerce/woocommerce/woocommerce.php'),
 @('sqlite','https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip','sqlite/sqlite-database-integration/db.copy'),
 @('stripe','https://downloads.wordpress.org/plugin/woocommerce-gateway-stripe.latest-stable.zip','stripe/woocommerce-gateway-stripe/woocommerce-gateway-stripe.php')
)
foreach($package in $packages){
 if(!(Test-Path -LiteralPath (Join-Path $runtime $package[2]))){
  Invoke-WebRequest -Uri $package[1] -OutFile "$runtime/$($package[0]).zip" -TimeoutSec 180
  Expand-Archive -LiteralPath "$runtime/$($package[0]).zip" -DestinationPath "$runtime/$($package[0])" -Force
 }
}
$php=Join-Path $runtime 'php/php.exe'
$wp=Join-Path $runtime 'wordpress/wordpress'
$ini=@"
extension_dir="$runtime/php/ext"
extension=curl
extension=openssl
extension=mbstring
extension=gd
extension=intl
extension=pdo_sqlite
extension=sqlite3
extension=zip
memory_limit=512M
max_execution_time=180
upload_max_filesize=32M
post_max_size=32M
date.timezone=Asia/Dubai
display_errors=Off
log_errors=On
error_log="$runtime/php-errors.log"
"@
Set-Content -LiteralPath "$runtime/php/php.ini" -Value $ini
foreach($item in @(@('woocommerce/woocommerce','woocommerce'),@('sqlite/sqlite-database-integration','sqlite-database-integration'),@('stripe/woocommerce-gateway-stripe','woocommerce-gateway-stripe'))){
 $target="$wp/wp-content/plugins/$($item[1])"
 if(!(Test-Path -LiteralPath $target)){Copy-Item -LiteralPath "$runtime/$($item[0])" -Destination $target -Recurse}
}
Copy-Item -LiteralPath "$runtime/sqlite/sqlite-database-integration/db.copy" -Destination "$wp/wp-content/db.php" -Force
if(!(Test-Path -LiteralPath "$wp/wp-config.php")){
 $salt=[guid]::NewGuid().ToString()+[guid]::NewGuid().ToString()
 $config=@'
<?php
define('DB_NAME','haya2');
define('DB_USER','local');
define('DB_PASSWORD','');
define('DB_HOST','localhost');
define('DB_CHARSET','utf8mb4');
define('DB_COLLATE','');
define('WP_ENVIRONMENT_TYPE','local');
define('WP_HOME','http://127.0.0.1:8088');
define('WP_SITEURL','http://127.0.0.1:8088');
define('DISABLE_WP_CRON',true);
define('WP_DEBUG',false);
define('AUTH_KEY','LOCAL_SALT');
define('SECURE_AUTH_KEY','LOCAL_SALT-secure');
define('LOGGED_IN_KEY','LOCAL_SALT-login');
define('NONCE_KEY','LOCAL_SALT-nonce');
define('AUTH_SALT','LOCAL_SALT-auth');
define('SECURE_AUTH_SALT','LOCAL_SALT-securesalt');
define('LOGGED_IN_SALT','LOCAL_SALT-loginsalt');
define('NONCE_SALT','LOCAL_SALT-noncesalt');
$table_prefix='wp_';
if (!defined('ABSPATH')) define('ABSPATH',__DIR__.'/');
require_once ABSPATH.'wp-settings.php';
'@
 Set-Content -LiteralPath "$wp/wp-config.php" -Value $config.Replace('LOCAL_SALT',$salt)
}
$themePath="$wp/wp-content/themes/haya2"
New-Item -ItemType Directory -Force $themePath,"$wp/wp-content/plugins/haya2-demo" | Out-Null
Copy-Item -Path "$PSScriptRoot/theme/*" -Destination $themePath -Recurse -Force
Copy-Item -LiteralPath "$PSScriptRoot/plugins/haya2-demo.php" -Destination "$wp/wp-content/plugins/haya2-demo/haya2-demo.php" -Force
& $php "$PSScriptRoot/seed.php"
if($LASTEXITCODE -ne 0){throw 'WordPress setup failed. Check work/shop-runtime/php-errors.log.'}
Write-Host 'Setup complete. Run shop/start.ps1. Admin credentials are in work/shop-runtime/admin-credentials.txt.'

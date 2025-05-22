param(
    [string]$dbname = "wordpress_test",
    [string]$dbuser = "root",
    [string]$dbpass = "",
    [string]$dbhost = "localhost",
    [string]$wp_version = "latest",
    [bool]$skip_db_create = $false,
    [string]$dbcharset = "utf8"
)

# Set up paths
$temp_dir = "$env:TEMP\wordpress-tests-lib"
$wp_tests_config = "$temp_dir\wp-tests-config.php"

# Ensure a clean slate
if (Test-Path -Path $temp_dir) {
    Write-Host "Removing existing WordPress test library directory: $temp_dir"
    Remove-Item -Path $temp_dir -Recurse -Force
}

Write-Host "Downloading WordPress test library..."
$url = "https://github.com/WordPress/wordpress-develop/archive/refs/heads/trunk.zip"
$zip_file = "$env:TEMP\wordpress-tests-lib.zip"

Write-Host "Attempting to download from: $url"
# Download the file
Invoke-WebRequest -Uri $url -OutFile $zip_file
Write-Host "Downloaded zip file exists: $(Test-Path -Path $zip_file)"

# Extract the file
Expand-Archive -Path $zip_file -DestinationPath $temp_dir -Force
Write-Host "Extracted files to: $temp_dir"
Write-Host "Contents of temp_dir after extraction:"
Get-ChildItem -Path $temp_dir | ForEach-Object { Write-Host $_.Name }

# Clean up
Remove-Item -Path $zip_file -Force

# Move files from the versioned directory to the root
$versioned_dir = Get-ChildItem -Path $temp_dir -Directory | Select-Object -First 1
if ($versioned_dir) {
    Write-Host "Found versioned directory: $($versioned_dir.FullName)"
    Get-ChildItem -Path $versioned_dir.FullName | Move-Item -Destination $temp_dir -Force
    Write-Host "Moved files from $($versioned_dir.FullName) to $temp_dir"
    Remove-Item -Path $versioned_dir.FullName -Recurse -Force
} else {
    Write-Host "No versioned directory found."
}
    
    # Create the test config
    Copy-Item -Path "$temp_dir\wp-tests-config-sample.php" -Destination $wp_tests_config -Force
    
    # Update the test config
    (Get-Content $wp_tests_config) | ForEach-Object {
        $_ -replace 'youremptytestdbnamehere', $dbname `
           -replace 'yourusernamehere', $dbuser `
           -replace 'yourpasswordhere', $dbpass `
           -replace 'localhost', $dbhost `
           -replace "define\( 'DB_CHARSET', 'utf8' \);", "define( 'DB_CHARSET', '$dbcharset' );"
    } | Set-Content $wp_tests_config -Force
    
    # Create the database if needed
    if (-not $skip_db_create) {
        try {
            $connection = New-Object MySql.Data.MySqlClient.MySqlConnection
            $connection.ConnectionString = "server=$dbhost;user id=$dbuser;password=$dbpass"
            $connection.Open()
            $command = $connection.CreateCommand()
            $command.CommandText = "CREATE DATABASE IF NOT EXISTS $dbname"
            $command.ExecuteNonQuery() | Out-Null
            $connection.Close()
        } catch {
            Write-Warning "Failed to create database: $_"
        }
    }
    
    # Install WordPress test suite
    Push-Location $temp_dir
    try {
        php .\install.php
    } finally {
        Pop-Location
    }

Write-Host "WordPress test environment is ready at: $temp_dir"

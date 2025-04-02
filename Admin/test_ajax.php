<?php
// Simple test file to check AJAX functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'layouts/session.php';
include 'layouts/head-main.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>AJAX Test | Gym Management System</title>
    <?php include 'layouts/head.php'; ?>
    <?php include 'layouts/head-style.php'; ?>
</head>

<body>
<?php include 'layouts/body.php'; ?>

<div id="layout-wrapper">
    <?php include 'layouts/menu.php'; ?>
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-flex align-items-center justify-content-between">
                            <h4 class="mb-0">AJAX Test</h4>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">Test Package Loading</h4>
                                
                                <div class="mt-4">
                                    <button id="testPackagesBtn" class="btn btn-primary">Test Get Packages</button>
                                    <button id="testRenewBtn" class="btn btn-success">Test Renew Subscription</button>
                                </div>
                                
                                <div class="mt-4">
                                    <h5>Results:</h5>
                                    <pre id="ajaxResult" class="p-3 bg-light">Click a button to test...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>
</div>

<?php include 'layouts/vendor-scripts.php'; ?>

<script>
    $(document).ready(function() {
        // Test packages ajax
        $('#testPackagesBtn').on('click', function() {
            $('#ajaxResult').html('Loading packages...');
            
            $.ajax({
                url: 'ajax/get_packages.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    $('#ajaxResult').html(JSON.stringify(data, null, 2));
                    console.log('Packages loaded successfully:', data);
                },
                error: function(xhr, status, error) {
                    $('#ajaxResult').html('Error: ' + error + '\n\nResponse: ' + xhr.responseText);
                    console.error('AJAX error:', status, error, xhr.responseText);
                }
            });
        });
        
        // Test renewal
        $('#testRenewBtn').on('click', function() {
            // Get the first client ID for testing
            var testClientId = prompt("Enter a client ID to test renewal:", "");
            if (!testClientId) return;
            
            $('#ajaxResult').html('Testing renewal...');
            
            $.ajax({
                url: 'ajax/get_packages.php',
                type: 'GET',
                dataType: 'json',
                success: function(packages) {
                    if (packages.length === 0) {
                        $('#ajaxResult').html('No packages available');
                        return;
                    }
                    
                    // Use the first package for testing
                    var testPackageId = packages[0].id;
                    
                    // Now try renewal
                    fetch(`renew_subscription.php?id=${testClientId}&package=${testPackageId}&months=1`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText);
                            }
                            return response.text();
                        })
                        .then(data => {
                            try {
                                var jsonData = JSON.parse(data);
                                $('#ajaxResult').html('Renewal Response: ' + JSON.stringify(jsonData, null, 2));
                            } catch (e) {
                                $('#ajaxResult').html('Error parsing JSON: ' + e + '\n\nRaw Response: ' + data);
                            }
                        })
                        .catch(error => {
                            $('#ajaxResult').html('Fetch error: ' + error);
                            console.error('Fetch error:', error);
                        });
                },
                error: function(xhr, status, error) {
                    $('#ajaxResult').html('Error loading packages: ' + error);
                    console.error('AJAX error:', status, error);
                }
            });
        });
    });
</script>

</body>
</html> 
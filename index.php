<?php
require_once 'includes/config.php';

$pageTitle = "Home";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="jumbotron bg-light p-5 rounded mb-4">
                <h1 class="display-4">Welcome to CPMS</h1>
                <p class="lead">College Placement Management System – Connecting Students, Teachers, and Companies Efficiently</p>
                <hr class="my-4">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a class="btn btn-primary btn-lg" href="<?php echo $base_url; ?>/login.php" role="button">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <a href="<?php echo $base_url; ?>/login.php?role=student" class="text-decoration-none">
                <div class="card h-100 hover-shadow">
                    <div class="card-body text-center">
                        <i class="fas fa-user-graduate fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Students</h5>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 mb-4">
            <a href="<?php echo $base_url; ?>/login.php?role=teacher" class="text-decoration-none">
                <div class="card h-100 hover-shadow">
                    <div class="card-body text-center">
                        <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Teachers</h5>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 mb-4">
            <a href="<?php echo $base_url; ?>/login.php?role=company" class="text-decoration-none">
                <div class="card h-100 hover-shadow">
                    <div class="card-body text-center">
                        <i class="fas fa-building fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Companies</h5>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


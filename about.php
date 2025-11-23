<?php
require_once 'includes/config.php';

$pageTitle = "About Us";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <h2 class="mb-4">About CPMS</h2>
            
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-3">College Placement Management System</h4>
                    <p class="card-text">
                        CPMS is a modern, minimal platform designed to streamline placement and internship management for multiple colleges. It connects students, teachers, and companies to manage postings and applications efficiently.
                    </p>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-bullseye text-primary me-2"></i>Our Mission</h5>
                            <p class="card-text">
                                To bridge the gap between students and internship opportunities by providing a 
                                user-friendly platform that simplifies the application and management process.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-eye text-success me-2"></i>Our Vision</h5>
                            <p class="card-text">
                                To become the leading platform for internship and placement management, helping 
                                students achieve their career goals through seamless connections with opportunities.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow mt-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Key Features</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <h6><i class="fas fa-check-circle text-primary me-2"></i>For Students</h6>
                            <ul>
                                <li>Browse available opportunities</li>
                                <li>Apply for internships</li>
                                <li>Track application status</li>
                                <li>Manage profile and resume</li>
                            </ul>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>For Teachers</h6>
                            <ul>
                                <li>Review student applications</li>
                                <li>Monitor student progress</li>
                                <li>Post internship opportunities</li>
                                <li>Generate reports</li>
                            </ul>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6><i class="fas fa-check-circle text-danger me-2"></i>For Companies</h6>
                            <ul>
                                <li>Post jobs and internships directly</li>
                                <li>Manage applications and communication</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


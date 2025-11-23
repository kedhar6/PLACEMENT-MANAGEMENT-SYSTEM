<?php
require_once 'includes/config.php';

$opportunities = [];
$sql = "SELECT o.*, u.username as posted_by_name, u.role as poster_role 
    FROM opportunities o 
    JOIN users u ON o.posted_by = u.id 
    WHERE o.status = 'approved' 
    AND o.application_deadline >= CURDATE()
    AND u.role IN ('teacher','company')
    ORDER BY o.created_at DESC 
    LIMIT 10";
$result = $conn->query($sql);
if ($result) {
    $opportunities = $result->fetch_all(MYSQLI_ASSOC);
}

// Debug logging
if (function_exists('error_log')) {
    error_log('[opportunities.php] Fetched opportunities: ' . count($opportunities));
}

$pageTitle = "Opportunities";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-briefcase text-primary me-2"></i>
                    Available Opportunities
                </h2>
                <div class="d-flex gap-2">
                    <input type="text" class="form-control" id="searchInput" placeholder="Search opportunities..." style="width: 250px;">
                    <select class="form-select" id="filterType" style="width: 150px;">
                        <option value="">All Types</option>
                        <option value="internship">Internship</option>
                        <option value="job">Job</option>
                    </select>
                    <select class="form-select" id="filterLocation" style="width: 150px;">
                        <option value="">All Locations</option>
                        <option value="Bangalore">Bangalore</option>
                        <option value="Mumbai">Mumbai</option>
                        <option value="Pune">Pune</option>
                        <option value="Delhi">Delhi</option>
                        <option value="Hyderabad">Hyderabad</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($opportunities)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No opportunities available at the moment</h4>
                    <p class="text-muted">Please check back later or contact the placement office.</p>
                    <div class="mt-4">
                        <a href="<?php echo $base_url; ?>/" class="btn btn-outline-primary me-2">
                            <i class="fas fa-home me-2"></i>Back to Home
                        </a>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'student'): ?>
                            <a href="<?php echo $base_url; ?>/modules/student/opportunities.php" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>View All Opportunities
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="row" id="opportunitiesContainer">
                    <?php foreach ($opportunities as $opp): ?>
                        <div class="col-lg-4 col-md-6 mb-4 opportunity-card" 
                             data-type="<?php echo $opp['type']; ?>" 
                             data-location="<?php echo htmlspecialchars($opp['location']); ?>"
                             data-title="<?php echo htmlspecialchars($opp['title']); ?>"
                             data-company="<?php echo htmlspecialchars($opp['company_name']); ?>">
                            <div class="card h-100 shadow-sm opportunity-card-inner">
                                <div class="card-header bg-white border-0 pt-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($opp['title']); ?></h5>
                                            <h6 class="text-muted mb-2">
                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($opp['company_name']); ?>
                                            </h6>
                                        </div>
                                        <span class="badge bg-primary rounded-pill"><?php echo ucfirst($opp['type']); ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars(substr($opp['description'], 0, 120)); ?>...
                                    </p>
                                    <div class="mb-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if (!empty($opp['location'])): ?>
                                                <span class="badge bg-light text-dark">
                                                    <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($opp['location']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($opp['stipend_salary'])): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-rupee-sign me-1"></i><?php echo htmlspecialchars($opp['stipend_salary']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            Deadline: <?php echo date('M d, Y', strtotime($opp['application_deadline'])); ?>
                                        </small>
                                        <?php 
                                        $days_left = (strtotime($opp['application_deadline']) - strtotime(date('Y-m-d'))) / 86400;
                                        if ($days_left <= 7): ?>
                                            <span class="badge bg-warning text-dark"><?php echo round($days_left); ?> days left</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-0">
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'student'): ?>
                                        <div class="d-grid gap-2">
                                            <a href="<?php echo $base_url; ?>/modules/student/view-opportunity.php?id=<?php echo $opp['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-grid gap-2">
                                            <a href="<?php echo $base_url; ?>/login.php" class="btn btn-outline-primary">
                                                <i class="fas fa-sign-in-alt me-2"></i>Login to Apply
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const filterLocation = document.getElementById('filterLocation');
    const opportunityCards = document.querySelectorAll('.opportunity-card');
    const container = document.getElementById('opportunitiesContainer');
    
    function filterOpportunities() {
        const searchTerm = searchInput.value.toLowerCase();
        const typeFilter = filterType.value;
        const locationFilter = filterLocation.value;
        
        let visibleCount = 0;
        
        opportunityCards.forEach(card => {
            const title = card.dataset.title.toLowerCase();
            const company = card.dataset.company.toLowerCase();
            const type = card.dataset.type;
            const location = card.dataset.location;
            
            const matchesSearch = title.includes(searchTerm) || company.includes(searchTerm);
            const matchesType = !typeFilter || type === typeFilter;
            const matchesLocation = !locationFilter || location === locationFilter;
            
            if (matchesSearch && matchesType && matchesLocation) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show no results message if needed
        if (visibleCount === 0) {
            if (!document.getElementById('noResults')) {
                const noResultsDiv = document.createElement('div');
                noResultsDiv.id = 'noResults';
                noResultsDiv.className = 'col-12 text-center py-5';
                noResultsDiv.innerHTML = `
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No opportunities found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                `;
                container.appendChild(noResultsDiv);
            }
        } else {
            const noResults = document.getElementById('noResults');
            if (noResults) {
                noResults.remove();
            }
        }
    }
    
    searchInput.addEventListener('input', filterOpportunities);
    filterType.addEventListener('change', filterOpportunities);
    filterLocation.addEventListener('change', filterOpportunities);
});
</script>

<style>
.opportunity-card-inner {
    transition: transform 0.2s, box-shadow 0.2s;
}

.opportunity-card-inner:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.opportunity-card {
    transition: opacity 0.3s;
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.badge {
    font-size: 0.75em;
}
</style>

<?php include 'includes/footer.php'; ?>


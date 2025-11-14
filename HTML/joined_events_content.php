<?php if (empty($joined_events)): ?>
    <div class="no-events">
        <div class="no-events-icon">🐾</div>
        <h3>No Joined Events</h3>
        <p>You haven't joined any events yet. Browse upcoming events to get started!</p>
        <a href="events.php" class="action-btn view-btn" style="margin-top: 15px; display: inline-block; width: auto;">
            Browse Events
        </a>
    </div>
<?php else: ?>
    <?php foreach ($joined_events as $event): ?>
    <div class="event-card">
        <div class="event-header">
            <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
            <span class="event-status status-joined">Joined</span>
        </div>
        
        <div class="event-details">
            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
            
            <div class="event-meta">
                <div class="event-meta-item">
                    <strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?>
                </div>
                <div class="event-meta-item">
                    <strong>Time:</strong> <?php echo htmlspecialchars($event['time']); ?>
                </div>
                <div class="event-meta-item">
                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                </div>
                <div class="event-meta-item">
                    <strong>Host:</strong> <?php echo htmlspecialchars($event['host']); ?>
                </div>
                <!-- Pet Information - Only show if available -->
                <?php if (isset($event['pet']) && $event['pet']['name']): ?>
                <div class="event-meta-item">
                    <strong>Joined With:</strong> 
                    <?php echo htmlspecialchars($event['pet']['name']); ?> 
                    (<?php echo htmlspecialchars($event['pet']['species']); ?> - <?php echo htmlspecialchars($event['pet']['age']); ?>)
                </div>
                <?php endif; ?>
                <div class="event-meta-item">
                    <strong>Joined On:</strong> <?php echo date('F j, Y', strtotime($event['joined_at'])); ?>
                </div>
            </div>
            
            <?php if (!empty($event['services'])): ?>
            <div class="event-services">
                <span class="services-label">Services Included:</span>
                <div class="service-tags">
                    <?php foreach ($event['services'] as $service): ?>
                        <span class="service-tag"><?php echo htmlspecialchars($service); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="event-actions">
            <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="action-btn view-btn">View Details</a>
            <button class="action-btn cancel-btn" onclick="cancelJoin(<?php echo $event['participant_id']; ?>)">Cancel Join</button>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
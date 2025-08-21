<!-- Edit Rental Modal -->
<div class="modal fade" id="editRentalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Rental</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="edit_rental" value="1">
                    <input type="hidden" name="rental_id" id="edit_rental_id">
                    
                    <div class="row g-3">
                        <!-- Customer -->
                        <div class="col-md-6">
                            <label class="form-label">Customer</label>
                            <select class="form-select" name="customer_id" id="edit_rental_customer" required>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Vehicle -->
                        <div class="col-md-6">
                            <label class="form-label">Vehicle</label>
                            <select class="form-select" name="vehicle_id" id="edit_rental_vehicle" required>
                                <?php foreach ($vehicles as $v): ?>
                                    <option value="<?= $v['id'] ?>">
                                        <?= htmlspecialchars($v['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Dates -->
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="edit_rental_start" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="edit_rental_end" required>
                        </div>

                        <!-- Rate & Status -->
                        <div class="col-md-6">
                            <label class="form-label">Daily Rate</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" 
                                    name="daily_rate" id="edit_rental_rate" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="edit_rental_status">
                                <option value="Active">Active</option>
                                <option value="Reserved">Reserved</option>
                                <option value="Completed">Completed</option>
                                <option value="Canceled">Canceled</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_rental" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Update Rental
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
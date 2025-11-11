<?php
// components/manage_groups/members.php
?>

<!-- Dashboard Section -->
<div id="dashboard-section" class="content-section active">
    <div class="container-fluid">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card members">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Members</p>
                        <h3 class="stat-card-value"><?= count($members) ?></h3>
                    </div>
                </div>
            </div>
            <div class="stat-card savings">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-piggy-bank"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Total Savings</p>
                        <h3 class="stat-card-value">KSh <?= number_format($totalSavings, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="stat-card withdrawals">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Total Withdrawals</p>
                        <h3 class="stat-card-value">KSh <?= number_format($totalWithdrawals, 2) ?></h3>
                    </div>
                </div>
            </div>
            <div class="stat-card balance">
                <div class="stat-card-header">
                    <div class="stat-card-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="stat-card-content">
                        <p class="stat-card-title">Net Balance</p>
                        <h3 class="stat-card-value">KSh <?= number_format($netBalance, 2) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Group Information Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">Group Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Group Name:</strong> <?= htmlspecialchars($groupDetails['group_name']) ?></p>
                        <p><strong>Reference:</strong> <?= htmlspecialchars($groupDetails['group_reference']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Area:</strong> <?= htmlspecialchars($groupDetails['area']) ?></p>
                        <p><strong>Field Officer:</strong> <?= htmlspecialchars($groupDetails['firstname'] . ' ' . $groupDetails['lastname']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Members Section -->
<div id="members-section" class="content-section">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="m-0">Group Members</h5>
                <button class="btn btn-success" data-toggle="modal" data-target="#addMemberModal">
                    <i class="fas fa-user-plus"></i> Add Member
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="membersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Member Name</th>
                                <th>Shareholder No</th>
                                <th>Phone Number</th>
                                <th>Location</th>
                                <th>Date Joined</th>
                                <th>Total Savings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></td>
                                    <td><?= htmlspecialchars($member['shareholder_no']) ?></td>
                                    <td><?= htmlspecialchars($member['phone_number']) ?></td>
                                    <td><?= htmlspecialchars($member['location']) ?></td>
                                    <td><?= date("Y-m-d", strtotime($member['date_joined'])) ?></td>
                                    <td>KSh <?= number_format($member['total_savings'], 2) ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-primary view-member" 
                                                    data-member-id="<?= $member['account_id'] ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger remove-member" 
                                                    data-member-id="<?= $member['account_id'] ?>"
                                                    data-member-name="<?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?>">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #51087E;">
                <h5 class="modal-title text-white">Add New Member</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addMemberForm">
                <div class="modal-body">
                    <input type="hidden" name="group_id" value="<?= $groupId ?>">
                    <div class="form-group">
                        <label>Select Client</label>
                        <select class="form-control select2-clients" name="account_id" style="width: 100%">
                            <option value="">Search by name or shareholder number...</option>
                            <?php
                            // Fetch clients who aren't in any group
                            $query = "SELECT a.* FROM client_accounts a 
                                    LEFT JOIN group_members m ON a.account_id = m.account_id
                                    WHERE m.group_id IS NULL OR m.status = 'inactive'
                                    ORDER BY a.first_name, a.last_name";
                            $result = $db->conn->query($query);
                            while ($client = $result->fetch_assoc()) {
                                echo "<option value='" . $client['account_id'] . "' 
                                      data-shareholder='" . $client['shareholder_no'] . "'
                                      data-phone='" . $client['phone_number'] . "'
                                      data-location='" . $client['location'] . "'
                                      data-division='" . $client['division'] . "'
                                      data-village='" . $client['village'] . "'>";
                                echo $client['first_name'] . ' ' . $client['last_name'] . 
                                     ' (' . $client['shareholder_no'] . ')';
                                echo "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="clientDetails" style="display: none;">
                        <h6 class="font-weight-bold mt-3">Selected Client Details:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Shareholder No:</strong> <span id="clientShareholderNo"></span></p>
                                <p><strong>Phone:</strong> <span id="clientPhone"></span></p>
                                <p><strong>Location:</strong> <span id="clientLocation"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Division:</strong> <span id="clientDivision"></span></p>
                                <p><strong>Village:</strong> <span id="clientVillage"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Remove Member Modal -->
<div class="modal fade" id="removeMemberModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">Remove Member</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove <span id="memberToRemove"></span> from the group?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRemove">Remove</button>
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
    // Initialize DataTables for Members
    $('#membersTable').DataTable({
        responsive: true,
        pageLength: 10,
        language: {
            search: "Search members:",
            lengthMenu: "Show _MENU_ members per page",
            info: "Showing _START_ to _END_ of _TOTAL_ members"
        }
    });

    // Initialize Select2 for client selection with search
    $('.select2-clients').select2({
        placeholder: 'Search by name or shareholder number',
        width: '100%',
        dropdownParent: $('#addMemberModal'),
        allowClear: true
    }).on('select2:select', function(e) {
        var option = $(this).find(':selected');
        
        // Display client details
        $('#clientShareholderNo').text(option.data('shareholder'));
        $('#clientPhone').text(option.data('phone'));
        $('#clientLocation').text(option.data('location'));
        $('#clientDivision').text(option.data('division'));
        $('#clientVillage').text(option.data('village'));
        $('#clientDetails').show();
    });

    // Handle member addition
    $('#addMemberForm').on('submit', function(e) {
        e.preventDefault();
        
        var accountId = $('.select2-clients').val();
        if (!accountId) {
            showMessage('Please select a client first', 'error');
            return;
        }

        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: {
                action: 'addMember',
                group_id: <?= $groupId ?>,
                account_id: accountId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#addMemberModal').modal('hide');
                    showMessage('Member added successfully', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error adding member. Please try again.', 'error');
            }
        });
    });

    // Reset form and details when modal is closed
    $('#addMemberModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('.select2-clients').val(null).trigger('change');
        $('#clientDetails').hide();
    });

    // Remove Member Click - Using event delegation for DataTables pagination
    // This ensures the click event works on all pages, not just the first one
    $(document).on('click', '.remove-member', function() {
        var memberId = $(this).data('member-id');
        var memberName = $(this).data('member-name');
        $('#memberToRemove').text(memberName);
        $('#confirmRemove').data('id', memberId);
        $('#removeMemberModal').modal('show');
    });

    // Confirm Remove Member
    $('#confirmRemove').click(function() {
        var memberId = $(this).data('id');
        $.ajax({
            url: '../controllers/groupController.php',
            method: 'POST',
            data: {
                action: 'removeMember',
                group_id: <?= $groupId ?>,
                account_id: memberId
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#removeMemberModal').modal('hide');
                    showMessage('Member removed successfully', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage('Error: ' + response.message, 'error');
                }
            },
            error: function() {
                showMessage('Error removing member', 'error');
            }
        });
    });

    // View member details - Also using event delegation
    $(document).on('click', '.view-member', function() {
        var memberId = $(this).data('member-id');
        window.open(`../views/view_account.php?id=${memberId}`, '_blank');
    });
});
</script>
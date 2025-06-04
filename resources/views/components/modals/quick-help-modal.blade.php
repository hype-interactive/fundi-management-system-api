<div wire:ignore.self class="modal fade" id="quickHelpModal" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    SMS Campaign Help
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="help-section">
                    <h6><i class="fa-solid fa-envelope mr-2"></i>Creating Campaigns</h6>
                    <ul class="">
                        <li>Use templates to save time with common messages</li>
                        <li>Messages are limited to 1000 characters</li>
                        <li>You can send immediately or schedule for later</li>
                        <li>Preview recipient count before sending</li>
                    </ul>
                </div>

                <div class="help-section mt-3">
                    <h6><i class="fa-solid fa-users mr-2"></i>Recipients</h6>
                    <ul class="">
                        <li><strong>All:</strong> Sends to both operators and workers</li>
                        <li><strong>Operators:</strong> Sends only to system operators</li>
                        <li><strong>Workers:</strong> Sends only to field workers (mafundi)</li>
                        <li><strong>Custom:</strong> Manually select specific recipients</li>
                    </ul>
                </div>

                <div class="help-section mt-3">
                    <h6><i class="fa-solid fa-filter mr-2"></i>Filtering</h6>
                    <ul class="">
                        <li>Filter by region, district, or ward</li>
                        <li>Combine multiple location filters</li>
                        <li>Location filters work with recipient types</li>
                    </ul>
                </div>

                <div class="help-section mt-3">
                    <h6><i class="fa-solid fa-clock mr-2"></i>Scheduling</h6>
                    <ul class="">
                        <li>Send immediately for urgent messages</li>
                        <li>Schedule for optimal delivery times</li>
                        <li>Scheduled campaigns can be modified before sending</li>
                        <li>Minimum scheduling time is 5 minutes from now</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    Got it!
                </button>
            </div>
        </div>
    </div>
</div>

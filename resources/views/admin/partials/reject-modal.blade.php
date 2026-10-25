    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="rejectTitle">Reject post</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">You're rejecting <span class="modal-post-title"></span>. The author will be emailed the reason.</p>
                    <label class="form-label" for="rejectReason">Reason</label>
                    <select class="form-select mb-3" id="rejectReason">
                        <option>Breaks community rules</option>
                        <option>Spam or self-promotion</option>
                        <option>Inappropriate media</option>
                        <option>Off topic</option>
                        <option>Duplicate post</option>
                    </select>
                    <label class="form-label" for="rejectNote">Message to author <span class="opt">(optional)</span></label>
                    <textarea class="form-control" id="rejectNote" rows="3" maxlength="500" style="min-height: 90px;"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-fill" id="rejectConfirm">Reject post</button>
                </div>
            </div>
        </div>
    </div>

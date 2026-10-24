    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" id="reportForm">
                <div class="modal-header">
                    <h2 class="modal-title" id="reportTitle">Report this post</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Reports are anonymous. Tell us what's wrong and a moderator will take a look.</p>
                    <div class="report-options">
                        <label><input type="radio" name="reason" value="spam"><span><strong>Spam or self-promotion</strong><small>Ads, repeated links or scams</small></span></label>
                        <label><input type="radio" name="reason" value="harassment"><span><strong>Harassment or hate</strong><small>Targets or insults a person or group</small></span></label>
                        <label><input type="radio" name="reason" value="privacy"><span><strong>Private information</strong><small>Shares someone's personal details</small></span></label>
                        <label><input type="radio" name="reason" value="media"><span><strong>Inappropriate media</strong><small>Photos or video that break the rules</small></span></label>
                        <label><input type="radio" name="reason" value="other"><span><strong>Something else</strong><small>Tell us more below</small></span></label>
                    </div>
                    <label class="form-label mt-2" for="reportNote">Add details <span class="opt">(optional)</span></label>
                    <textarea class="form-control" id="reportNote" name="note" rows="3" maxlength="500" style="min-height: 90px;"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-soft" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-fill">Submit report</button>
                </div>
            </form>
        </div>
    </div>

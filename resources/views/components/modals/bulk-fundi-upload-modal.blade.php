<div class="modal fade" id="bulk" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-center" id="exampleModalLabel">Upload excel file</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @livewire('bulk-fundi-upload')
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-neg " data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-custom">Save</button>
            </div>
            </form> {{-- End of form to upload --}}
        </div>
    </div>
</div>

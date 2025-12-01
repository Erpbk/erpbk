<?php echo Form::open(['route' => ['riderEmails.destroy', $id], 'method' => 'delete']); ?>

<div class='btn-group'>
    <a href="javascript:void(0);" data-action="<?php echo e(route('riderEmails.show', $id)); ?>" data-title="View Email" data-size="md" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-eye"></i>
    </a>
    
</div>
<?php echo Form::close(); ?>

<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_emails/datatables_actions.blade.php ENDPATH**/ ?>
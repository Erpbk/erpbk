@include('partials.activate_deactivate_picker', [
    'formUrl' => route('sims.activateDeactivate'),
    'idsField' => 'sim_ids',
    'itemSingular' => 'SIM',
    'itemPlural' => 'SIMs',
    'deactivateItems' => $inOfficeSims,
    'activateItems' => $deactivatedSims,
    'deactivateHint' => 'Only SIMs currently in office can be deactivated. Deactivated SIMs cannot be assigned until they are activated again.',
    'activateHint' => 'Activating returns the selected SIMs to the office so they become available to assign again.',
])

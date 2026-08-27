@include('partials.activate_deactivate_picker', [
    'formUrl' => route('fuelCards.activateDeactivate'),
    'idsField' => 'card_ids',
    'itemSingular' => 'card',
    'itemPlural' => 'cards',
    'deactivateItems' => $inOfficeCards,
    'activateItems' => $deactivatedCards,
    'deactivateHint' => 'Only cards currently in office can be deactivated. Deactivated cards cannot be assigned to a rider until they are activated again.',
    'activateHint' => 'Activating returns the selected cards to the office so they become available to assign again.',
])

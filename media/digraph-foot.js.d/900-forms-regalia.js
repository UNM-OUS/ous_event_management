// level/field auto-setting stuff
$(() => {
    const levelFields = {
        'DOCTOR: PHD': 'PHD',
        'DOCTOR: NURSING': 'NURSING',
        'DOCTOR: PHYSICAL THERAPY': 'PHYSICAL THERAPY',
        'DOCTOR: EDUCATION': 'EDUCATION',
        'DOCTOR: JURIS DOCTOR': 'JURIS DOCTOR',
        'DOCTOR: FINE ARTS': 'FINE ARTS',
        'DOCTOR: MEDICINE': 'MEDICINE',
        'DOCTOR: PHARMACY': 'PHARMACY',
    };
    const levelChange = function (e) {
        const $this = $(this);
        const $field = $this.closest('div.class-DegreeInfoField').find('div.class-DegreeFieldField');
        const $fieldActual = $field.find('input.AutocompleteActual').val(levelFields[$this.val()]);
        if (levelFields[$this.val()]) {
            $field.hide();
            $fieldActual.val(levelFields[$this.val()]);
        } else {
            $field.show();
            $fieldActual.val('');
        }
    };
    $('select.class-DegreeLevelField')
        .each(levelChange)
        .change(levelChange);
});

// regalia opt out checkbox
$(() => {
    const ownedChange = function (e) {
        if ($(this).prop('checked')) {
            $(this).closest('div.class-RegaliaComboField').addClass('optedOut');
        } else {
            $(this).closest('div.class-RegaliaComboField').removeClass('optedOut');
        }
    };
    $('.regalia-optout-checkbox')
        .each(ownedChange)
        .change(ownedChange);
});
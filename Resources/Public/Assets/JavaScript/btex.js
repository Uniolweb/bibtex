$('a.bibtextoggle').click(function ()
    {
        $(this).parent('div').next('div').next('div').slideToggle();
        return false;
    }
);

// submit form when different sorting is selected (radio button)
$('input.bibtex-form-radio[type=radio]').on('change', function() {
    $(this).closest("form").submit();
});